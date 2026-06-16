<?php
$pageTitle  = $pageTitle  ?? APP_NAME;
$activePage = $activePage ?? 'home';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> — <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<style>
:root{--primary:#1a3a6b;--primary-light:#2451a3;--accent:#e63946;--success:#2a9d8f;--gold:#f4a261;--bg:#f0f4f8;--card:#fff;--text:#1a2332;--text-muted:#64748b;--border:#e2e8f0;--radius:14px;--shadow:0 2px 12px rgba(0,0,0,.06)}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;line-height:1.6}
/* NAVBAR */
.navbar{position:fixed;top:0;left:0;right:0;z-index:200;background:var(--primary);display:flex;align-items:center;justify-content:space-between;padding:0 40px;height:62px;box-shadow:0 2px 20px rgba(0,0,0,.18)}
.navbar-brand{display:flex;align-items:center;gap:12px;text-decoration:none;color:#fff;cursor:pointer}
.logo-icon{width:36px;height:36px;background:var(--accent);border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:1.05rem;flex-shrink:0}
.brand-name{font-family:'Plus Jakarta Sans',sans-serif;font-weight:800;font-size:1.15rem;display:block}
.brand-sub{font-size:.65rem;color:rgba(255,255,255,.5);display:block}
.navbar-links{display:flex;gap:6px;align-items:center}
.nav-link{color:rgba(255,255,255,.8);text-decoration:none;padding:7px 14px;border-radius:7px;font-size:.88rem;font-weight:500;transition:all .2s}
.nav-link:hover,.nav-link.active{color:#fff;background:rgba(255,255,255,.12)}
.btn-login{background:var(--accent);color:#fff;text-decoration:none;padding:8px 20px;border-radius:8px;font-size:.88rem;font-weight:600;transition:all .2s}
.btn-login:hover{background:#c1121f}
.hamburger{display:none;background:none;border:none;color:#fff;font-size:1.4rem;cursor:pointer;padding:4px 8px}
.mobile-menu{display:none;flex-direction:column;background:var(--primary);position:fixed;top:62px;left:0;right:0;z-index:190;padding:8px 0;box-shadow:0 8px 20px rgba(0,0,0,.2)}
.mobile-menu.open{display:flex}
.mobile-menu a{color:rgba(255,255,255,.85);text-decoration:none;padding:12px 28px;font-size:.92rem;font-weight:500;border-bottom:1px solid rgba(255,255,255,.08)}
.mobile-menu a:hover{background:rgba(255,255,255,.08);color:#fff}
/* PAGE */
.page-wrap{padding-top:62px}
/* HERO */
.hero{background:linear-gradient(135deg,var(--primary) 0%,var(--primary-light) 55%,#3b6fd4 100%);padding:56px 48px 40px;color:#fff}
.hero-tag{display:inline-block;background:rgba(255,255,255,.15);padding:4px 14px;border-radius:20px;font-size:.78rem;font-weight:600;margin-bottom:14px}
.hero h1{font-family:'Plus Jakarta Sans',sans-serif;font-size:2.3rem;font-weight:800;margin-bottom:8px}
.hero p{color:rgba(255,255,255,.75);font-size:1rem;margin-bottom:26px}
.hero-search{display:flex;gap:10px;max-width:580px}
.hero-search input{flex:1;padding:13px 18px;border-radius:10px;border:none;font-size:.95rem;outline:none;font-family:inherit}
.btn-search{background:var(--accent);color:#fff;border:none;padding:13px 24px;border-radius:10px;font-weight:600;cursor:pointer;font-size:.9rem;font-family:inherit;white-space:nowrap}
/* STATS */
.stats-bar{background:#fff;padding:24px 48px;display:grid;grid-template-columns:repeat(3,1fr);gap:20px;box-shadow:0 4px 20px rgba(0,0,0,.07)}
.stat-card{display:flex;align-items:center;gap:16px;padding:18px 20px;border-radius:var(--radius);background:var(--bg);border:1px solid var(--border)}
.stat-icon{width:50px;height:50px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0}
.si-blue{background:#dbeafe}.si-green{background:#d1fae5}.si-orange{background:#fef3c7}
.stat-label{font-size:.77rem;color:var(--text-muted);margin-bottom:3px}
.stat-value{font-family:'Plus Jakarta Sans',sans-serif;font-size:1.3rem;font-weight:800}
/* SECTION */
.section{padding:32px 48px}
.page-header{margin-bottom:28px}
.page-header h2{font-family:'Plus Jakarta Sans',sans-serif;font-size:1.6rem;font-weight:800;margin-bottom:4px}
.page-header p{color:var(--text-muted);font-size:.95rem}
/* GRID */
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px}
.grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
/* CARD */
.card{background:var(--card);border-radius:var(--radius);border:1px solid var(--border);box-shadow:var(--shadow);padding:26px}
.card h3{font-family:'Plus Jakarta Sans',sans-serif;font-size:.9rem;font-weight:700;margin-bottom:18px;padding-bottom:12px;border-bottom:1px solid var(--border)}
/* TABLE */
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse}
th{font-size:.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;padding:11px 14px;border-bottom:2px solid var(--border);text-align:left;white-space:nowrap}
td{padding:12px 14px;font-size:.88rem;border-bottom:1px solid var(--border)}
tr:last-child td{border-bottom:none}
tbody tr:hover td{background:#f8fafc}
/* BADGE */
.badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:.73rem;font-weight:600;white-space:nowrap}
.badge-success{background:#d1fae5;color:#065f46}
.badge-warning{background:#fef3c7;color:#92400e}
.badge-danger{background:#fee2e2;color:#991b1b}
.badge-info{background:#dbeafe;color:#1e40af}
/* FORM */
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:.82rem;font-weight:600;margin-bottom:6px;color:var(--text)}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:10px;font-size:.9rem;font-family:inherit;outline:none;transition:border .2s;background:#fff;color:var(--text)}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:var(--primary-light)}
.form-group textarea{resize:vertical;min-height:80px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-grid .full{grid-column:1/-1}
.form-hint{font-size:.74rem;color:var(--text-muted);margin-top:5px}
.required{color:var(--danger)}
/* BUTTONS */
.btn{display:inline-flex;align-items:center;gap:7px;padding:11px 24px;border-radius:10px;font-size:.88rem;font-weight:600;cursor:pointer;transition:all .2s;font-family:inherit;border:none;text-decoration:none}
.btn-primary{background:var(--primary);color:#fff}
.btn-primary:hover{background:var(--primary-light)}
.btn-success{background:var(--success);color:#fff}
.btn-success:hover{background:#21867a}
.btn-danger{background:#fee2e2;color:#991b1b}
.btn-danger:hover{background:#fecaca}
.btn-warning{background:#fef3c7;color:#92400e}
.btn-warning:hover{background:#fde68a}
.btn-sm{padding:6px 13px;font-size:.78rem;border-radius:7px}
.btn-block{width:100%;justify-content:center}
/* ALERT */
.alert{padding:12px 18px;border-radius:10px;font-size:.88rem;font-weight:500;margin-bottom:16px}
.alert-success{background:#d1fae5;color:#065f46}
.alert-error{background:#fee2e2;color:#991b1b}
.alert-warning{background:#fef3c7;color:#92400e}
.alert-info{background:#dbeafe;color:#1e40af}
/* PROGRESS */
.progress-item{margin-bottom:20px}
.progress-header{display:flex;justify-content:space-between;margin-bottom:7px}
.progress-label{font-weight:600;font-size:.88rem}
.progress-pct{font-weight:700;font-size:.88rem;color:var(--primary-light)}
.progress-bar{height:9px;background:#e2e8f0;border-radius:99px;overflow:hidden}
.progress-fill{height:100%;border-radius:99px;background:linear-gradient(90deg,var(--primary),var(--primary-light))}
.progress-amounts{display:flex;justify-content:space-between;font-size:.73rem;color:var(--text-muted);margin-top:4px}
/* FILTER */
.filter-bar{display:flex;gap:10px;margin-bottom:18px;align-items:center;flex-wrap:wrap}
.filter-bar select,.filter-bar input[type=text],.filter-bar input[type=search]{padding:9px 14px;border:1.5px solid var(--border);border-radius:9px;font-size:.87rem;font-family:inherit;outline:none;background:#fff}
.ml-auto{margin-left:auto}
/* CHIP */
.chip{display:inline-block;padding:4px 12px;border-radius:6px;font-size:.78rem;font-weight:600}
.chip-blue{background:#dbeafe;color:#1e40af}
.chip-green{background:#d1fae5;color:#065f46}
.chip-yellow{background:#fef3c7;color:#92400e}
.chip-purple{background:#f3e8ff;color:#6b21a8}
.chip-red{background:#fee2e2;color:#991b1b}
/* FOOTER */
.footer{background:var(--primary);color:rgba(255,255,255,.65);padding:28px 48px;margin-top:48px}
.footer-brand{display:flex;align-items:center;gap:10px;margin-bottom:6px;color:#fff;font-size:1rem}
.footer p{font-size:.82rem}
/* ACTION BTNS */
.action-btns{display:flex;gap:6px}
/* RESPONSIVE */
@media(max-width:768px){
  .navbar{padding:0 20px}
  .navbar-links{display:none}
  .hamburger{display:block}
  .hero{padding:40px 20px 28px}
  .hero h1{font-size:1.7rem}
  .stats-bar{grid-template-columns:1fr;padding:20px}
  .section{padding:20px}
  .grid-2,.grid-3{grid-template-columns:1fr}
  .form-grid{grid-template-columns:1fr}
  .footer{padding:24px 20px}
}
</style>
</head>
<body>
<nav class="navbar">
  <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
    <div class="logo-icon">🏛️</div>
    <div><span class="brand-name"><?= APP_NAME ?></span><span class="brand-sub"><?= APP_DESC ?></span></div>
  </a>
  <div class="navbar-links">
    <a class="nav-link <?= $activePage==='home'?'active':'' ?>" href="<?= BASE_URL ?>/index.php">Beranda</a>
    <a class="nav-link <?= $activePage==='laporan'?'active':'' ?>" href="<?= BASE_URL ?>/pages/laporan.php">Laporan</a>
    <a class="nav-link <?= $activePage==='grafik'?'active':'' ?>" href="<?= BASE_URL ?>/pages/grafik.php">Grafik</a>
    <a class="nav-link <?= $activePage==='tentang'?'active':'' ?>" href="<?= BASE_URL ?>/pages/tentang.php">Tentang</a>
    <?php if(isLoggedIn()): ?>
      <a class="btn-login" href="<?= BASE_URL ?>/admin/index.php">Dashboard Admin</a>
    <?php else: ?>
      <a class="btn-login" href="<?= BASE_URL ?>/login.php">Login Admin</a>
    <?php endif; ?>
  </div>
  <button class="hamburger" onclick="document.getElementById('mob').classList.toggle('open')">☰</button>
</nav>
<div class="mobile-menu" id="mob">
  <a href="<?= BASE_URL ?>/index.php">Beranda</a>
  <a href="<?= BASE_URL ?>/pages/laporan.php">Laporan</a>
  <a href="<?= BASE_URL ?>/pages/grafik.php">Grafik</a>
  <a href="<?= BASE_URL ?>/pages/tentang.php">Tentang</a>
  <a href="<?= BASE_URL ?>/login.php">Login Admin</a>
</div>
