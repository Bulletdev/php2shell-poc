<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Times | scrims.lol</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#070C14;--sidebar:rgba(15,24,35,.97);--surface:#0c1a2e;--line:rgba(200,155,60,.45);--line-soft:rgba(200,155,60,.12);--text:#e8e0d0;--text2:rgba(255,255,255,.7);--text3:rgba(200,155,60,.6);--accent:#C89B3C;--acc-fill:#C89B3C;--acc-bg:rgba(200,155,60,.08);--acc-bdr:rgba(200,155,60,.45);--mint:#4ECDC4}
html,body{height:100%;background:var(--bg);color:var(--text);font-family:'Share Tech Mono',system-ui,sans-serif;font-size:15px}
.layout{display:flex;min-height:100vh}
.sidebar{width:148px;min-width:148px;background:var(--sidebar);border-right:1px solid var(--line);display:flex;flex-direction:column;flex-shrink:0;position:sticky;top:0;height:100vh}
.sb-brand{display:flex;align-items:center;gap:8px;padding:14px 12px 12px;border-bottom:1px solid var(--line)}
.sb-brand-name{font-family:'Rajdhani',sans-serif;font-size:16px;font-weight:700;letter-spacing:.06em}
.sb-section{padding:8px 0 2px;border-bottom:1px solid var(--line-soft)}
.sb-section-label{padding:6px 12px 2px;font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--text3)}
.sb-item{display:flex;align-items:center;gap:9px;padding:7px 12px;text-decoration:none;color:var(--text2);font-size:13px;font-weight:500;border-left:2px solid transparent}
.sb-item:hover{background:rgba(200,155,60,.04);color:var(--text)}
.sb-item.active{background:var(--acc-bg);border-left-color:var(--acc-fill);color:var(--accent)}
.sb-foot{margin-top:auto;border-top:1px solid var(--line);padding:10px 12px}
.main{flex:1;min-width:0;background:radial-gradient(1200px 520px at 70% -180px,rgba(12,34,63,.6),transparent 70%) no-repeat fixed,var(--bg)}
.page{padding:2rem}
.page-title{font-family:'Rajdhani',sans-serif;font-size:1.75rem;font-weight:700;margin-bottom:1.5rem}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem}
.team-card{background:var(--surface);border:1px solid var(--line);border-radius:8px;padding:1rem}
.team-tag{font-family:'Rajdhani',sans-serif;font-size:1.1rem;font-weight:700;color:var(--accent);margin-bottom:.25rem}
.team-name{font-size:13px;color:var(--text2);margin-bottom:.5rem}
.team-meta{font-size:11px;color:var(--text3)}
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
    <a href="/index.php" class="sb-item"><svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1L1 7v8h5v-5h4v5h5V7z"/></svg>Início</a>
    <a href="/fila.php" class="sb-item"><svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M8 5v3l2 2" stroke="currentColor" stroke-width="1.5"/></svg>Fila</a>
    <a href="/listas.php" class="sb-item"><svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="3" width="14" height="2" rx="1"/><rect x="1" y="7" width="14" height="2" rx="1"/><rect x="1" y="11" width="9" height="2" rx="1"/></svg>Listas</a>
    <a href="/ao-vivo.php" class="sb-item"><svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><circle cx="8" cy="8" r="5" stroke="currentColor" stroke-width="1.5" fill="none"/><circle cx="8" cy="8" r="2" fill="currentColor"/></svg>Ao vivo</a>
  </div>
  <div class="sb-section">
    <div class="sb-section-label">Comunidade</div>
    <a href="/app/roster.php" class="sb-item"><svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M8 8a3 3 0 100-6 3 3 0 000 6zm-5 6a5 5 0 0110 0H3z"/></svg>Jogadores</a>
    <a href="/ranking.php" class="sb-item"><svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M3 5h10v2H3zm0 4h10v2H3z"/></svg>Ranking</a>
    <a href="/times.php" class="sb-item active"><svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M2 4h12v8H2z" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M6 4V2h4v2"/></svg>Times</a>
  </div>
  <div class="sb-section">
    <div class="sb-section-label">Conta</div>
    <a href="/admin/" class="sb-item"><svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M5 8h6M8 5v6" stroke="currentColor" stroke-width="1.5"/></svg>Admin</a>
  </div>
  <div class="sb-foot">
    <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:var(--text2)">
      <div style="width:28px;height:28px;border-radius:50%;background:var(--acc-bg);border:1px solid var(--acc-bdr);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:var(--accent)">M</div>
      <span>Michael</span>
    </div>
  </div>
</nav>
<div class="main">
  <div class="page">
    <h1 class="page-title">Times Cadastrados</h1>
    <div class="grid">
      <?php
      $times = [
        ['NVS','Nova Sombra','5 membros · High'],
        ['VXL','Vortex Legacy','5 membros · Semi Pro'],
        ['CB','CraqueBullet Clan','3 membros · Low'],
        ['PHX','Phoenix Esports','5 membros · Pro'],
        ['DKS','Dark Steel','4 membros · High'],
        ['ARC','ArcMage Squad','5 membros · Mid'],
      ];
      foreach ($times as [$tag,$name,$meta]):
      ?>
      <div class="team-card">
        <div class="team-tag">[<?= $tag ?>]</div>
        <div class="team-name"><?= htmlspecialchars($name) ?></div>
        <div class="team-meta"><?= $meta ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
</div>
</body>
</html>
