<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
if (!isLoggedIn()) redirect(BASE_URL . '/login.php');

$adminPage  = 'dashboard';
$adminTitle = 'Dashboard';
$db   = getDB();
$year = date('Y');

$totalAnggaran  = (int)$db->query("SELECT SUM(jumlah) FROM anggaran WHERE tahun=$year")->fetchColumn();
$totalRealisasi = (int)$db->query("SELECT SUM(jumlah) FROM realisasi WHERE YEAR(tanggal)=$year AND status='Selesai'")->fetchColumn();
$serapan        = $totalAnggaran > 0 ? round(($totalRealisasi / $totalAnggaran) * 100, 1) : 0;
$totalTx        = (int)$db->query("SELECT COUNT(*) FROM realisasi WHERE YEAR(tanggal)=$year")->fetchColumn();
$menunggu       = (int)$db->query("SELECT COUNT(*) FROM realisasi WHERE status='Proses' AND divalidasi_oleh IS NULL")->fetchColumn();

$recent = $db->query(
  "SELECT r.*, u.nama AS nama_user FROM realisasi r
   LEFT JOIN users u ON u.id=r.dibuat_oleh
   ORDER BY r.created_at DESC LIMIT 6"
)->fetchAll();

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
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f4f6fb;color:#1a2332;min-height:100vh;display:flex}

/* SIDEBAR */
.sidebar{width:240px;background:#1a3a6b;min-height:100vh;display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100}
.sb-top{padding:24px 20px 16px;border-bottom:1px solid rgba(255,255,255,.08)}
.sb-logo{display:flex;align-items:center;gap:10px}
.sb-logo-icon{width:36px;height:36px;background:#e63946;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
.sb-logo-text{font-weight:800;font-size:1.05rem;color:#fff}
.sb-logo-sub{font-size:.62rem;color:rgba(255,255,255,.4);margin-top:1px}
.sb-nav{flex:1;padding:16px 12px;display:flex;flex-direction:column;gap:2px}
.sb-link{display:flex;align-items:center;gap:11px;padding:11px 14px;border-radius:10px;color:rgba(255,255,255,.65);text-decoration:none;font-size:.875rem;font-weight:500;transition:all .18s}
.sb-link:hover{background:rgba(255,255,255,.08);color:#fff}
.sb-link.active{background:rgba(255,255,255,.15);color:#fff;font-weight:700}
.sb-link span{font-size:1rem;flex-shrink:0}
.sb-divider{height:1px;background:rgba(255,255,255,.08);margin:8px 0}
.sb-foot{padding:12px}
.sb-user{display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:10px;background:rgba(255,255,255,.07)}
.sb-avatar{width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.2);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.8rem;flex-shrink:0}
.sb-uname{font-size:.8rem;font-weight:600;color:#fff}
.sb-urole{font-size:.68rem;color:rgba(255,255,255,.45)}

/* MAIN */
.main{margin-left:240px;flex:1;display:flex;flex-direction:column;min-height:100vh}
.topbar{background:#fff;padding:0 32px;height:64px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e8edf5;position:sticky;top:0;z-index:50}
.topbar-left h2{font-size:1.15rem;font-weight:800;color:#1a2332}
.topbar-left p{font-size:.78rem;color:#8896ab;margin-top:1px}
.topbar-right{display:flex;align-items:center;gap:12px}
.notif-btn{width:38px;height:38px;border-radius:10px;background:#f4f6fb;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1rem;position:relative}
.notif-dot{width:8px;height:8px;background:#e63946;border-radius:50%;position:absolute;top:8px;right:8px;border:2px solid #fff}
.top-avatar{width:38px;height:38px;border-radius:50%;background:#1a3a6b;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.82rem}

/* BODY */
.body{padding:28px 32px;flex:1}

/* STAT CARDS */
.stat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:28px}
.stat-card{background:#fff;border-radius:16px;padding:22px 24px;border:1px solid #e8edf5;display:flex;align-items:center;justify-content:space-between;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.stat-info{}
.stat-label{font-size:.78rem;color:#8896ab;font-weight:500;margin-bottom:6px}
.stat-val{font-size:1.5rem;font-weight:800;color:#1a2332;line-height:1}
.stat-icon-wrap{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0}
.si-blue{background:#dbeafe}
.si-green{background:#d1fae5}
.si-orange{background:#fff3e0}

/* ALERT VALIDASI */
.alert-validasi{background:#fff8e1;border:1px solid #fde68a;border-radius:14px;padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;gap:14px}
.alert-validasi .av-icon{font-size:1.4rem;flex-shrink:0}
.alert-validasi .av-text{flex:1}
.alert-validasi .av-title{font-weight:700;font-size:.92rem;color:#92400e}
.alert-validasi .av-sub{font-size:.8rem;color:#a16207;margin-top:2px}
.alert-validasi a{background:#f59e0b;color:#fff;padding:8px 16px;border-radius:8px;font-size:.8rem;font-weight:700;text-decoration:none;flex-shrink:0}

/* GRID 2 */
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px}

/* CARD */
.card{background:#fff;border-radius:16px;padding:24px;border:1px solid #e8edf5;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
.card-title{font-size:.9rem;font-weight:700;color:#1a2332}
.card-link{font-size:.78rem;color:#2451a3;text-decoration:none;font-weight:600}

/* TABLE */
.tw{overflow-x:auto}
table{width:100%;border-collapse:collapse}
th{font-size:.72rem;font-weight:700;color:#8896ab;text-transform:uppercase;letter-spacing:.05em;padding:10px 14px;border-bottom:2px solid #f0f4f8;text-align:left;white-space:nowrap}
td{padding:12px 14px;font-size:.85rem;border-bottom:1px solid #f4f6fb;color:#1a2332}
tr:last-child td{border-bottom:none}
tbody tr:hover td{background:#f9fafc}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;white-space:nowrap}
.bs{background:#d1fae5;color:#065f46}
.bw{background:#fff3e0;color:#9a3412}
.bd{background:#fee2e2;color:#991b1b}
.bi{background:#dbeafe;color:#1e40af}
.publik-yes{font-size:.9rem}
.action-btns{display:flex;gap:5px}
.btn-sm{display:inline-flex;align-items:center;padding:5px 11px;border-radius:7px;font-size:.75rem;font-weight:600;cursor:pointer;text-decoration:none;border:none;font-family:inherit}
.btn-edit{background:#eff6ff;color:#1d4ed8}
.btn-del{background:#fef2f2;color:#dc2626}

/* PROGRESS */
.prog-item{margin-bottom:16px}
.prog-head{display:flex;justify-content:space-between;margin-bottom:5px}
.prog-label{font-size:.83rem;font-weight:600;color:#1a2332}
.prog-pct{font-size:.83rem;font-weight:700;color:#2451a3}
.prog-bar{height:8px;background:#f0f4f8;border-radius:99px;overflow:hidden}
.prog-fill{height:100%;border-radius:99px;background:linear-gradient(90deg,#1a3a6b,#2451a3)}
.prog-amt{display:flex;justify-content:space-between;font-size:.72rem;color:#8896ab;margin-top:3px}

@media(max-width:900px){
  .sidebar{transform:translateX(-100%)}
  .main{margin-left:0}
  .stat-grid,.grid2{grid-template-columns:1fr}
  .body{padding:20px}
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sb-top">
    <div class="sb-logo">
      <div class="sb-logo-icon">🏛️</div>
      <div><div class="sb-logo-text"><?= APP_NAME ?></div><div class="sb-logo-sub">Admin Panel</div></div>
    </div>
  </div>
  <nav class="sb-nav">
    <a class="sb-link active" href="<?= BASE_URL ?>/admin/index.php"><span>📊</span> Dashboard</a>
    <?php if(hasRole(['bendahara','sekdes'])): ?>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/kelola_anggaran.php"><span>📋</span> Kelola Anggaran</a>
    <?php endif; ?>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/realisasi_dana.php"><span>💸</span> Realisasi Dana</a>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/laporan.php"><span>📄</span> Laporan Publik</a>
    <?php if(hasRole(['sekdes','kades'])): ?>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/grafik.php"><span>📈</span> Grafik</a>
    <?php endif; ?>
    <div class="sb-divider"></div>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/pengaturan.php"><span>⚙️</span> Pengaturan</a>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/logout.php"><span>🚪</span> Keluar</a>
  </nav>
  <div class="sb-foot">
    <div class="sb-user">
      <div class="sb-avatar"><?= $avatar ?></div>
      <div><div class="sb-uname"><?= htmlspecialchars($user['nama']) ?></div><div class="sb-urole"><?= $roleDisplay ?></div></div>
    </div>
  </div>
</aside>

<!-- MAIN -->
<div class="main">
  <!-- TOPBAR -->
  <header class="topbar">
    <div class="topbar-left">
      <h2>Dashboard</h2>
      <p>Selamat datang, <?= htmlspecialchars($user['nama']) ?> — <?= date('l, d F Y') ?></p>
    </div>
    <div class="topbar-right">
      <?php if($menunggu > 0): ?>
      <button class="notif-btn" title="<?= $menunggu ?> menunggu validasi">🔔<span class="notif-dot"></span></button>
      <?php endif; ?>
      <div class="top-avatar"><?= $avatar ?></div>
    </div>
  </header>

  <div class="body">

    <!-- STAT CARDS -->
    <div class="stat-grid">
      <div class="stat-card">
        <div class="stat-info">
          <div class="stat-label">Total Anggaran <?= $year ?></div>
          <div class="stat-val"><?= rupiah($totalAnggaran) ?></div>
        </div>
        <div class="stat-icon-wrap si-blue">💼</div>
      </div>
      <div class="stat-card">
        <div class="stat-info">
          <div class="stat-label">Total Realisasi</div>
          <div class="stat-val"><?= rupiah($totalRealisasi) ?></div>
        </div>
        <div class="stat-icon-wrap si-green">📈</div>
      </div>
      <div class="stat-card">
        <div class="stat-info">
          <div class="stat-label">Serapan Anggaran</div>
          <div class="stat-val"><?= $serapan ?>%</div>
        </div>
        <div class="stat-icon-wrap si-orange">🎯</div>
      </div>
    </div>

    <!-- NOTIF VALIDASI -->
    <?php if($menunggu > 0 && hasRole('kades')): ?>
    <div class="alert-validasi">
      <div class="av-icon">⚠️</div>
      <div class="av-text">
        <div class="av-title"><?= $menunggu ?> Realisasi Menunggu Validasi Anda</div>
        <div class="av-sub">Silakan tinjau dan validasi agar dapat dipublikasikan ke masyarakat</div>
      </div>
      <a href="<?= BASE_URL ?>/admin/realisasi_dana.php">Tinjau →</a>
    </div>
    <?php endif; ?>

    <!-- CHARTS -->
    <div class="grid2">
      <div class="card">
        <div class="card-head">
          <span class="card-title">Anggaran vs Realisasi per Sektor</span>
        </div>
        <div style="height:240px"><canvas id="barChart"></canvas></div>
      </div>
      <div class="card">
        <div class="card-head">
          <span class="card-title">Sumber Pendapatan</span>
        </div>
        <div style="height:240px"><canvas id="pieChart"></canvas></div>
      </div>
    </div>

    <!-- SERAPAN + TABEL -->
    <div class="grid2">
      <div class="card">
        <div class="card-head"><span class="card-title">Serapan per Sektor</span></div>
        <?php foreach($barData as $c):
          $pct = $c['anggaran']>0 ? round(($c['realisasi']/$c['anggaran'])*100,1) : 0;
        ?>
        <div class="prog-item">
          <div class="prog-head"><span class="prog-label"><?= $c['kategori'] ?></span><span class="prog-pct"><?= $pct ?>%</span></div>
          <div class="prog-bar"><div class="prog-fill" style="width:<?= min($pct,100) ?>%"></div></div>
          <div class="prog-amt"><span><?= rupiah($c['realisasi']) ?></span><span><?= rupiah($c['anggaran']) ?></span></div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="card">
        <div class="card-head">
          <span class="card-title">Transaksi Terbaru</span>
          <a class="card-link" href="<?= BASE_URL ?>/admin/realisasi_dana.php">Lihat semua →</a>
        </div>
        <div class="tw">
          <table>
            <thead><tr><th>Kegiatan</th><th>Jumlah</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach($recent as $r):
                $bc=['Selesai'=>'bs','Proses'=>'bw','Batal'=>'bd'];
              ?>
              <tr>
                <td><?= htmlspecialchars(mb_strimwidth($r['nama_kegiatan'],0,30,'…')) ?></td>
                <td><?= rupiah($r['jumlah']) ?></td>
                <td><span class="badge <?= $bc[$r['status']]??'bi' ?>"><?= $r['status'] ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div><!-- /body -->
</div><!-- /main -->

<script>
new Chart(document.getElementById('barChart'),{type:'bar',data:{
  labels:<?= json_encode(array_column($barData,'kategori')) ?>,
  datasets:[
    {label:'Anggaran',data:<?= json_encode(array_map(fn($r)=>(int)$r['anggaran'],$barData)) ?>,backgroundColor:'#bfdbfe',borderRadius:6},
    {label:'Realisasi',data:<?= json_encode(array_map(fn($r)=>(int)$r['realisasi'],$barData)) ?>,backgroundColor:'#1a3a6b',borderRadius:6}
  ]
},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}},scales:{y:{ticks:{callback:v=>'Rp'+(v/1e6)+'jt'},grid:{color:'#f4f6fb'}},x:{grid:{display:false}}}}});

new Chart(document.getElementById('pieChart'),{type:'doughnut',data:{
  labels:<?= json_encode(array_column($pieData,'sumber')) ?>,
  datasets:[{data:<?= json_encode(array_map(fn($r)=>(int)$r['jumlah'],$pieData)) ?>,backgroundColor:['#1a3a6b','#2a9d8f','#f4a261'],borderWidth:3,borderColor:'#fff'}]
},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}},cutout:'65%'}});
</script>
</body>
</html>
