<?php
// admin/realisasi_dana.php — Catat & Kelola Realisasi Dana (Uang Keluar)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn()) redirect(BASE_URL . '/login.php');
if (!hasRole(['sekdes','kades','bendahara'])) {
    setFlash('error','Akses ditolak.'); redirect(BASE_URL.'/admin/index.php');
}

$db = getDB();
$adminPage  = 'realisasi';
$adminTitle = 'Realisasi Dana';
$user       = currentUser();
$tahunF = (int)($_GET['tahun'] ?? date('Y'));

// ── VALIDASI SECURE REALISASI DENGAN VERIFIKASI PASSWORD (Hanya Kades) ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['secure_action']) && $_POST['secure_action'] === 'validasi_realisasi' && hasRole('kades')) {
    verifyCsrf();
    $realisasiId = (int)($_POST['realisasi_id'] ?? 0);
    $password    = $_POST['confirm_password'] ?? '';

    $stmtUser = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmtUser->execute([$user['id']]);
    $currentHash = $stmtUser->fetchColumn();

    if (password_verify($password, $currentHash)) {
        $db->prepare("UPDATE realisasi SET divalidasi_oleh=?, divalidasi_at=NOW(), is_publik=1 WHERE id=?")
           ->execute([$user['id'], $realisasiId]);
        setFlash('success','Otentikasi Berhasil! Realisasi belanja sah divalidasi oleh Kepala Desa.');
    } else {
        setFlash('error','Gagal Validasi: Kata sandi konfirmasi tidak sesuai.');
    }
    redirect(BASE_URL.'/admin/realisasi_dana.php?tahun='.$tahunF);
}

// ── HAPUS ─────────────────────────────────────────────────────
if (isset($_GET['hapus']) && is_numeric($_GET['hapus']) && !hasRole('kades')) {
    $row = $db->prepare("SELECT file_bukti FROM realisasi WHERE id=?");
    $row->execute([(int)$_GET['hapus']]);
    $bukti = $row->fetchColumn();
    if ($bukti && file_exists(UPLOAD_DIR . $bukti)) { unlink(UPLOAD_DIR . $bukti); }
    $db->prepare("DELETE FROM realisasi WHERE id=?")->execute([(int)$_GET['hapus']]);
    setFlash('success','Transaksi pengeluaran berhasil dihapus.');
    redirect(BASE_URL.'/admin/realisasi_dana.php?tahun='.$tahunF);
}

// ── HANDLE POST (Tambah / Edit) ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['secure_action'])) {
    verifyCsrf();
    if (hasRole('kades')) {
        setFlash('error', 'Kepala Desa hanya berhak melakukan validasi laporan pengeluaran.');
        redirect(BASE_URL . '/admin/realisasi_dana.php');
    }
    
    $action        = $_POST['action'] ?? 'tambah';
    $nama          = trim($_POST['nama_kegiatan'] ?? '');
    $kategori      = $_POST['kategori'] ?? '';
    $jumlah        = (int)str_replace(['.',',',' '], '', $_POST['jumlah'] ?? '');
    $tanggal       = $_POST['tanggal'] ?? '';
    $status        = $_POST['status'] ?? 'Proses';
    $keterangan    = trim($_POST['keterangan'] ?? '');

    $errors = [];
    if (!$nama)     $errors[] = 'Nama belanja kegiatan wajib diisi.';
    if (!$kategori) $errors[] = 'Kategori wajib dipilih.';
    if ($jumlah<=0) $errors[] = 'Jumlah uang keluar harus lebih dari 0.';
    if (!$tanggal)  $errors[] = 'Tanggal wajib diisi.';

    $fileBukti = null;
    if (!empty($_FILES['file_bukti']['name'])) {
        $allowed = ['jpg','jpeg','png','pdf'];
        $ext = strtolower(pathinfo($_FILES['file_bukti']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Format file salah (Gunakan JPG, PNG, PDF).';
        } else {
            $fileBukti = uniqid('bukti_') . '.' . $ext;
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
            move_uploaded_file($_FILES['file_bukti']['tmp_name'], UPLOAD_DIR . $fileBukti);
        }
    }

    if (empty($errors)) {
        if ($action === 'edit' && !empty($_POST['id'])) {
            $sql = "UPDATE realisasi SET nama_kegiatan=?,kategori=?,jumlah=?,tanggal=?,status=?,keterangan=?,divalidasi_oleh=NULL,divalidasi_at=NULL,is_publik=0";
            $params = [$nama,$kategori,$jumlah,$tanggal,$status,$keterangan];
            if ($fileBukti) { $sql .= ',file_bukti=?'; $params[] = $fileBukti; }
            $sql .= ' WHERE id=?'; $params[] = (int)$_POST['id'];
            $db->prepare($sql)->execute($params);
            setFlash('success','Data realisasi diperbarui (Menunggu Re-Validasi Kades).');
        } else {
            $db->prepare(
                "INSERT INTO realisasi (nama_kegiatan,kategori,jumlah,tanggal,status,keterangan,file_bukti,is_publik,dibuat_oleh)
                 VALUES (?,?,?,?,?,?,?,0,?)"
            )->execute([$nama,$kategori,$jumlah,$tanggal,$status,$keterangan,$fileBukti,$user['id']]);
            setFlash('success','Realisasi uang keluar berhasil diajukan.');
        }
        redirect(BASE_URL.'/admin/realisasi_dana.php?tahun='.date('Y', strtotime($tanggal)));
    }
}

$editData = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit']) && !hasRole('kades')) {
    $s = $db->prepare("SELECT * FROM realisasi WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $editData = $s->fetch();
}

$katF   = $_GET['kat'] ?? '';
$where  = ["YEAR(r.tanggal)=?"]; $params = [$tahunF];
if ($katF) { $where[] = "r.kategori=?"; $params[] = $katF; }
$listStmt = $db->prepare(
    "SELECT r.*, u.nama AS user_nama, v.nama AS validator_nama FROM realisasi r
     LEFT JOIN users u ON u.id=r.dibuat_oleh LEFT JOIN users v ON v.id=r.divalidasi_oleh
     WHERE ".implode(' AND ',$where)." ORDER BY r.tanggal DESC"
);
$listStmt->execute($params);
$list = $listStmt->fetchAll();

$chartStmt = $db->prepare(
    "SELECT a.kategori, SUM(a.jumlah) AS anggaran, COALESCE(SUM(r.jumlah),0) AS realisasi
     FROM anggaran a LEFT JOIN realisasi r ON r.kategori=a.kategori AND r.status='Selesai' AND YEAR(r.tanggal)=?
     WHERE a.tahun=? GROUP BY a.kategori"
);
$chartStmt->execute([$tahunF,$tahunF]);
$chartData = $chartStmt->fetchAll();

$roleLabel = ['bendahara'=>'Bendahara Desa','sekdes'=>'Sekretaris Desa','kades'=>'Kepala Desa'];
$roleDisplay = $roleLabel[$user['role']] ?? $user['role'];
$avatar = strtoupper(substr($user['nama'], 0, 2));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Realisasi Dana — <?= APP_NAME ?> Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Material+Icons+Round&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<style>
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #FFF9F1; color: #1E2229; min-height: 100vh; display: flex; }
.sidebar { width: 80px; background: #FFAE34; min-height: 100vh; display: flex; flex-direction: column; position: fixed; top: 0; left: 0; bottom: 0; z-index: 100; box-shadow: 4px 0 20px rgba(243, 230, 211, 0.4); align-items: center; }
.sb-top { padding: 24px 0 16px; width: 100%; text-align: center; border-bottom: 1px solid rgba(255, 255, 255, 0.2); }
.sb-logo-text { font-weight: 800; font-size: 0.85rem; color: #1E2229; letter-spacing: 0.05em; text-transform: uppercase; }
.sb-nav { flex: 1; padding: 20px 0; display: flex; flex-direction: column; gap: 8px; width: 100%; align-items: center; }
.sb-link { display: flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 14px; color: rgba(30, 34, 41, 0.7); text-decoration: none; transition: all .2s ease; }
.sb-link .material-icons-round { font-size: 1.4rem; color: rgba(30, 34, 41, 0.75); }
.sb-link:hover { background: rgba(255, 255, 255, 0.2); color: #1E2229; }
.sb-link.active { background: #FF6B6B; color: #FFFFFF; position: relative; }
.sb-link.active .material-icons-round { color: #FFFFFF; }
.sb-link.active::after { content: ''; position: absolute; right: -16px; top: 50%; transform: translateY(-50%); border-style: solid; border-width: 6px 6px 6px 0; border-color: transparent #FFF9F1 transparent transparent; }
.sb-divider { width: 70%; height: 1px; background: rgba(255, 255, 255, 0.2); margin: 8px 0; }
.sb-foot { padding: 20px 0; width: 100%; display: flex; justify-content: center; }
.sb-avatar { width: 40px; height: 40px; border-radius: 50%; background: #FFFFFF; color: #1E2229; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: .85rem; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
.main { margin-left: 80px; flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
.topbar { padding: 0 40px; height: 80px; display: flex; align-items: center; justify-content: space-between; background: transparent; }
.topbar-left h2 { font-size: 2rem; font-weight: 800; color: #1E2229; letter-spacing: -0.03em; }
.topbar-left p { font-size: .78rem; color: #FF6B6B; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; margin-top: 2px; }
.topbar-right { display: flex; align-items: center; gap: 16px; }
.top-avatar { width: 40px; height: 40px; border-radius: 50%; background: #FFAE34; color: #1E2229; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: .85rem; }
.body { padding: 12px 40px 40px 40px; flex: 1; }
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 32px; }
.card { background: #FFFFFF; border-radius: 20px; padding: 28px; border: none; box-shadow: 0 10px 30px rgba(243, 230, 211, 0.4); }
.card-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.card-title { font-size: 1.05rem; font-weight: 800; color: #1E2229; letter-spacing: -0.02em; }
.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px; }
.form-group.full { grid-column: span 2; }
.form-group label { display: block; font-size: 0.85rem; font-weight: 700; color: #1E2229; margin-bottom: 8px; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 14px 18px; border: 1.5px solid #F3E6D3; background-color: #FFFDF9; border-radius: 12px; font-family: inherit; font-size: 0.92rem; color: #1E2229; outline: none; transition: all 0.2s ease; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #FFAE34; background-color: #FFFFFF; box-shadow: 0 0 0 4px rgba(255, 174, 52, 0.1); }
.form-hint { font-size: 0.78rem; color: #8A929A; margin-top: 6px; }
.required { color: #FF6B6B; }
.btn-mockup-primary { background: #FF6B6B; color: #FFFFFF; padding: 14px 28px; border-radius: 12px; font-weight: 700; font-size: 0.92rem; border: none; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 6px 16px rgba(255, 107, 107, 0.25); }
.btn-mockup-primary:hover { background: #E85555; transform: translateY(-1px); }
.btn-mockup-warning { background: #FFF3E5; color: #FFAE34; padding: 14px 28px; border-radius: 12px; font-weight: 700; font-size: 0.92rem; text-decoration: none; display: inline-block; transition: all 0.2s ease; }
.btn-mockup-warning:hover { background: #FFEAD1; }
.filter-select { background: #FFFFFF; border: 1.5px solid #F3E6D3; padding: 10px 18px; border-radius: 10px; color: #1E2229; font-family: inherit; font-size: 0.88rem; font-weight: 700; outline: none; cursor: pointer; box-shadow: 0 2px 8px rgba(243, 230, 211, 0.3); }
.tw { overflow-x: auto; }
table { width: 100%; border-collapse: separate; border-spacing: 0 6px; }
th { font-size: .75rem; font-weight: 700; color: #8A929A; text-transform: uppercase; letter-spacing: .05em; padding: 10px 14px; text-align: left; }
td { padding: 16px 14px; font-size: .88rem; background-color: #FFFDF9; color: #1E2229; }
td:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
td:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }
.badge { display: inline-block; padding: 4px 12px; border-radius: 10px; font-size: .75rem; font-weight: 700; }
.badge-success { background: #E6F7F4; color: #2a9d8f; } 
.badge-warning { background: #FFF3E5; color: #FFAE34; } 
.badge-danger { background: #FFF0F0; color: #FF6B6B; } 
.badge-info { background: #E6F2FF; color: #4a90e2; }
.btn-action-edit { color: #FFAE34; background: #FFF3E5; padding: 6px 14px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 0.8rem; margin-right: 6px; display: inline-block; }
.btn-action-edit:hover { background: #FFEAD1; }
.btn-action-del { color: #FF6B6B; background: #FFF0F0; padding: 6px 14px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 0.8rem; display: inline-block; }
.btn-action-del:hover { background: #FFE1E1; }
.btn-action-success { color: #2a9d8f; background: #E6F7F4; padding: 6px 14px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 0.8rem; display: inline-block; cursor: pointer; border: none; }
.btn-action-success:hover { background: #CEF3EC; }
.prog-item { margin-bottom: 20px; }
.prog-item:last-child { margin-bottom: 0; }
.prog-head { display: flex; justify-content: space-between; margin-bottom: 6px; }
.prog-label { font-size: .88rem; font-weight: 700; color: #1E2229; }
.prog-pct { font-size: .88rem; font-weight: 800; color: #FF6B6B; }
.prog-bar { height: 8px; background: #FFF9F0; border-radius: 20px; overflow: hidden; }
.prog-fill { height: 100%; border-radius: 20px; background: #FFAE34; }
.prog-amt { display: flex; justify-content: space-between; font-size: .75rem; color: #8A929A; margin-top: 4px; font-weight: 600; }
.alert-success { background: #E6F7F4; border: 1px solid #CEF3EC; border-radius: 12px; padding: 14px 18px; color: #2a9d8f; font-size: 0.88rem; font-weight: 600; margin-bottom: 24px; }
.alert-error { background: #FFF0F0; border: 1px solid #FFE1E1; border-radius: 12px; padding: 14px 18px; color: #FF6B6B; font-size: 0.88rem; font-weight: 600; margin-bottom: 24px; }

/* MODAL VERIFIKASI SECURE */
.secure-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 500; align-items: center; justify-content: center; }
.modal-content { background: #FFF9F1; padding: 32px; border-radius: 20px; width: 100%; max-width: 450px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 2.5px solid #FF6B6B; }
.modal-content h4 { font-size: 1.1rem; font-weight: 800; color: #1E2229; margin-bottom: 12px; }
.modal-content p { font-size: 0.85rem; color: #8A929A; margin-bottom: 20px; line-height: 1.4; }
</style>
</head>
<body>

<div class="secure-modal" id="secureModal">
  <div class="modal-content">
    <h4>🔒 Verifikasi Keamanan Pengeluaran</h4>
    <p>Otentikasi khusus Kepala Desa diperlukan untuk menyetujui klaim realisasi belanja ini agar dapat disebarluaskan ke masyarakat publik.</p>
    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="secure_action" value="validasi_realisasi">
      <input type="hidden" name="realisasi_id" id="modalRealisasiId" value="">
      <div class="form-group" style="margin-bottom: 20px;">
        <label>Kata Sandi Akun Anda</label>
        <input type="password" name="confirm_password" placeholder="Masukkan password Anda" required style="background:#fff;">
      </div>
      <div style="display:flex; gap:10px; justify-content: flex-end;">
        <button type="button" class="btn-mockup-warning" style="padding:10px 20px;" onclick="closeSecureModal()">Batal</button>
        <button type="submit" class="btn-mockup-primary" style="padding:10px 20px; box-shadow:none;">Setujui Belanja</button>
      </div>
    </form>
  </div>
</div>

<aside class="sidebar">
  <div class="sb-top"><div class="sb-logo-text">STK</div></div>
  <nav class="sb-nav">
    <a class="sb-link" href="<?= BASE_URL ?>/admin/index.php" title="Dashboard"><span class="material-icons-round">space_dashboard</span></a>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/kelola_anggaran.php" title="Kelola Anggaran"><span class="material-icons-round">assignment</span></a>
    <a class="sb-link active" href="<?= BASE_URL ?>/admin/realisasi_dana.php" title="Realisasi Dana"><span class="material-icons-round">payments</span></a>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/laporan.php" title="Laporan Publik"><span class="material-icons-round">description</span></a>
    <?php if(hasRole(['sekdes','kades'])): ?><a class="sb-link" href="<?= BASE_URL ?>/admin/grafik.php" title="Grafik"><span class="material-icons-round">insert_chart</span></a><?php endif; ?>
    <div class="sb-divider"></div>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/pengaturan.php" title="Pengaturan"><span class="material-icons-round">settings</span></a>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/logout.php" title="Keluar"><span class="material-icons-round">logout</span></a>
  </nav>
  <div class="sb-foot"><div class="sb-avatar"><?= $avatar ?></div></div>
</aside>

<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <h2><?= $adminTitle ?></h2>
      <p>SITANGKIS — Pengeluaran Pelaksanaan Program Kerja (Uang Keluar)</p>
    </div>
    <div class="topbar-right"><div class="top-avatar"><?= $avatar ?></div></div>
  </header>

  <div class="body">
    <?php if(isset($_SESSION['flash']['success'])): ?>
      <div class="alert-success"><?= $_SESSION['flash']['success'] ?></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['flash']['error'])): ?>
      <div class="alert-error"><?= $_SESSION['flash']['error'] ?></div>
    <?php endif; ?>

    <div class="grid2">
      <div class="card">
        <div class="card-head"><span class="card-title">Realisasi Pengeluaran per Sektor <?= $tahunF ?></span></div>
        <div style="height:240px; position: relative;"><canvas id="realBar"></canvas></div>
      </div>
      <div class="card">
        <div class="card-head"><span class="card-title">Detail Serapan Anggaran Sektor</span></div>
        <div>
          <?php foreach ($chartData as $c):
            $pct = $c['anggaran'] > 0 ? round(($c['realisasi']/$c['anggaran'])*100,1) : 0;
          ?>
          <div class="prog-item">
            <div class="prog-head"><span class="prog-label"><?= $c['kategori'] ?></span><span class="prog-pct"><?= $pct ?>%</span></div>
            <div class="prog-bar"><div class="prog-fill" style="width:<?= min($pct,100) ?>%"></div></div>
            <div class="prog-amt"><span>Belanja: <?= rupiah($c['realisasi']) ?></span><span>Pagu Batas: <?= rupiah($c['anggaran']) ?></span></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <?php if (!hasRole('kades')): ?>
    <div class="card" style="margin-bottom: 32px; margin-top:32px;">
      <div class="card-head"><span class="card-title"><?= $editData ? 'Edit Nota Realisasi Belanja' : 'Catat Pengeluaran Belanja Baru' ?></span></div>
      <?php if (!empty($errors)): ?><div class="alert alert-error"><?= implode('<br>', array_map('clean', $errors)) ?></div><?php endif; ?>
      <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="<?= $editData ? 'edit' : 'tambah' ?>">
        <?php if ($editData): ?><input type="hidden" name="id" value="<?= $editData['id'] ?>"><?php endif; ?>
        <div class="form-grid">
          <div class="form-group full">
            <label>Nama Belanja Kegiatan <span class="required">*</span></label>
            <input type="text" name="nama_kegiatan" value="<?= clean($editData['nama_kegiatan'] ?? '') ?>" placeholder="Contoh: Belanja Aspal Pembangunan Jalan RT 05" required>
          </div>
          <div class="form-group">
            <label>Tanggal Transaksi <span class="required">*</span></label>
            <input type="date" name="tanggal" value="<?= $editData['tanggal'] ?? date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label>Kategori Sektor Tujuan <span class="required">*</span></label>
            <select name="kategori" required>
              <option value="">— Pilih Kategori —</option>
              <?php foreach (['Infrastruktur','Pendidikan','Kesehatan','Administrasi','Pemberdayaan'] as $k): ?>
              <option value="<?= $k ?>" <?= ($editData['kategori']??'')===$k?'selected':'' ?>><?= $k ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Jumlah Uang Keluar (Rp) <span class="required">*</span></label>
            <input type="number" name="jumlah" min="1" value="<?= $editData['jumlah'] ?? '' ?>" required>
          </div>
          <div class="form-group">
            <label>Status Pelaksanaan</label>
            <select name="status">
              <?php foreach (['Proses','Selesai','Batal'] as $s): ?>
              <option value="<?= $s ?>" <?= ($editData['status']??'Proses')===$s?'selected':'' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Upload Berkas Bukti Kwitansi (Opsional)</label>
            <input type="file" name="file_bukti" accept=".jpg,.jpeg,.png,.pdf">
          </div>
          <div class="form-group full">
            <label>Keterangan Alasan Pengeluaran</label>
            <textarea name="keterangan" rows="3"><?= clean($editData['keterangan'] ?? '') ?></textarea>
          </div>
        </div>
        <div style="display:flex;gap:12px">
          <button type="submit" class="btn-mockup-primary"><?= $editData ? 'Perbarui Berkas Belanja' : 'Kunci Uang Keluar' ?></button>
          <?php if ($editData): ?><a href="<?= BASE_URL ?>/admin/realisasi_dana.php" class="btn-mockup-warning">Batal</a><?php endif; ?>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <div class="card" style="margin-top:32px;">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:20px;">
        <span class="card-title">Daftar Transaksi Realisasi Uang Keluar</span>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
          <select name="tahun" onchange="this.form.submit()" class="filter-select"><?php foreach ([2026,2025,2024] as $y): ?><option value="<?= $y ?>" <?= $tahunF==$y?'selected':'' ?>><?= $y ?></option><?php endforeach; ?></select>
        </form>
      </div>
      <div class="tw">
        <table>
          <thead>
            <tr>
              <th>#</th><th>Tanggal</th><th>Nama Belanja</th><th>Kategori</th><th>Jumlah Keluar</th><th>Status</th><th>Validasi Kades</th><th>Publik</th><th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($list as $i => $r): $bc = ['Selesai'=>'badge-success','Proses'=>'badge-warning','Batal'=>'badge-danger']; ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
              <td><strong><?= clean($r['nama_kegiatan']) ?></strong></td>
              <td><?= $r['kategori'] ?></td>
              <td><strong><?= rupiah($r['jumlah']) ?></strong></td>
              <td><span class="badge <?= $bc[$r['status']]??'badge-info' ?>"><?= $r['status'] ?></span></td>
              <td>
                <?php if ($r['validator_nama']): ?>
                <span style="color:#2a9d8f; font-weight:700;">Sesuai: <?= clean($r['validator_nama']) ?></span>
                <?php elseif (hasRole('kades')): ?>
                <button type="button" class="btn-action-success" onclick="openSecureModal(<?= $r['id'] ?>)">Setujui</button>
                <?php else: ?>
                <span class="badge badge-warning">Menunggu</span>
                <?php endif; ?>
              </td>
              <td><?= $r['is_publik'] ? '<span class="badge badge-success">Publik</span>' : '<span class="badge badge-info">Draft</span>' ?></td>
              <td>
                <div class="action-btns">
                  <?php if (!hasRole('kades')): ?>
                  <a href="?edit=<?= $r['id'] ?>" class="btn-action-edit">Edit</a>
                  <a href="?tahun=<?= $tahunF ?>&hapus=<?= $r['id'] ?>" class="btn-action-del" onclick="return confirm('Hapus pengeluaran?')">Hapus</a>
                  <?php else: ?>-<?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($list)): ?>
            <tr><td colspan="9" style="text-align:center;padding:40px;color:#8A929A;font-weight:600;">Belum ada data realisasi.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script>
const commonFont = { family: 'Plus Jakarta Sans', size: 11, weight: '600' };
new Chart(document.getElementById('realBar'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($chartData, 'kategori')) ?>,
    datasets: [
      { label: 'Pagu Dana', data: <?= json_encode(array_map(fn($r)=>(int)$r['anggaran'], $chartData)) ?>, backgroundColor: '#FFF3E5', borderColor: '#FFAE34', borderWidth: 1.5 },
      { label: 'Realisasi Keluar', data: <?= json_encode(array_map(fn($r)=>(int)$r['realisasi'], $chartData)) ?>, backgroundColor: '#FF6B6B' }
    ]
  },
  options: { responsive: true, maintainAspectRatio: false }
});

function openSecureModal(id) {
    document.getElementById('modalRealisasiId').value = id;
    document.getElementById('secureModal').style.display = 'flex';
}
function closeSecureModal() {
    document.getElementById('secureModal').style.display = 'none';
}
</script>
</body>
</html>