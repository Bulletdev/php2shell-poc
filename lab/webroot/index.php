<?php
session_start();

// Chain A — LFI surface: template loader simulating a theme/page system
$page = $_GET['page'] ?? null;
if ($page) {
    include($page);
    exit;
}

$sid     = session_id();
$handler = ini_get('session.serialize_handler');
$php_ver = PHP_VERSION;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Início | scrims.lol</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:       #070C14;
  --sidebar:  rgba(15,24,35,.97);
  --surface:  #0c1a2e;
  --elevated: #0f2040;
  --hover:    rgba(200,155,60,.04);
  --line:     rgba(200,155,60,.45);
  --line-soft:rgba(200,155,60,.12);
  --text:     #e8e0d0;
  --text2:    rgba(255,255,255,.7);
  --text3:    rgba(200,155,60,.6);
  --accent:   #C89B3C;
  --acc-str:  #C89B3C;
  --acc-fill: #C89B3C;
  --acc-bg:   rgba(200,155,60,.08);
  --acc-bdr:  rgba(200,155,60,.45);
  --mint:     #4ECDC4;
  --gold:     #C89B3C;
  --red:      #ff5a5f;
  --blue:     #3d9bff;
}
html,body{height:100%;background:var(--bg);color:var(--text);font-family:'Share Tech Mono',system-ui,sans-serif;font-size:15px;line-height:1.5}

/* ── Layout ── */
.layout{display:flex;min-height:100vh}

/* ── Sidebar ── */
.sidebar{
  width:148px;min-width:148px;background:var(--sidebar);
  border-right:1px solid var(--line);display:flex;flex-direction:column;
  flex-shrink:0;position:sticky;top:0;height:100vh;overflow-y:auto;
}
.sb-brand{
  display:flex;align-items:center;gap:8px;
  padding:14px 12px 12px;border-bottom:1px solid var(--line);
}
.sb-brand svg{flex-shrink:0}
.sb-brand-name{font-family:'Rajdhani',sans-serif;font-size:16px;font-weight:700;letter-spacing:.06em;color:var(--text)}
.sb-xp{padding:10px 12px;border-bottom:1px solid var(--line)}
.sb-xp-label{font-size:11px;color:var(--text3);margin-bottom:4px}
.sb-xp-bar{height:4px;border-radius:2px;background:var(--line);overflow:hidden}
.sb-xp-fill{height:100%;width:0%;background:linear-gradient(90deg,var(--acc-fill),var(--accent));border-radius:2px}
.sb-section{padding:8px 0 2px;border-bottom:1px solid var(--line-soft)}
.sb-section-label{padding:6px 12px 2px;font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--text3)}
.sb-item{
  display:flex;align-items:center;gap:9px;
  padding:7px 12px;text-decoration:none;color:var(--text2);
  font-size:13px;font-weight:500;transition:background .12s,color .12s;
  border-left:2px solid transparent;
}
.sb-item:hover{background:var(--hover);color:var(--text)}
.sb-item.active{background:var(--acc-bg);border-left-color:var(--acc-fill);color:var(--accent)}
.sb-item svg{flex-shrink:0;opacity:.7}
.sb-item.active svg{opacity:1}
.sb-foot{margin-top:auto;border-top:1px solid var(--line);padding:10px 12px}
.sb-user{display:flex;align-items:center;gap:8px;font-size:12px;color:var(--text2)}
.sb-avatar{width:28px;height:28px;border-radius:50%;background:var(--acc-bg);border:1px solid var(--acc-bdr);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:var(--accent);flex-shrink:0}

/* ── Main content ── */
.main{
  flex:1;min-width:0;
  background:radial-gradient(1200px 520px at 70% -180px,rgba(12,34,63,.6),transparent 70%) no-repeat fixed,var(--bg);
}

/* ── Topbar (mobile) ── */
.topbar{display:none;align-items:center;gap:10px;padding:10px 16px;background:var(--sidebar);border-bottom:1px solid var(--line)}
.topbar-name{font-family:'Rajdhani',sans-serif;font-size:16px;font-weight:700;color:var(--text)}

/* ── Page padding ── */
.page{padding:2rem}

/* ── Hero ── */
.hero{
  background:var(--surface);border:1px solid var(--line);border-radius:12px;
  padding:2.5rem;margin-bottom:1.25rem;
  display:grid;grid-template-columns:1fr 280px;gap:2rem;align-items:start;
}
.hero-eyebrow{font-size:12px;color:var(--accent);font-weight:600;letter-spacing:.06em;margin-bottom:.5rem}
.hero-title{font-family:'Rajdhani',sans-serif;font-size:clamp(2.5rem,5vw,3.5rem);font-weight:700;line-height:.95;margin-bottom:.75rem}
.hero-desc{font-size:14px;color:var(--text2);max-width:380px;margin-bottom:1.5rem;line-height:1.6}
.hero-actions{display:flex;gap:.75rem;flex-wrap:wrap;align-items:center}
.btn{display:inline-flex;align-items:center;gap:6px;padding:.5rem 1.125rem;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;transition:opacity .12s,transform .1s;border:none}
.btn:active{transform:translateY(1px)}
.btn-primary{background:var(--acc-fill);color:#fff}
.btn-primary:hover{opacity:.88}
.btn-ghost{background:transparent;color:var(--accent);border:1px solid var(--acc-bdr)}
.btn-ghost:hover{background:var(--acc-bg)}

/* ── Queue panel ── */
.queue-panel{display:flex;flex-direction:column;gap:.5rem}
.queue-row{
  display:flex;align-items:center;justify-content:space-between;
  padding:.5rem .75rem;border-radius:6px;background:var(--bg);border:1px solid var(--line);
}
.queue-row-name{font-size:12px;color:var(--text2);font-weight:500}
.queue-dots{display:flex;gap:3px;align-items:center;flex:1;margin:0 .75rem}
.dot{width:14px;height:4px;border-radius:2px;background:var(--line)}
.dot.filled.purple{background:var(--acc-fill)}
.dot.filled.red   {background:var(--red)}
.queue-count{font-family:'Rajdhani',sans-serif;font-size:18px;font-weight:700;color:var(--text);white-space:nowrap}
.queue-count span{font-size:11px;font-weight:500;color:var(--text3)}

/* ── Grid de cards ── */
.cards-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.25rem}
.card{background:var(--surface);border:1px solid var(--line);border-radius:8px;padding:1rem}
.card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem}
.card-title{font-size:12px;font-weight:600;color:var(--text2);letter-spacing:.04em}
.card-link{font-size:11px;color:var(--accent);text-decoration:none;font-weight:500}
.card-link:hover{text-decoration:underline}
.live-dot{display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--mint);font-weight:500}
.live-dot::before{content:'';display:block;width:6px;height:6px;border-radius:50%;background:var(--mint);box-shadow:0 0 0 2px rgba(63,216,154,.25)}
.match-item{display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--line-soft)}
.match-item:last-child{border-bottom:none}
.match-id{font-size:13px;font-weight:600;color:var(--text)}
.match-meta{font-size:11px;color:var(--text3)}
.match-arrow{color:var(--text3);font-size:12px}
.empty-state{font-size:12px;color:var(--text3);padding:.5rem 0}

/* ── Upload card ── */
.upload-card{
  background:var(--surface);border:1px solid var(--line);border-radius:8px;
  padding:1rem;margin-bottom:1.25rem;
}
.upload-label{font-size:12px;font-weight:600;color:var(--text2);letter-spacing:.04em;margin-bottom:.75rem;display:block}
.upload-zone{
  border:1px dashed var(--line);border-radius:6px;padding:1.25rem;text-align:center;
  display:flex;flex-direction:column;align-items:center;gap:.5rem;
}
.upload-zone-text{font-size:12px;color:var(--text3)}
.upload-zone input[type=file]{display:none}
.upload-zone label{font-size:11px;color:var(--accent);cursor:pointer;font-weight:600}
.upload-zone label:hover{text-decoration:underline}
.upload-fname{font-size:11px;color:var(--text2);margin-top:.25rem;display:none}

/* ── Lane badges ── */
.badge{display:inline-flex;align-items:center;padding:2px 7px;border-radius:4px;font-size:10px;font-weight:700;letter-spacing:.06em}
.badge-top{background:rgba(255,155,92,.12);color:#ff9b5c}
.badge-jg {background:rgba(92,207,138,.12);color:#5ccf8a}
.badge-mid{background:rgba(79,209,217,.12);color:#4fd1d9}
.badge-bot{background:rgba(232,210,122,.12);color:#e8d27a}
.badge-sup{background:rgba(196,155,255,.12);color:#c49bff}

/* ── Leader rows ── */
.leader-row{display:flex;align-items:center;gap:.5rem;padding:.375rem 0;border-bottom:1px solid var(--line-soft)}
.leader-row:last-child{border-bottom:none}
.leader-pos{font-size:11px;color:var(--text3);width:16px;text-align:right;font-weight:600}
.leader-name{font-size:13px;font-weight:500;color:var(--text);flex:1}
.leader-pts{font-size:12px;color:var(--accent);font-weight:600}

/* ── Responsive ── */
@media(max-width:900px){
  .sidebar{display:none}
  .topbar{display:flex}
  .hero{grid-template-columns:1fr}
  .queue-panel{display:none}
  .cards-grid{grid-template-columns:1fr}
}
@media(max-width:600px){.page{padding:1rem}}
</style>
</head>
<body>
<div class="layout">

<!-- ── Sidebar ── -->
<nav class="sidebar" aria-label="Navegação principal">
  <div class="sb-brand">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
      <polygon points="12,1 23,12 12,23" fill="#8C1A1A"/>
      <polygon points="12,1 1,12 12,23" fill="#1A3A8C"/>
      <polygon points="12,1 23,12 12,23 1,12" fill="none" stroke="#C89B3C" stroke-width="1.5"/>
    </svg>
    <span class="sb-brand-name"><span style="color:#C89B3C">scrims</span><span style="color:#4ECDC4">.lol</span></span>
  </div>

  <div class="sb-xp">
    <div class="sb-xp-label">0 / 100 XP</div>
    <div class="sb-xp-bar"><div class="sb-xp-fill"></div></div>
  </div>

  <div class="sb-section">
    <a href="/index.php" class="sb-item active">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1L1 7v8h5v-5h4v5h5V7z"/></svg>
      Início
    </a>
    <a href="/fila.php" class="sb-item">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M8 5v3l2 2" stroke="currentColor" stroke-width="1.5"/></svg>
      Fila
    </a>
    <a href="/listas.php" class="sb-item">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="3" width="14" height="2" rx="1"/><rect x="1" y="7" width="14" height="2" rx="1"/><rect x="1" y="11" width="9" height="2" rx="1"/></svg>
      Listas
    </a>
    <a href="/ao-vivo.php" class="sb-item">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><circle cx="8" cy="8" r="5" stroke="currentColor" stroke-width="1.5" fill="none"/><circle cx="8" cy="8" r="2" fill="currentColor"/></svg>
      Ao vivo
    </a>
  </div>

  <div class="sb-section">
    <div class="sb-section-label">Comunidade</div>
    <a href="/app/roster.php" class="sb-item">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M8 8a3 3 0 100-6 3 3 0 000 6zm-5 6a5 5 0 0110 0H3z"/></svg>
      Jogadores
    </a>
    <a href="/ranking.php" class="sb-item">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M3 5h10v2H3zm0 4h10v2H3z"/></svg>
      Ranking
    </a>
    <a href="/times.php" class="sb-item">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M2 4h12v8H2z" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M6 4V2h4v2"/></svg>
      Times
    </a>
  </div>

  <div class="sb-section">
    <div class="sb-section-label">Conta</div>
    <a href="/admin/" class="sb-item">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M5 8h6M8 5v6" stroke="currentColor" stroke-width="1.5"/></svg>
      Admin
    </a>
  </div>

  <div class="sb-foot">
    <div class="sb-user">
      <div class="sb-avatar">M</div>
      <span>Michael</span>
    </div>
  </div>
</nav>

<!-- ── Main ── -->
<div class="main">
  <div class="topbar">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
      <polygon points="12,1 23,12 12,23" fill="#8C1A1A"/>
      <polygon points="12,1 1,12 12,23" fill="#1A3A8C"/>
      <polygon points="12,1 23,12 12,23 1,12" fill="none" stroke="#C89B3C" stroke-width="1.5"/>
    </svg>
    <span class="topbar-name"><span style="color:#C89B3C">scrims</span><span style="color:#4ECDC4">.lol</span></span>
  </div>

  <div class="page">

    <?php if (!empty($_SESSION['flash'])): ?>
    <div style="background:rgba(78,205,196,.10);border:1px solid rgba(78,205,196,.35);border-radius:6px;padding:.625rem 1rem;margin-bottom:1rem;font-size:13px;color:#4ECDC4">
      ✓ <?= $_SESSION['flash'] ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
    <?php elseif (!empty($_SESSION['flash_err'])): ?>
    <div style="background:rgba(255,90,95,.10);border:1px solid rgba(255,90,95,.35);border-radius:6px;padding:.625rem 1rem;margin-bottom:1rem;font-size:13px;color:#ff5a5f">
      <?= htmlspecialchars($_SESSION['flash_err']) ?>
    </div>
    <?php unset($_SESSION['flash_err']); ?>
    <?php endif; ?>

    <!-- Hero -->
    <div class="hero">
      <div>
        <div class="hero-eyebrow">Bem-vindo de volta, Michael</div>
        <h1 class="hero-title">Scrims.<br>No nível certo.</h1>
        <p class="hero-desc">Entre na fila, jogue com a comunidade e suba no ranking. Times balanceados por MMR, MVP a cada partida.</p>
        <div class="hero-actions">
          <a href="#" class="btn btn-primary">
            <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1v10M3 6l5 5 5-5"/></svg>
            Entrar na fila
          </a>
          <a href="#" class="btn btn-ghost">Ver listas</a>
        </div>
      </div>

      <!-- Queue panel -->
      <div class="queue-panel">
        <div class="queue-row">
          <span class="queue-row-name">Fila da Pro</span>
          <div class="queue-dots">
            <?php for($i=0;$i<10;$i++) echo '<div class="dot'.($i<8?' filled purple':'').'"></div>'; ?>
          </div>
          <span class="queue-count">8<span>/10</span></span>
        </div>
        <div class="queue-row">
          <span class="queue-row-name">Semi Pro</span>
          <div class="queue-dots">
            <?php for($i=0;$i<10;$i++) echo '<div class="dot"></div>'; ?>
          </div>
          <span class="queue-count">0<span>/10</span></span>
        </div>
        <div class="queue-row">
          <span class="queue-row-name">High</span>
          <div class="queue-dots">
            <?php for($i=0;$i<10;$i++) echo '<div class="dot'.($i<4?' filled red':'').'"></div>'; ?>
          </div>
          <span class="queue-count">4<span>/10</span></span>
        </div>
        <div class="queue-row">
          <span class="queue-row-name">Low</span>
          <div class="queue-dots">
            <?php for($i=0;$i<10;$i++) echo '<div class="dot'.($i<3?' filled red':'').'"></div>'; ?>
          </div>
          <span class="queue-count">3<span>/10</span></span>
        </div>
        <div class="queue-row">
          <span class="queue-row-name">Geral</span>
          <div class="queue-dots">
            <?php for($i=0;$i<10;$i++) echo '<div class="dot"></div>'; ?>
          </div>
          <span class="queue-count">0<span>/10</span></span>
        </div>

        <div style="margin-top:.5rem;padding:.75rem;background:var(--bg);border:1px solid var(--line);border-radius:6px;display:flex;align-items:center;justify-content:space-between">
          <div>
            <div style="font-size:11px;color:var(--text2);margin-bottom:2px">Partidas ao vivo</div>
            <div class="live-dot">Acontecendo agora</div>
          </div>
          <span style="font-family:'Rajdhani',sans-serif;font-size:2rem;font-weight:700;color:var(--text)">1</span>
        </div>
        <div style="padding:.75rem;background:var(--bg);border:1px solid var(--line);border-radius:6px">
          <div style="font-size:11px;color:var(--text2);margin-bottom:4px">Sua próxima lista</div>
          <div style="font-size:12px;color:var(--text3)">Nenhuma marcada</div>
        </div>
      </div>
    </div>

    <!-- Upload replay -->
    <div class="upload-card">
      <span class="upload-label">Enviar replay de partida</span>
      <form method="POST" action="/upload.php" enctype="multipart/form-data">
        <input type="hidden" name="PHP_SESSION_UPLOAD_PROGRESS" value="replay_upload">
        <div class="upload-zone">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#7a8d92" stroke-width="1.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          <span class="upload-zone-text">Arraste o arquivo .rofl aqui ou</span>
          <label for="replay-input">selecione do computador</label>
          <input id="replay-input" type="file" name="replay" accept=".rofl,.json"
                 onchange="document.querySelector('.upload-fname').style.display='block';document.querySelector('.upload-fname').textContent=this.files[0]?.name||''">
          <span class="upload-fname"></span>
          <button type="submit" class="btn btn-primary" style="margin-top:.25rem;font-size:12px;padding:.4rem .9rem">Enviar</button>
        </div>
      </form>
    </div>

    <!-- Cards grid -->
    <div class="cards-grid">

      <!-- Ao vivo -->
      <div class="card">
        <div class="card-head">
          <span class="card-title">AO VIVO</span>
          <a href="#" class="card-link">Ver todas</a>
        </div>
        <div class="match-item">
          <div>
            <div class="match-id">M00062</div>
            <div class="match-meta">Fila da High · MD1</div>
          </div>
          <span class="match-arrow">›</span>
        </div>
        <div class="match-item">
          <div>
            <div class="match-id">M00061</div>
            <div class="match-meta">Fila da Pro · MD1</div>
          </div>
          <span class="match-arrow">›</span>
        </div>
      </div>

      <!-- Próximas listas -->
      <div class="card">
        <div class="card-head">
          <span class="card-title">PRÓXIMAS LISTAS</span>
          <a href="#" class="card-link">Ver listas</a>
        </div>
        <div class="empty-state">Nenhuma lista agendada.<br>A fila está sempre aberta.</div>
      </div>

      <!-- Líderes -->
      <div class="card">
        <div class="card-head">
          <span class="card-title">LÍDERES</span>
          <span style="display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:600;color:var(--red);background:rgba(255,90,95,.10);padding:2px 6px;border-radius:4px">● Low</span>
        </div>
        <div class="leader-row">
          <span class="leader-pos">1</span>
          <span class="leader-name">NocturnalEdge</span>
          <span class="leader-pts">847 pts</span>
        </div>
        <div class="leader-row">
          <span class="leader-pos">2</span>
          <span class="leader-name">VoidWalker_BR</span>
          <span class="leader-pts">791 pts</span>
        </div>
        <div class="leader-row">
          <span class="leader-pos">3</span>
          <span class="leader-name">SilverBolt</span>
          <span class="leader-pts">734 pts</span>
        </div>
        <a href="#" class="card-link" style="display:block;margin-top:.5rem">Ver ranking completo</a>
      </div>

    </div><!-- /cards-grid -->

  </div><!-- /page -->
</div><!-- /main -->

</div><!-- /layout -->
</body>
</html>
