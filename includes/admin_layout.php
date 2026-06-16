<?php
$adminPage  = $adminPage  ?? 'dashboard';
$adminTitle = $adminTitle ?? 'Dashboard';
$user = currentUser();
$roleLabel = ['bendahara'=>'Bendahara Desa','sekdes'=>'Sekretaris Desa','kades'=>'Kepala Desa'];
$roleDisplay = $roleLabel[$user['role']] ?? $user['role'];
$avatar = strtoupper(substr($user['nama'], 0, 2));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($adminTitle) ?> — <?= APP_NAME ?> Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<style>
:root{--primary:#1a3a6b;--primary-light:#2451a3;--accent:#e63946;--success:#2a9d8f;--bg:#f0f4f8;--card:#fff;--text:#1a2332;--text-muted:#64748b;--border:#e2e8f0;--radius:14px;--shadow:0 2px 12px rgba(0,0,0,.06);--sw:244px}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
/* LAYOUT */
.aw{display:flex;min-height:100vh}
/* SIDEBAR */
.sidebar{width:var(--sw);background:var(--primary);color:#fff;display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100;overflow-y:auto}
.sb-brand{display:flex;align-items:center;gap:12px;padding:22px 18px 16px;border-bottom:1px solid rgba(255,255,255,.1)}
.logo-icon{width:34px;height:34px;background:var(--accent);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.95rem;flex-shrink:0}
.sb-name{font-family:'Plus Jakarta Sans',sans-serif;font-weight:800;font-size:.98rem}
.sb-sub{font-size:.63rem;color:rgba(255,255,255,.45)}
.sb-nav{flex:1;padding:14px 10px;display:flex;flex-direction:column;gap:3px}
.sb-item{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;cursor:pointer;font-size:.87rem;font-weight:500;color:rgba(255,255,255,.7);transition:all .2s;text-decoration:none;border:none;background:none;width:100%;text-align:left;font-family:inherit}
.sb-item:hover{background:rgba(255,255,255,.09);color:#fff}
.sb-item.active{background:rgba(255,255,255,.17);color:#fff;font-weight:700}
.sb-foot{padding:0 10px 20px}
/* MAIN */
.ac{margin-left:var(--sw);flex:1;display:flex;flex-direction:column;min-height:100vh}
.topbar{display:flex;align-items:center;justify-content:space-between;padding:16px 32px;background:#fff;border-bottom:1px solid var(--border);position:sticky;top:0;z-index:50}
.topbar h2{font-family:'Plus Jakarta Sans',sans-serif;font-size:1.1rem;font-weight:800}
.au{display:flex;align-items:center;gap:12px}
.au-name{font-size:.88rem;font-weight:600;text-align:right}
.au-role{font-size:.72rem;color:var(--text-muted);text-align:right}
.avatar{width:38px;height:38px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.85rem}
.pb{padding:28px 32px;flex:1}
.flash-w{padding:16px 32px 0}
/* DASH STATS */
.ds-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:28px}
.ds-card{background:#fff;border-radius:var(--radius);padding:22px 24px;border:1px solid var(--border);display:flex;align-items:center;gap:16px;box-shadow:var(--shadow)}
.ds-icon{width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0}
.ds-label{font-size:.77rem;color:var(--text-muted);margin-bottom:4px}
.ds-val{font-family:'Plus Jakarta Sans',sans-serif;font-size:1.35rem;font-weight:800}
/* CARD */
.card{background:var(--card);border-radius:var(--radius);border:1px solid var(--border);box-shadow:var(--shadow);padding:26px;margin-bottom:24px}
.card h3{font-family:'Plus Jakarta Sans',sans-serif;font-size:.9rem;font-weight:700;margin-bottom:18px;padding-bottom:12px;border-bottom:1px solid var(--border)}
/* TABLE */
.tw{overflow-x:auto}
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
.form-group label{display:block;font-size:.82rem;font-weight:600;margin-bottom:6px}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:10px;font-size:.9rem;font-family:inherit;outline:none;transition:border .2s;background:#fff}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:var(--primary-light)}
.form-group textarea{resize:vertical;min-height:80px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-grid .full{grid-column:1/-1}
.form-hint{font-size:.74rem;color:var(--text-muted);margin-top:5px}
.required{color:var(--accent)}
/* BTN */
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
/* VALIDASI BOX */
.val-box{background:#fef3c7;border:1px solid #fde68a;border-radius:12px;padding:18px 22px;margin-top:20px}
.val-box h4{font-size:.95rem;font-weight:700;margin-bottom:6px}
.val-box p{font-size:.84rem;color:var(--text-muted);margin-bottom:14px}
/* ACTION */
.action-btns{display:flex;gap:6px}
/* GRID */
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px}
/* FILTER */
.filter-bar{display:flex;gap:10px;margin-bottom:18px;align-items:center;flex-wrap:wrap}
.filter-bar select,.filter-bar input{padding:9px 14px;border:1.5px solid var(--border);border-radius:9px;font-size:.87rem;font-family:inherit;outline:none;background:#fff}
.ml-auto{margin-left:auto}
/* RESPONSIVE */
@media(max-width:900px){
  .sidebar{transform:translateX(-100%);transition:transform .3s}
  .sidebar.open{transform:translateX(0)}
  .ac{margin-left:0}
  .pb{padding:20px}
  .topbar{padding:14px 20px}
  .ds-grid,.grid-2{grid-template-columns:1fr}
  .form-grid{grid-template-columns:1fr}
}
</style>
<script>
function makeBarChart(id,labels,datasets){
  const ctx=document.getElementById(id);if(!ctx)return;
  return new Chart(ctx,{type:'bar',data:{labels,datasets},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}},scales:{y:{ticks:{callback:v=>'Rp '+(v/1e6).toFixed(0)+'jt'},grid:{color:'#f1f5f9'}},x:{grid:{display:false}}}}});
}
function makePieChart(id,labels,data,colors){
  const ctx=document.getElementById(id);if(!ctx)return;
  return new Chart(ctx,{type:'doughnut',data:{labels,datasets:[{data,backgroundColor:colors,borderWidth:2}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}}});
}
function makeLineChart(id,labels,data){
  const ctx=document.getElementById(id);if(!ctx)return;
  return new Chart(ctx,{type:'line',data:{labels,datasets:[{label:'Realisasi (jt)',data,borderColor:'#1a3a6b',backgroundColor:'rgba(26,58,107,.1)',borderWidth:2.5,pointBackgroundColor:'#1a3a6b',tension:.4,fill:true}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}},scales:{y:{ticks:{callback:v=>v+'jt'},grid:{color:'#f1f5f9'}},x:{grid:{display:false}}}}});
}
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('[data-confirm]').forEach(el=>{
    el.addEventListener('click',e=>{if(!confirm(el.dataset.confirm||'Yakin?'))e.preventDefault();});
  });
});
</script>
</head>
<body>
<div class="aw">
<aside class="sidebar">
  <div class="sb-brand">
    <div class="logo-icon">🏛️</div>
    <div><div class="sb-name"><?= APP_NAME ?></div><div class="sb-sub">Admin Panel</div></div>
  </div>
  <nav class="sb-nav">
    <a class="sb-item <?= $adminPage==='dashboard'?'active':'' ?>" href="<?= BASE_URL ?>/admin/index.php"><span>📊</span> Dashboard</a>
    <?php if(hasRole(['bendahara','sekdes'])): ?>
    <a class="sb-item <?= $adminPage==='kelola'?'active':'' ?>" href="<?= BASE_URL ?>/admin/kelola_anggaran.php"><span>📋</span> Kelola Anggaran</a>
    <?php endif; ?>
    <?php if(hasRole(['sekdes','kades','bendahara'])): ?>
    <a class="sb-item <?= $adminPage==='realisasi'?'active':'' ?>" href="<?= BASE_URL ?>/admin/realisasi_dana.php"><span>💸</span> Realisasi Dana</a>
    <?php endif; ?>
    <a class="sb-item <?= $adminPage==='laporan'?'active':'' ?>" href="<?= BASE_URL ?>/admin/laporan.php"><span>📄</span> Laporan Publik</a>
    <?php if(hasRole(['sekdes','kades'])): ?>
    <a class="sb-item <?= $adminPage==='grafik'?'active':'' ?>" href="<?= BASE_URL ?>/admin/grafik.php"><span>📈</span> Grafik</a>
    <?php endif; ?>
    <a class="sb-item <?= $adminPage==='pengaturan'?'active':'' ?>" href="<?= BASE_URL ?>/admin/pengaturan.php"><span>⚙️</span> Pengaturan</a>
  </nav>
  <div class="sb-foot">
    <a class="sb-item" href="<?= BASE_URL ?>/admin/logout.php"><span>🚪</span> Keluar</a>
  </div>
</aside>
<div class="ac">
  <header class="topbar">
    <h2><?= htmlspecialchars($adminTitle) ?></h2>
    <div class="au">
      <div><div class="au-name"><?= htmlspecialchars($user['nama']) ?></div><div class="au-role"><?= htmlspecialchars($roleDisplay) ?></div></div>
      <div class="avatar"><?= $avatar ?></div>
    </div>
  </header>
  <?php $flash=getFlash(); if(!empty($flash)): ?>
  <div class="flash-w"><div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div></div>
  <?php endif; ?>
  <div class="pb">
