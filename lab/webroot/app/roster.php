<?php
// /app/ — php_serialize handler (via .user.ini)
// Chain C write endpoint
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Jogadores | scrims.lol</title>
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
.sb-item{display:flex;align-items:center;gap:9px;padding:7px 12px;text-decoration:none;color:var(--text2);font-size:13px;font-weight:500;border-left:2px solid transparent}
.sb-item:hover{background:rgba(30,45,53,.9);color:var(--text)}
.sb-item.active{background:var(--acc-bg);border-left-color:var(--acc-fill);color:var(--accent)}
.sb-section{padding:8px 0 2px;border-bottom:1px solid var(--line-soft)}
.sb-section-label{padding:6px 12px 2px;font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--text3)}
.sb-foot{margin-top:auto;border-top:1px solid var(--line);padding:10px 12px}
.sb-avatar{width:28px;height:28px;border-radius:50%;background:var(--acc-bg);border:1px solid var(--acc-bdr);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:var(--accent);flex-shrink:0}
.main{flex:1;min-width:0;background:radial-gradient(1200px 520px at 70% -180px,rgba(12,34,63,.6),transparent 70%) no-repeat fixed,var(--bg)}
.page{padding:2rem}
.page-head{margin-bottom:1.5rem}
.page-title{font-family:'Rajdhani',sans-serif;font-size:1.75rem;font-weight:700;margin-bottom:.25rem}
.page-subtitle{font-size:13px;color:var(--text2)}
.filters{display:flex;gap:.5rem;margin-bottom:1rem;flex-wrap:wrap}
.filter-btn{padding:.3rem .75rem;border-radius:6px;font-size:12px;font-weight:500;border:1px solid var(--line);background:var(--surface);color:var(--text2);cursor:pointer}
.filter-btn.active,.filter-btn:hover{border-color:var(--acc-bdr);background:var(--acc-bg);color:var(--accent)}
.search-box{flex:1;max-width:240px;padding:.3rem .75rem;border-radius:6px;border:1px solid var(--line);background:var(--surface);color:var(--text);font-size:12px;outline:none}
.search-box:focus{border-color:var(--acc-bdr)}
table{width:100%;border-collapse:collapse;font-size:13px}
thead th{text-align:left;padding:.5rem .75rem;font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--text3);border-bottom:1px solid var(--line)}
tbody tr{border-bottom:1px solid var(--line-soft);transition:background .1s}
tbody tr:hover{background:var(--surface)}
tbody td{padding:.625rem .75rem;color:var(--text2);vertical-align:middle}
tbody td:first-child{color:var(--text);font-weight:500}
.badge{display:inline-flex;align-items:center;padding:2px 7px;border-radius:4px;font-size:10px;font-weight:700;letter-spacing:.06em}
.badge-top{background:rgba(255,155,92,.12);color:#ff9b5c}
.badge-jg {background:rgba(92,207,138,.12);color:#5ccf8a}
.badge-mid{background:rgba(79,209,217,.12);color:#4fd1d9}
.badge-bot{background:rgba(232,210,122,.12);color:#e8d27a}
.badge-sup{background:rgba(196,155,255,.12);color:#c49bff}
.tier{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:6px;font-family:'Rajdhani',sans-serif;font-size:13px;font-weight:700;border:1px solid var(--acc-bdr);background:var(--acc-bg);color:var(--accent)}
.pts{color:var(--accent);font-weight:600}
.upload-form{display:none}
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
    <a href="/fila.php" class="sb-item">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>Fila</a>
    <a href="/ao-vivo.php" class="sb-item">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><circle cx="8" cy="8" r="5" stroke="currentColor" stroke-width="1.5" fill="none"/><circle cx="8" cy="8" r="2" fill="currentColor"/></svg>Ao vivo</a>
  </div>
  <div class="sb-section">
    <div class="sb-section-label">Comunidade</div>
    <a href="/app/roster.php" class="sb-item active">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M8 8a3 3 0 100-6 3 3 0 000 6zm-5 6a5 5 0 0110 0H3z"/></svg>Jogadores</a>
    <a href="/ranking.php" class="sb-item">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M3 5h10v2H3zm0 4h10v2H3z"/></svg>Ranking</a>
  </div>
  <div class="sb-foot">
    <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:var(--text2)">
      <div class="sb-avatar">M</div><span>Michael</span>
    </div>
  </div>
</nav>

<div class="main">
  <div class="page">
    <div class="page-head">
      <h1 class="page-title">Jogadores</h1>
      <p class="page-subtitle">415 participantes · Temporada 5</p>
    </div>

    <div class="filters">
      <button class="filter-btn active">Todos</button>
      <button class="filter-btn">TOP</button>
      <button class="filter-btn">JG</button>
      <button class="filter-btn">MID</button>
      <button class="filter-btn">BOT</button>
      <button class="filter-btn">SUP</button>
      <input class="search-box" type="text" placeholder="Buscar jogador...">
    </div>

    <!-- Hidden upload form — multipart POST to this endpoint triggers rfc1867 with php_serialize handler -->
    <form class="upload-form" method="POST" action="/app/roster.php" enctype="multipart/form-data">
      <input type="hidden" name="PHP_SESSION_UPLOAD_PROGRESS" value="roster_upload">
      <input type="file" name="f">
    </form>

    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Jogador</th>
          <th>Lane</th>
          <th>Tier</th>
          <th>Partidas</th>
          <th>Pontos</th>
          <th>W/L</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $players = [
          ['NocturnalEdge','top','S',142,'W',847],['VoidWalker_BR','jg','A+',138,'W',791],
          ['SilverBolt','mid','A',131,'L',734],['CraqueBullet','sup','A',129,'W',718],
          ['PhantomADC','bot','A-',125,'W',705],['StormRider99','jg','B+',120,'L',688],
          ['IronFist_SP','top','B+',118,'W',671],['ArcaneWitch','mid','B',115,'L',654],
          ['ShadowStep','bot','B',112,'W',637],['HexCore_BR','sup','B',109,'L',620],
        ];
        $lane_class=['top'=>'badge-top','jg'=>'badge-jg','mid'=>'badge-mid','bot'=>'badge-bot','sup'=>'badge-sup'];
        $lane_label=['top'=>'TOP','jg'=>'JG','mid'=>'MID','bot'=>'BOT','sup'=>'SUP'];
        foreach($players as $i=>[$name,$lane,$tier,$games,$wl,$pts]):
          $wl_color=$wl==='W'?'#3fd89a':'#ff5a5f';
        ?>
        <tr>
          <td style="color:var(--text3);font-size:12px"><?=$i+1?></td>
          <td><?=htmlspecialchars($name)?></td>
          <td><span class="badge <?=$lane_class[$lane]?>"><?=$lane_label[$lane]?></span></td>
          <td><span class="tier"><?=$tier?></span></td>
          <td><?=$games?></td>
          <td class="pts"><?=$pts?></td>
          <td style="color:<?=$wl_color?>;font-weight:600"><?=$wl?></td>
        </tr>
        <?php endforeach?>
      </tbody>
    </table>
  </div>
</div>

</div>
</body>
</html>
