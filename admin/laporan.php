<?php
// admin/laporan.php — Laporan Publik (Admin View)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn()) redirect(BASE_URL . '/login.php');

$db = getDB();
$adminPage  = 'laporan';
$adminTitle = 'Laporan Publik';

$tahun  = (int)($_GET['tahun'] ?? date('Y'));
$kat    = $_GET['kat'] ?? '';
$cari   = trim($_GET['cari'] ?? '');

$where  = ["YEAR(tanggal)=?"]; $params = [$tahun];
if ($kat)  { $where[] = "kategori=?"; $params[] = $kat; }
if ($cari) { $where[] = "nama_kegiatan LIKE ?"; $params[] = "%$cari%"; }

// Tampilkan semua (bukan hanya publik) di admin
$stmt = $db->prepare("SELECT r.*, u.nama AS user_nama FROM realisasi r LEFT JOIN users u ON u.id=r.dibuat_oleh WHERE ".implode(' AND ',$where)." ORDER BY r.tanggal DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Summary per kategori
$summary = $db->prepare(
    "SELECT kategori, SUM(jumlah) AS total, COUNT(*) AS jml
     FROM realisasi WHERE YEAR(tanggal)=? GROUP BY kategori"
);
$summary->execute([$tahun]);
$summaryData = $summary->fetchAll();

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
<title>Laporan Publik — <?= APP_NAME ?> Admin</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Material+Icons+Round&display=swap" rel="stylesheet">

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

/* COMPONENT CARD */
.card {
  background: #FFFFFF; border-radius: 20px;
  padding: 28px; border: none;
  box-shadow: 0 10px 30px rgba(243, 230, 211, 0.4);
}
.card-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.card-title { font-size: 1.05rem; font-weight: 800; color: #1E2229; letter-spacing: -0.02em; }

/* SUMMARY GRID */
.stat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}
.stat-card {
  background: #FFFFFF; border-radius: 20px;
  padding: 20px 24px; border: none;
  box-shadow: 0 10px 30px rgba(243, 230, 211, 0.4);
  transition: transform 0.2s;
}
.stat-card:hover { transform: translateY(-3px); }
.stat-label { font-size: .78rem; color: #8A929A; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 6px; }
.stat-val { font-size: 1.35rem; font-weight: 800; color: #1E2229; line-height: 1.2; letter-spacing: -0.02em; }
.stat-sub { font-size: .78rem; color: #8A929A; margin-top: 6px; font-weight: 600; }

/* FILTERS & SEARCH INPUTS */
.filter-form { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 24px; }
.filter-select, .search-input {
  background: #FFFFFF; border: 1.5px solid #F3E6D3; padding: 12px 18px;
  border-radius: 12px; color: #1E2229; font-family: inherit; font-size: 0.88rem;
  font-weight: 700; outline: none; transition: all 0.2s;
}
.filter-select { cursor: pointer; }
.search-input { font-weight: 500; min-width: 240px; }
.search-input:focus, .filter-select:focus {
  border-color: #FFAE34; box-shadow: 0 0 0 4px rgba(255, 174, 52, 0.1);
}

/* BUTTONS */
.btn-primary-mockup {
  background: #FF6B6B; color: #FFFFFF; padding: 12px 24px; border-radius: 12px;
  font-weight: 700; font-size: 0.88rem; border: none; cursor: pointer; transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(255, 107, 107, 0.2);
}
.btn-primary-mockup:hover { background: #E85555; transform: translateY(-1px); }

.btn-warning-mockup {
  background: #FFF3E5; color: #FFAE34; padding: 12px 24px; border-radius: 12px;
  font-weight: 700; font-size: 0.88rem; text-decoration: none; display: inline-block; transition: all 0.2s;
}
.btn-warning-mockup:hover { background: #FFEAD1; }

/* MINIMALIST DATA TABLE */
.tw { overflow-x: auto; }
table { width: 100%; border-collapse: separate; border-spacing: 0 6px; }
th {
  font-size: .75rem; font-weight: 700; color: #8A929A;
  text-transform: uppercase; letter-spacing: .05em; padding: 10px 14px; text-align: left;
}
td { padding: 16px 14px; font-size: .88rem; background-color: #FFFDF9; color: #1E2229; }
td:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
td:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

/* BADGES */
.badge { display: inline-block; padding: 4px 12px; border-radius: 10px; font-size: .75rem; font-weight: 700; }
.badge-success { background: #E6F7F4; color: #2a9d8f; } 
.badge-warning { background: #FFF3E5; color: #FFAE34; } 
.badge-danger { background: #FFF0F0; color: #FF6B6B; } 
.badge-info { background: #E6F2FF; color: #4a90e2; }

@media(max-width:1024px){
  .sidebar { transform: translateX(-100%); }
  .main { margin-left: 0; }
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
    <a class="sb-link active" href="<?= BASE_URL ?>/admin/laporan.php" title="Laporan Publik">
      <span class="material-icons-round">description</span>
    </a>
    <?php if(hasRole(['sekdes','kades'])): ?>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/grafik.php" title="Grafik">
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
      <p>SITANGKIS — Transparansi Publikasi Anggaran Desa</p>
    </div>
    <div class="topbar-right">
      <div class="top-avatar"><?= $avatar ?></div>
    </div>
  </header>

  <div class="body">

    <div class="stat-grid">
      <?php foreach ($summaryData as $s): ?>
      <div class="stat-card">
        <div class="stat-label"><?= $s['kategori'] ?></div>
        <div class="stat-val"><?= rupiah($s['total']) ?></div>
        <div class="stat-sub"><?= $s['jml'] ?> kegiatan</div>
      </div>
      <?php endforeach; ?>
      <?php if(empty($summaryData)): ?>
      <div class="stat-card" style="grid-column: 1/-1; text-align: center; padding: 24px; color: #8A929A; font-weight: 600;">
        Belum ada data akumulasi kegiatan pada tahun <?= $tahun ?>.
      </div>
      <?php endif; ?>
    </div>

    <form method="GET" class="filter-form">
      <select name="tahun" onchange="this.form.submit()" class="filter-select">
        <?php foreach ([2026,2025,2024] as $y): ?>
        <option value="<?= $y ?>" <?= $tahun==$y?'selected':'' ?>><?= $y ?></option>
        <?php endforeach; ?>
      </select>
      
      <select name="kat" onchange="this.form.submit()" class="filter-select">
        <option value="">Semua Kategori</option>
        <?php foreach (['Infrastruktur','Pendidikan','Kesehatan','Administrasi','Pemberdayaan'] as $k): ?>
        <option value="<?= $k ?>" <?= $kat===$k?'selected':'' ?>><?= $k ?></option>
        <?php endforeach; ?>
      </select>
      
      <input type="search" name="cari" value="<?= clean($cari) ?>" placeholder="Cari program kerja..." class="search-input">
      
      <button type="submit" class="btn-primary-mockup">Cari</button>
      
      <?php if ($cari||$kat): ?>
      <a href="?tahun=<?= $tahun ?>" class="btn-warning-mockup">Reset</a>
      <?php endif; ?>
    </form>

    <div class="card">
      <div class="card-head">
        <span class="card-title">Semua Transaksi <?= $tahun ?></span>
        <span style="font-size:.85rem; color:#8A929A; font-weight: 600;"><?= count($rows) ?> record ditemukan</span>
      </div>
      
      <div class="tw">
        <table>
          <thead>
            <tr>
              <th style="width: 5%">#</th>
              <th style="width: 12%">Tanggal</th>
              <th style="width: 33%">Nama Kegiatan</th>
              <th style="width: 13%">Kategori</th>
              <th style="width: 15%">Jumlah</th>
              <th style="width: 10%">Status</th>
              <th style="width: 12%">Publik</th>
              <th style="width: 12%">Dibuat Oleh</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $i=>$r):
              $bc=['Selesai'=>'badge-success','Proses'=>'badge-warning','Batal'=>'badge-danger'];
            ?>
            <tr>
              <td><span style="color:#8A929A; font-weight:700;"><?= $i+1 ?></span></td>
              <td><span style="font-weight: 600; color:#8A929A;"><?= date('d M Y', strtotime($r['tanggal'])) ?></span></td>
              <td><strong style="color:#1E2229; font-size:0.95rem;"><?= clean($r['nama_kegiatan']) ?></strong></td>
              <td><span style="font-weight:600; color:#8A929A;"><?= $r['kategori'] ?></span></td>
              <td><strong style="color:#1E2229;"><?= rupiah($r['jumlah']) ?></strong></td>
              <td><span class="badge <?= $bc[$r['status']]??'badge-info' ?>"><?= $r['status'] ?></span></td>
              <td><?= $r['is_publik'] ? '<span class="badge badge-success">Publik</span>' : '<span class="badge badge-info">Draft</span>' ?></td>
              <td><span style="font-weight: 600; color:#1E2229;"><?= clean($r['user_nama']??'-') ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
            <tr><td colspan="8" style="text-align:center; padding:40px; color:#8A929A; font-weight:600;">Tidak ada data laporan yang sesuai filter.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

</body>
</html>