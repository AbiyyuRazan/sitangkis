<?php
// admin/grafik.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn()) redirect(BASE_URL . '/login.php');
if (!hasRole(['sekdes','kades'])) {
    setFlash('error','Akses ditolak.'); redirect(BASE_URL.'/admin/index.php');
}

$db = getDB();
$adminPage  = 'grafik';
$adminTitle = 'Grafik Visualisasi';
$year = (int)($_GET['tahun'] ?? date('Y'));

$barStmt = $db->prepare(
    "SELECT a.kategori, a.jumlah AS anggaran, COALESCE(SUM(r.jumlah),0) AS realisasi
     FROM anggaran a
     LEFT JOIN realisasi r ON r.kategori=a.kategori AND r.status='Selesai' AND YEAR(r.tanggal)=?
     WHERE a.tahun=? GROUP BY a.kategori, a.jumlah"
);
$barStmt->execute([$year,$year]);
$barData = $barStmt->fetchAll();

$pieRows = $db->prepare("SELECT sumber, jumlah FROM sumber_pendapatan WHERE tahun=?");
$pieRows->execute([$year]);
$pieData = $pieRows->fetchAll();

$lineStmt = $db->prepare(
    "SELECT MONTH(tanggal) AS bln, SUM(jumlah) AS total
     FROM realisasi WHERE YEAR(tanggal)=? AND status='Selesai'
     GROUP BY MONTH(tanggal) ORDER BY bln"
);
$lineStmt->execute([$year]);
$lineRaw = $lineStmt->fetchAll();
$lineData = array_fill(1, 12, 0);
foreach ($lineRaw as $row) $lineData[$row['bln']] = (int)$row['total'];

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
<title>Grafik Visualisasi — <?= APP_NAME ?> Admin</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Material+Icons+Round&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>

<style>
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
body {
  font-family: 'Plus Jakarta Sans', sans-serif;
  background-color: #FFF9F1; 
  color: #1E2229;
  min-height: 100vh;
  display: flex;
}

/* SIDEBAR RE-DESIGN */
.sidebar {
  width: 80px; 
  background: #FFAE34; 
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0; left: 0; bottom: 0;
  z-index: 100;
  box-shadow: 4px 0 20px rgba(243, 230, 211, 0.4);
  align-items: center; 
}
.sb-top {
  padding: 24px 0 16px;
  width: 100%;
  text-align: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}
.sb-logo-text { 
  font-weight: 800; 
  font-size: 0.85rem; 
  color: #1E2229;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}
.sb-nav { 
  flex: 1; 
  padding: 20px 0; 
  display: flex; 
  flex-direction: column; 
  gap: 8px; 
  width: 100%;
  align-items: center;
}
.sb-link {
  display: flex; 
  align-items: center; 
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 14px;
  color: rgba(30, 34, 41, 0.7); 
  text-decoration: none;
  transition: all .2s ease;
}
.sb-link .material-icons-round { font-size: 1.4rem; color: rgba(30, 34, 41, 0.75); }
.sb-link:hover { background: rgba(255, 255, 255, 0.2); color: #1E2229; }
.sb-link.active {
  background: #FF6B6B; 
  color: #FFFFFF;
  position: relative;
}
.sb-link.active .material-icons-round { color: #FFFFFF; }
.sb-link.active::after {
  content: '';
  position: absolute;
  right: -16px; top: 50%;
  transform: translateY(-50%);
  border-style: solid;
  border-width: 6px 6px 6px 0;
  border-color: transparent #FFF9F1 transparent transparent;
}
.sb-divider { width: 70%; height: 1px; background: rgba(255, 255, 255, 0.2); margin: 8px 0; }
.sb-foot { padding: 20px 0; width: 100%; display: flex; justify-content: center; }
.sb-avatar {
  width: 40px; height: 40px; border-radius: 50%;
  background: #FFFFFF; color: #1E2229;
  display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-size: .85rem;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

/* MAIN AREA ADJUSTMENT */
.main { margin-left: 80px; flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
.topbar {
  padding: 0 40px; height: 80px;
  display: flex; align-items: center; justify-content: space-between;
  background: transparent;
}
.topbar-left h2 { font-size: 2rem; font-weight: 800; color: #1E2229; letter-spacing: -0.03em; }
.topbar-left p { font-size: .78rem; color: #FF6B6B; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; margin-top: 2px; }
.topbar-right { display: flex; align-items: center; gap: 16px; }
.top-avatar {
  width: 40px; height: 40px; border-radius: 50%;
  background: #FFAE34; color: #1E2229;
  display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-size: .85rem;
}

/* BODY CONTAINER */
.body { padding: 12px 40px 40px 40px; flex: 1; }

/* FILTER DROPDOWN CONTAINER */
.filter-container { display: flex; justify-content: flex-end; margin-bottom: 24px; }
.filter-select {
  background: #FFFFFF; border: 1.5px solid #F3E6D3; padding: 10px 18px;
  border-radius: 10px; color: #1E2229; font-family: inherit; font-size: 0.88rem;
  font-weight: 700; outline: none; cursor: pointer; box-shadow: 0 2px 8px rgba(243, 230, 211, 0.3);
}
.filter-select:focus { border-color: #FFAE34; }

/* LAYOUT GRID STRUCTURE */
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 32px; }

/* COMPONENT CARD */
.card {
  background: #FFFFFF; border-radius: 20px;
  padding: 28px; border: none;
  box-shadow: 0 10px 30px rgba(243, 230, 211, 0.4);
}
.card h3 { font-size: 1.05rem; font-weight: 800; color: #1E2229; letter-spacing: -0.02em; margin-bottom: 24px; }

/* PROGRESS BAR SERAPAN */
.prog-item { margin-bottom: 20px; }
.prog-item:last-child { margin-bottom: 0; }
.prog-head { display: flex; justify-content: space-between; margin-bottom: 6px; }
.prog-label { font-size: .88rem; font-weight: 700; color: #1E2229; }
.prog-pct { font-size: .88rem; font-weight: 800; color: #FF6B6B; }
.prog-bar { height: 8px; background: #FFF9F0; border-radius: 20px; overflow: hidden; }
.prog-fill { height: 100%; border-radius: 20px; background: #FFAE34; }
.prog-amt { display: flex; justify-content: space-between; font-size: .75rem; color: #8A929A; margin-top: 4px; font-weight: 600; }

@media(max-width:1024px){
  .sidebar { transform: translateX(-100%); }
  .main { margin-left: 0; }
  .grid2 { grid-template-columns: 1fr; }
  .body { padding: 24px; }
}
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sb-top">
    <div class="sb-logo-text">STK</div>
  </div>
  <nav class="sb-nav">
    <a class="sb-link" href="<?= BASE_URL ?>/admin/index.php" title="Dashboard">
      <span class="material-icons-round">space_dashboard</span>
    </a>
    <?php if(hasRole(['bendahara','sekdes'])): ?>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/kelola_anggaran.php" title="Kelola Anggaran">
      <span class="material-icons-round">assignment</span>
    </a>
    <?php endif; ?>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/realisasi_dana.php" title="Realisasi Dana">
      <span class="material-icons-round">payments</span>
    </a>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/laporan.php" title="Laporan Publik">
      <span class="material-icons-round">description</span>
    </a>
    <?php if(hasRole(['sekdes','kades'])): ?>
    <a class="sb-link active" href="<?= BASE_URL ?>/admin/grafik.php" title="Grafik">
      <span class="material-icons-round">insert_chart</span>
    </a>
    <?php endif; ?>
    <div class="sb-divider"></div>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/pengaturan.php" title="Pengaturan">
      <span class="material-icons-round">settings</span>
    </a>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/logout.php" title="Keluar">
      <span class="material-icons-round">logout</span>
    </a>
  </nav>
  <div class="sb-foot">
    <div class="sb-avatar" title="<?= htmlspecialchars($user['nama']) ?> (<?= $roleDisplay ?>)"><?= $avatar ?></div>
  </div>
</aside>

<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <h2><?= $adminTitle ?></h2>
      <p>SITANGKIS — Visualisasi Grafik Makro Keuangan Desa</p>
    </div>
    <div class="topbar-right">
      <div class="top-avatar"><?= $avatar ?></div>
    </div>
  </header>

  <div class="body">

    <div class="filter-container">
      <form method="GET" style="display:flex; gap:8px; align-items:center">
        <select name="tahun" onchange="this.form.submit()" class="filter-select">
          <?php foreach ([2026,2025,2024] as $y): ?>
          <option value="<?= $y ?>" <?= $year==$y?'selected':'' ?>><?= $y ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>

    <div class="grid2">
      <div class="card">
        <h3>Anggaran vs Realisasi per Sektor</h3>
        <div style="height:280px; position: relative;"><canvas id="g-bar"></canvas></div>
      </div>
      
      <div class="card">
        <h3>Komposisi Sumber Pendapatan</h3>
        <div style="height:280px; position: relative;"><canvas id="g-pie"></canvas></div>
      </div>
      
      <div class="card">
        <h3>Tren Realisasi Bulanan <?= $year ?></h3>
        <div style="height:280px; position: relative;"><canvas id="g-line"></canvas></div>
      </div>
      
      <div class="card">
        <h3>Serapan Anggaran per Sektor</h3>
        <?php foreach ($barData as $c):
          $pct = $c['anggaran'] > 0 ? round(($c['realisasi']/$c['anggaran'])*100,1) : 0;
        ?>
        <div class="prog-item">
          <div class="prog-head">
            <span class="prog-label"><?= $c['kategori'] ?></span>
            <span class="prog-pct"><?= $pct ?>%</span>
          </div>
          <div class="prog-bar"><div class="prog-fill" style="width:<?= min($pct,100) ?>%"></div></div>
          <div class="prog-amt"><span><?= rupiah($c['realisasi']) ?></span><span><?= rupiah($c['anggaran']) ?></span></div>
        </div>
        <?php endforeach; ?>
        <?php if(empty($barData)): ?>
        <div style="text-align:center; padding:40px; color:#8A929A; font-weight:600;">Belum ada analisis serapan anggaran pada tahun <?= $year ?>.</div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<script>
const commonFont = { family: 'Plus Jakarta Sans', size: 11, weight: '600' };
const bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

// CHART 1 — BAR CHART
new Chart(document.getElementById('g-bar'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($barData, 'kategori')) ?>,
    datasets: [
      {
        label: 'Anggaran',
        data: <?= json_encode(array_map(fn($r)=>(int)$r['anggaran'], $barData)) ?>,
        backgroundColor: '#FFF3E5',
        borderColor: '#FFAE34',
        borderWidth: 1.5,
        barPercentage: 0.5,
        categoryPercentage: 0.8
      },
      {
        label: 'Realisasi',
        data: <?= json_encode(array_map(fn($r)=>(int)$r['realisasi'], $barData)) ?>,
        backgroundColor: '#FF6B6B',
        barPercentage: 0.5,
        categoryPercentage: 0.8
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { labels: { color: '#1E2229', font: commonFont }, position: 'bottom' } },
    scales: {
      y: { ticks: { color: '#8A929A', font: { family: 'Plus Jakarta Sans' }, callback: v => 'Rp ' + (v / 1e6) + 'jt' }, grid: { color: '#FFF9F0' } },
      x: { ticks: { color: '#1E2229', font: commonFont }, grid: { display: false } }
    }
  }
});

// CHART 2 — DOUGHNUT PIE CHART
new Chart(document.getElementById('g-pie'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_column($pieData, 'sumber')) ?>,
    datasets: [{
      data: <?= json_encode(array_map(fn($r)=>(int)$r['jumlah'], $pieData)) ?>,
      backgroundColor: ['#FFAE34', '#FF6B6B', '#2a9d8f', '#4a90e2'],
      borderWidth: 4,
      borderColor: '#ffffff'
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { labels: { color: '#1E2229', font: commonFont }, position: 'bottom' } },
    cutout: '70%'
  }
});

// CHART 3 — LINE CHART
new Chart(document.getElementById('g-line'), {
  type: 'line',
  data: {
    labels: bulan,
    datasets: [{
      label: 'Tren Realisasi',
      data: <?= json_encode(array_values($lineData)) ?>.map(v => Math.round(v / 1e6)),
      borderColor: '#FF6B6B',
      backgroundColor: '#FF6B6B',
      borderWidth: 3,
      tension: 0.3,
      pointBackgroundColor: '#FF6B6B'
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      y: { ticks: { color: '#8A929A', font: { family: 'Plus Jakarta Sans' }, callback: v => 'Rp ' + v + 'jt' }, grid: { color: '#FFF9F0' } },
      x: { ticks: { color: '#1E2229', font: commonFont }, grid: { display: false } }
    }
  }
});
</script>
</body>
</html>