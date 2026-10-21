<?php
// /admin/ — php handler (via .user.ini)
// Chain C read endpoint — rfc1867 decodes the php_serialize session with php handler → mismatch
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin | scrims.lol</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#070C14;--sidebar:rgba(15,24,35,.97);--surface:#0c1a2e;--elevated:#0f2040;--line:rgba(200,155,60,.45);--line-soft:rgba(200,155,60,.12);--text:#e8e0d0;--text2:rgba(255,255,255,.7);--text3:rgba(200,155,60,.6);--accent:#C89B3C;--acc-fill:#C89B3C;--acc-bg:rgba(200,155,60,.08);--acc-bdr:rgba(200,155,60,.45);--mint:#4ECDC4;--red:#ff5a5f;--gold:#C89B3C}
html,body{height:100%;background:var(--bg);color:var(--text);font-family:'Share Tech Mono',system-ui,sans-serif;font-size:15px}
.layout{display:flex;min-height:100vh}
.sidebar{width:148px;min-width:148px;background:var(--sidebar);border-right:1px solid var(--line);display:flex;flex-direction:column;flex-shrink:0;position:sticky;top:0;height:100vh}
.sb-brand{display:flex;align-items:center;gap:8px;padding:14px 12px 12px;border-bottom:1px solid var(--line)}
.sb-brand-name{font-family:'Rajdhani',sans-serif;font-size:16px;font-weight:700;letter-spacing:.06em}
.sb-item{display:flex;align-items:center;gap:9px;padding:7px 12px;text-decoration:none;color:var(--text2);font-size:13px;font-weight:500;border-left:2px solid transparent}
.sb-item:hover{background:rgba(30,45,53,.9);color:var(--text)}
.sb-item.active{background:var(--acc-bg);border-left-color:var(--acc-fill);color:var(--accent)}
.sb-section{padding:8px 0 2px;border-bottom:1px solid var(--line-soft)}
.sb-section-label{padding:6px 12px 2px;font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--text3)}
.sb-foot{margin-top:auto;border-top:1px solid var(--line);padding:10px 12px}
.main{flex:1;min-width:0;background:radial-gradient(1200px 520px at 70% -180px,rgba(12,34,63,.6),transparent 70%) no-repeat fixed,var(--bg)}
.page{padding:2rem}
.page-head{display:flex;align-items:center;gap:.75rem;margin-bottom:1.5rem}
.page-title{font-family:'Rajdhani',sans-serif;font-size:1.75rem;font-weight:700}
.admin-badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:4px;font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;background:rgba(230,190,106,.12);color:var(--gold);border:1px solid rgba(230,190,106,.35)}
.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem}
.stat-card{background:var(--surface);border:1px solid var(--line);border-radius:8px;padding:1rem}
.stat-label{font-size:11px;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;font-weight:600;margin-bottom:.375rem}
.stat-value{font-family:'Rajdhani',sans-serif;font-size:2rem;font-weight:700;line-height:1}
.stat-sub{font-size:11px;color:var(--text3);margin-top:.25rem}
.table-card{background:var(--surface);border:1px solid var(--line);border-radius:8px;overflow:hidden}
.table-head{padding:.75rem 1rem;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between}
.table-title{font-size:12px;font-weight:600;color:var(--text2);letter-spacing:.04em}
table{width:100%;border-collapse:collapse;font-size:13px}
thead th{text-align:left;padding:.5rem 1rem;font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--text3);border-bottom:1px solid var(--line)}
tbody tr{border-bottom:1px solid var(--line-soft)}
tbody tr:hover{background:var(--elevated)}
tbody td{padding:.5rem 1rem;color:var(--text2);vertical-align:middle}
.status{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:500;padding:2px 7px;border-radius:4px}
.status-ok{background:rgba(63,216,154,.10);color:var(--mint)}
.status-warn{background:rgba(255,181,71,.10);color:#ffb547}
.status-err{background:rgba(255,90,95,.10);color:var(--red)}
/* Hidden upload form — multipart POST here triggers rfc1867 with php handler */
.upload-hidden{display:none}
</style>
</head>
<body>
<div class="layout">
<nav class="sidebar">
  <div class="sb-brand">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><polygon points="12,1 23,12 12,23" fill="#8C1A1A"/><polygon points="12,1 1,12 12,23" fill="#1A3A8C"/><polygon points="12,1 23,12 12,23 1,12" fill="none" stroke="#C89B3C" stroke-width="1.5"/></svg>
    <span class="sb-brand-name"><span style="color:#C89B3C">scrims</span><span style="color:#4ECDC4">.lol</span></span>
  </div>
  <div class="sb-section">
    <a href="/index.php" class="sb-item">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1L1 7v8h5v-5h4v5h5V7z"/></svg>Início</a>
    <a href="/app/roster.php" class="sb-item">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M8 8a3 3 0 100-6 3 3 0 000 6zm-5 6a5 5 0 0110 0H3z"/></svg>Jogadores</a>
  </div>
  <div class="sb-section">
    <div class="sb-section-label">Conta</div>
    <a href="/admin/" class="sb-item active">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M5 8h6M8 5v6" stroke="currentColor" stroke-width="1.5"/></svg>Admin</a>
  </div>
  <div class="sb-foot">
    <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:var(--text2)">
      <div style="width:28px;height:28px;border-radius:50%;background:rgba(230,190,106,.12);border:1px solid rgba(230,190,106,.35);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:var(--gold)">M</div>
      <span>Michael</span>
    </div>
  </div>
</nav>

<div class="main">
  <div class="page">

    <div class="page-head">
      <h1 class="page-title">Painel Admin</h1>
      <span class="admin-badge">ADMIN</span>
    </div>

    <div class="grid">
      <div class="stat-card">
        <div class="stat-label">Jogadores ativos</div>
        <div class="stat-value" style="color:var(--accent)">415</div>
        <div class="stat-sub">+12 nesta semana</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Partidas hoje</div>
        <div class="stat-value" style="color:var(--mint)">38</div>
        <div class="stat-sub">3 ao vivo agora</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Uptime</div>
        <div class="stat-value" style="color:var(--gold)">99.8<span style="font-size:1rem">%</span></div>
        <div class="stat-sub">PHP <?=PHP_VERSION?></div>
      </div>
    </div>

    <div class="table-card">
      <div class="table-head">
        <span class="table-title">RELATÓRIO DE SESSÕES</span>
        <span style="font-size:11px;color:var(--text3)">handler: <?=ini_get('session.serialize_handler')?></span>
      </div>
      <table>
        <thead>
          <tr><th>SID (parcial)</th><th>IP</th><th>Última atividade</th><th>Status</th></tr>
        </thead>
        <tbody>
          <tr>
            <td style="color:var(--text);font-family:monospace;font-size:12px">a8f3…c91d</td>
            <td>177.92.13.44</td><td>há 2 min</td>
            <td><span class="status status-ok">● Ativa</span></td>
          </tr>
          <tr>
            <td style="color:var(--text);font-family:monospace;font-size:12px">7bc1…e02a</td>
            <td>189.55.207.18</td><td>há 8 min</td>
            <td><span class="status status-ok">● Ativa</span></td>
          </tr>
          <tr>
            <td style="color:var(--text);font-family:monospace;font-size:12px">f291…3b7c</td>
            <td>200.148.9.51</td><td>há 23 min</td>
            <td><span class="status status-warn">● Idle</span></td>
          </tr>
          <tr>
            <td style="color:var(--text);font-family:monospace;font-size:12px">2d4e…88f1</td>
            <td>201.17.64.120</td><td>há 1h</td>
            <td><span class="status status-err">● Expirada</span></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Hidden upload form — multipart POST here triggers rfc1867 with php handler -->
    <form class="upload-hidden" method="POST" action="/admin/index.php" enctype="multipart/form-data">
      <input type="hidden" name="PHP_SESSION_UPLOAD_PROGRESS" value="admin_upload">
      <input type="file" name="f">
    </form>

  </div>
</div>

</div>
</body>
</html>
