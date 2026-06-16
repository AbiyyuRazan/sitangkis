<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
if (!isLoggedIn()) redirect(BASE_URL . '/login.php');

$adminPage  = 'dashboard';
$adminTitle = 'Dashboard';
$db   = getDB();
$year = date('Y');

// SINKRONISASI MAKRO KAS: Total dana masuk hanya membaca Pagu yang sudah disetujui Kades
$totalAnggaran  = (int)$db->query("SELECT SUM(jumlah) FROM anggaran WHERE tahun=$year AND is_validasi=1")->fetchColumn();
$totalRealisasi = (int)$db->query("SELECT SUM(jumlah) FROM realisasi WHERE YEAR(tanggal)=$year AND status='Selesai'")->fetchColumn();
$serapan        = $totalAnggaran > 0 ? round(($totalRealisasi / $totalAnggaran) * 100, 1) : 0;
$totalTx        = (int)$db->query("SELECT COUNT(*) FROM realisasi WHERE YEAR(tanggal)=$year")->fetchColumn();
$menunggu       = (int)$db->query("SELECT COUNT(*) FROM realisasi WHERE status='Proses' AND divalidasi_oleh IS NULL")->fetchColumn();

$recent = $db->query(
  "SELECT r.*, u.nama AS nama_user FROM realisasi r
   LEFT JOIN users u ON u.id=r.dibuat_oleh
   ORDER BY r.created_at DESC LIMIT 6"
)->fetchAll();

$barData = [];
try {
    // FIXED BUG DOUBLE KATEGORI: Menggunakan SUM(a.jumlah) dan GROUP BY murni pada a.kategori
    $barStmt = $db->prepare(
      "SELECT a.kategori, SUM(a.jumlah) AS anggaran, 
       COALESCE((SELECT SUM(r.jumlah) FROM realisasi r WHERE r.kategori = a.kategori AND r.status = 'Selesai' AND YEAR(r.tanggal) = ?), 0) AS realisasi
       FROM anggaran a
       WHERE a.tahun=? GROUP BY a.kategori"
    );
    $barStmt->execute([$year,$year]);
    $barData = $barStmt->fetchAll();
} catch (Exception $e) {
    $barData = [];
}

// SINKRONISASI GRAFIK LINGKARAN: Mengambil data nama program (Sumber Dana) dinamis dari alokasi dana masuk yang valid
$pieData = [];
try {
    $pieRows = $db->prepare("SELECT nama_program AS sumber, jumlah FROM anggaran WHERE tahun=? AND is_validasi=1");
    $pieRows->execute([$year]);
    $pieData = $pieRows->fetchAll();
} catch (Exception $e) {
    $pieData = [];
}

// Menghitung jumlah pagu masuk yang baru diajukan bendahara dan belum divalidasi kades
$menungguPagu = (int)$db->query("SELECT COUNT(*) FROM anggaran WHERE is_validasi=0")->fetchColumn();

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
<title>Dashboard — <?= APP_NAME ?> Admin</title>

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

.notif-btn {
  width: 40px; height: 40px; border-radius: 12px;
  background: #FFFFFF; border: none; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  color: #1E2229; position: relative;
  box-shadow: 0 4px 12px rgba(243, 230, 211, 0.3);
}
.notif-dot {
  width: 8px; height: 8px; background: #FF6B6B;
  border-radius: 50%; position: absolute; top: 10px; right: 10px;
  border: 2px solid #fff;
}
.top-avatar {
  width: 40px; height: 40px; border-radius: 50%;
  background: #FFAE34; color: #1E2229;
  display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-size: .85rem;
}

/* BODY CONTAINER */
.body { padding: 12px 40px 40px 40px; flex: 1; }

/* STAT CARDS */
.stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 32px; }
.stat-card {
  background: #FFFFFF; border-radius: 20px;
  padding: 24px 28px; border: none;
  display: flex; align-items: center; justify-content: space-between;
  box-shadow: 0 10px 30px rgba(243, 230, 211, 0.4);
  transition: transform 0.2s;
}
.stat-card:hover { transform: translateY(-3px); }
.stat-label { font-size: .78rem; color: #8A929A; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 6px; }
.stat-val { font-size: 1.6rem; font-weight: 800; color: #1E2229; line-height: 1; letter-spacing: -0.02em; }
.stat-icon-wrap {
  width: 48px; height: 48px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.si-blue { background: #FFF3E5; color: #FFAE34; }
.si-green { background: #E6F7F4; color: #2a9d8f; }
.si-orange { background: #FFF0F0; color: #FF6B6B; }

/* ALERT VALIDASI KADES */
.alert-validasi {
  background: #FFF0F0; border: 1px solid #FFE1E1;
  border-radius: 16px; padding: 16px 24px; margin-bottom: 32px;
  display: flex; align-items: center; gap: 16px;
}
.av-text { flex: 1; }
.av-title { font-weight: 700; font-size: .92rem; color: #E85555; }
.av-sub { font-size: .8rem; color: #8A929A; margin-top: 2px; font-weight: 500; margin-bottom: 12px; }
.alert-validasi a {
  background: #FF6B6B; color: #fff; padding: 10px 20px;
  border-radius: 10px; font-size: .8rem; font-weight: 700;
  text-decoration: none; flex-shrink: 0; transition: background 0.2s;
}

/* LAYOUT STRUCTURE GRID */
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 32px; }

/* KARTU KOMPONEN */
.card {
  background: #FFFFFF; border-radius: 20px;
  padding: 28px; border: none;
  box-shadow: 0 10px 30px rgba(243, 230, 211, 0.4);
}
.card-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.card-title { font-size: 1.05rem; font-weight: 800; color: #1E2229; letter-spacing: -0.02em; }
.card-link { font-size: .82rem; color: #FF6B6B; text-decoration: none; font-weight: 700; }

/* MINIMALIST DATA TABLE */
.tw { overflow-x: auto; }
table { width: 100%; border-collapse: separate; border-spacing: 0 6px; }
th {
  font-size: .75rem; font-weight: 700; color: #8A929A;
  text-transform: uppercase; letter-spacing: .05em;
  padding: 10px 14px; text-align: left;
}
td { padding: 16px 14px; font-size: .88rem; background-color: #FFFDF9; color: #1E2229; }
td:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
td:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

/* STATUS BADGE */
.badge { display: inline-block; padding: 4px 12px; border-radius: 10px; font-size: .75rem; font-weight: 700; }
.bs { background: #E6F7F4; color: #2a9d8f; } 
.bw { background: #FFF3E5; color: #FFAE34; } 
.bd { background: #FFF0F0; color: #FF6B6B; } 

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
  .stat-grid,.grid2 { grid-template-columns: 1fr; }
  .body { padding: 24px; }
}
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sb-top"><div class="sb-logo-text">STK</div></div>
  <nav class="sb-nav">
    <a class="sb-link active" href="<?= BASE_URL ?>/admin/index.php" title="Dashboard"><span class="material-icons-round">space_dashboard</span></a>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/kelola_anggaran.php" title="Kelola Anggaran"><span class="material-icons-round">assignment</span></a>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/realisasi_dana.php" title="Realisasi Dana"><span class="material-icons-round">payments</span></a>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/laporan.php" title="Laporan Publik"><span class="material-icons-round">description</span></a>
    <?php if(hasRole(['sekdes','kades'])): ?>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/grafik.php" title="Grafik"><span class="material-icons-round">insert_chart</span></a>
    <?php endif; ?>
    <div class="sb-divider"></div>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/pengaturan.php" title="Pengaturan"><span class="material-icons-round">settings</span></a>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/logout.php" title="Keluar"><span class="material-icons-round">logout</span></a>
  </nav>
  <div class="sb-foot"><div class="sb-avatar"><?= $avatar ?></div></div>
</aside>

<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <h2>Sitangkis Dashboard</h2>
      <p>Selamat datang, <?= htmlspecialchars($user['nama']) ?> — <?= date('l, d F Y') ?></p>
    </div>
    <div class="topbar-right">
      <?php if(($menunggu > 0 || $menungguPagu > 0) && hasRole('kades')): ?>
      <button class="notif-btn" title="Ada transaksi/pagu membutuhkan validasi">
        <span class="material-icons-round">notifications</span><span class="notif-dot"></span>
      </button>
      <?php endif; ?>
      <div class="top-avatar"><?= $avatar ?></div>
    </div>
  </header>

  <div class="body">

    <div class="stat-grid">
      <div class="stat-card">
        <div class="stat-info">
          <div class="stat-label">Total Dana Masuk (Disetujui) <?= $year ?></div>
          <div class="stat-val"><?= rupiah($totalAnggaran) ?></div>
        </div>
        <div class="stat-icon-wrap si-blue"><span class="material-icons-round">business_center</span></div>
      </div>
      <div class="stat-card">
        <div class="stat-info">
          <div class="stat-label">Total Realisasi Keluar</div>
          <div class="stat-val"><?= rupiah($totalRealisasi) ?></div>
        </div>
        <div class="stat-icon-wrap si-green"><span class="material-icons-round">trending_up</span></div>
      </div>
      <div class="stat-card">
        <div class="stat-info">
          <div class="stat-label">Efisiensi Serapan Dana</div>
          <div class="stat-val"><?= $serapan ?>%</div>
        </div>
        <div class="stat-icon-wrap si-orange"><span class="material-icons-round">track_changes</span></div>
      </div>
    </div>

    <?php if(hasRole('kades')): ?>
      <?php if($menungguPagu > 0): ?>
      <div class="alert-validasi" style="background: #E6F7F4; border-color: #CEF3EC; margin-bottom:16px;">
        <div class="av-icon"><span class="material-icons-round" style="color: #2a9d8f">gavel</span></div>
        <div class="av-text">
          <div class="av-title" style="color:#2a9d8f"><?= $menungguPagu ?> Pagu Dana Masuk Menunggu Validasi</div>
          <div class="av-sub">Tinjau rancangan plafon pendapatan desa sebelum dikunci ke sistem keuangan.</div>
        </div>
        <a href="<?= BASE_URL ?>/admin/kelola_anggaran.php" style="background:#2a9d8f">Tinjau Pagu</a>
      </div>
      <?php endif; ?>

      <?php if($menunggu > 0): ?>
      <div class="alert-validasi">
        <div class="av-icon"><span class="material-icons-round" style="color: #FF6B6B">warning</span></div>
        <div class="av-text">
          <div class="av-title"><?= $menunggu ?> Nota Realisasi Belanja Menunggu Validasi Anda</div>
          <div class="av-sub">Silakan validasi bukti pengeluaran agar dapat didistribusikan ke laporan publik.</div>
        </div>
        <a href="<?= BASE_URL ?>/admin/realisasi_dana.php">Tinjau Belanja</a>
      </div>
      <?php endif; ?>
    <?php endif; ?>

    <div class="grid2">
      <div class="card">
        <div class="card-head"><span class="card-title">Grafik Komparasi Dana Sektor (Masuk vs Belanja)</span></div>
        <div style="height:240px; position: relative;"><canvas id="barChart"></canvas></div>
      </div>
      <div class="card">
        <div class="card-head"><span class="card-title">Komposisi Makro Sumber Pendapatan</span></div>
        <div style="height:240px; position: relative;"><canvas id="pieChart"></canvas></div>
      </div>
    </div>

    <div class="grid2">
      <div class="card">
        <div class="card-head"><span class="card-title">Progress Penyerapan Saldo Sektor</span></div>
        <?php foreach($barData as $c):
          $pct = $c['anggaran']>0 ? round(($c['realisasi']/$c['anggaran'])*100,1) : 0;
        ?>
        <div class="prog-item">
          <div class="prog-head">
            <span class="prog-label"><?= $c['kategori'] ?></span>
            <span class="prog-pct"><?= $pct ?>%</span>
          </div>
          <div class="prog-bar"><div class="prog-fill" style="width:<?= min($pct,100) ?>%"></div></div>
          <div class="prog-amt"><span>Keluar: <?= rupiah($c['realisasi']) ?></span><span>Pagu: <?= rupiah($c['anggaran']) ?></span></div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="card">
        <div class="card-head">
          <span class="card-title">Daftar Pengeluaran Belanja Terbaru</span>
          <a class="card-link" href="<?= BASE_URL ?>/admin/realisasi_dana.php">Lihat semua →</a>
        </div>
        <div class="tw">
          <table>
            <thead><tr><th>Kegiatan Belanja</th><th>Jumlah</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach($recent as $r):
                $bc=['Selesai'=>'bs','Proses'=>'bw','Batal'=>'bd'];
              ?>
              <tr>
                <td><strong><?= htmlspecialchars(mb_strimwidth($r['nama_kegiatan'],0,28,'…')) ?></strong></td>
                <td><?= rupiah($r['jumlah']) ?></td>
                <td><span class="badge <?= $bc[$r['status']]??'bi' ?>"><?= $r['status'] ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div></div>
<script>
const commonFont = { family: 'Plus Jakarta Sans', size: 11, weight: '600' };

new Chart(document.getElementById('barChart'),{
  type:'bar',
  data:{
    labels:<?= json_encode(array_column($barData,'kategori')) ?>,
    datasets:[
      {
        label:'Dana Masuk (Pagu)',
        data:<?= json_encode(array_map(fn($r)=>(int)$r['anggaran'], $barData)) ?>,
        backgroundColor:'#FFF3E5', 
        borderColor: '#FFAE34',   
        borderWidth: 1.5,
        barPercentage: 0.5,
        categoryPercentage: 0.8,
      },
      {
        label:'Uang Keluar (Realisasi)',
        data:<?= json_encode(array_map(fn($r)=>(int)$r['realisasi'],$barData)) ?>,
        backgroundColor:'#FF6B6B', 
        barPercentage: 0.5,
        categoryPercentage: 0.8,
      }
    ]
  },
  options:{
    responsive:true,
    maintainAspectRatio:false,
    plugins:{ legend:{ labels:{ color:'#1E2229', font:commonFont }, position:'bottom' } },
    scales:{
      y:{ ticks:{ color:'#8A929A', font:{ family: 'Plus Jakarta Sans' }, callback:v=>'Rp '+(v/1e6)+'jt' }, grid:{ color:'#FFF9F0' } },
      x:{ ticks:{ color:'#1E2229', font:commonFont }, grid:{ display:false } }
    }
  }
});

new Chart(document.getElementById('pieChart'),{
  type:'doughnut',
  data:{
    labels: <?= json_encode(array_column($pieData, 'sumber')) ?>,
    datasets:[{
      data: <?= json_encode(array_map(fn($r)=>(int)$r['jumlah'], $pieData)) ?>,
      backgroundColor:['#FFAE34','#FF6B6B','#2a9d8f','#4a90e2','#8a56f2','#f15bb5'], 
      borderWidth: 4,
      borderColor:'#ffffff'
    }]
  },
  options:{
    responsive:true,
    maintainAspectRatio:false,
    plugins:{ 
      legend:{ 
        labels:{ color:'#1E2229', font:commonFont }, 
        position:'bottom' 
      } 
    },
    cutout:'70%'
  }
});
</script>
</body>
</html>