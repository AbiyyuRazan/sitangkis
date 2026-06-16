<?php
// admin/realisasi_dana.php — Catat & Kelola Realisasi Dana
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

// ── VALIDASI MASSAL (Kades) ───────────────────────────────────
if (isset($_GET['validasi_all']) && hasRole('kades')) {
    $db->prepare(
        "UPDATE realisasi SET divalidasi_oleh=?, divalidasi_at=NOW(), is_publik=1
         WHERE status='Selesai' AND divalidasi_oleh IS NULL"
    )->execute([$user['id']]);
    setFlash('success','Semua realisasi berhasil divalidasi dan dipublikasikan!');
    redirect(BASE_URL.'/admin/realisasi_dana.php');
}

// ── VALIDASI SATU ─────────────────────────────────────────────
if (isset($_GET['validasi']) && is_numeric($_GET['validasi']) && hasRole('kades')) {
    $db->prepare(
        "UPDATE realisasi SET divalidasi_oleh=?, divalidasi_at=NOW(), is_publik=1 WHERE id=?"
    )->execute([$user['id'], (int)$_GET['validasi']]);
    setFlash('success','Realisasi berhasil divalidasi dan dipublikasikan.');
    redirect(BASE_URL.'/admin/realisasi_dana.php');
}

// ── HAPUS ─────────────────────────────────────────────────────
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    // Hapus file bukti juga jika ada
    $row = $db->prepare("SELECT file_bukti FROM realisasi WHERE id=?");
    $row->execute([(int)$_GET['hapus']]);
    $bukti = $row->fetchColumn();
    if ($bukti && file_exists(UPLOAD_DIR . $bukti)) {
        unlink(UPLOAD_DIR . $bukti);
    }
    $db->prepare("DELETE FROM realisasi WHERE id=?")->execute([(int)$_GET['hapus']]);
    setFlash('success','Transaksi berhasil dihapus.');
    redirect(BASE_URL.'/admin/realisasi_dana.php');
}

// ── HANDLE POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action        = $_POST['action'] ?? 'tambah';
    $nama          = trim($_POST['nama_kegiatan'] ?? '');
    $kategori      = $_POST['kategori'] ?? '';
    $jumlah        = (int)str_replace(['.',',',' '], '', $_POST['jumlah'] ?? '');
    $tanggal       = $_POST['tanggal'] ?? '';
    $status        = $_POST['status'] ?? 'Proses';
    $keterangan    = trim($_POST['keterangan'] ?? '');
    $is_publik     = isset($_POST['is_publik']) ? 1 : 0;

    $errors = [];
    if (!$nama)     $errors[] = 'Nama kegiatan wajib diisi.';
    if (!$kategori) $errors[] = 'Kategori wajib dipilih.';
    if ($jumlah<=0) $errors[] = 'Jumlah harus lebih dari 0.';
    if (!$tanggal)  $errors[] = 'Tanggal wajib diisi.';

    // Hanya kades yang bisa set publik langsung
    if ($is_publik && !hasRole('kades')) $is_publik = 0;

    // Upload file bukti
    $fileBukti = null;
    if (!empty($_FILES['file_bukti']['name'])) {
        $allowed = ['jpg','jpeg','png','pdf'];
        $ext = strtolower(pathinfo($_FILES['file_bukti']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Format file tidak didukung. Gunakan JPG, PNG, atau PDF.';
        } elseif ($_FILES['file_bukti']['size'] > MAX_UPLOAD_SIZE) {
            $errors[] = 'Ukuran file melebihi 5MB.';
        } else {
            $fileBukti = uniqid('bukti_') . '.' . $ext;
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
            move_uploaded_file($_FILES['file_bukti']['tmp_name'], UPLOAD_DIR . $fileBukti);
        }
    }

    if (empty($errors)) {
        if ($action === 'edit' && !empty($_POST['id'])) {
            $divalidasi   = ($status === 'Selesai' && hasRole('kades')) ? $user['id'] : null;
            $divalidasiAt = $divalidasi ? 'NOW()' : 'NULL';

            $sql = "UPDATE realisasi SET nama_kegiatan=?,kategori=?,jumlah=?,tanggal=?,status=?,keterangan=?,is_publik=?";
            $params = [$nama,$kategori,$jumlah,$tanggal,$status,$keterangan,$is_publik];
            if ($fileBukti) { $sql .= ',file_bukti=?'; $params[] = $fileBukti; }
            $sql .= ' WHERE id=?'; $params[] = (int)$_POST['id'];
            $db->prepare($sql)->execute($params);
            setFlash('success','Realisasi berhasil diperbarui.');
        } else {
            $db->prepare(
                "INSERT INTO realisasi (nama_kegiatan,kategori,jumlah,tanggal,status,keterangan,file_bukti,is_publik,dibuat_oleh)
                 VALUES (?,?,?,?,?,?,?,?,?)"
            )->execute([$nama,$kategori,$jumlah,$tanggal,$status,$keterangan,$fileBukti,$is_publik,$user['id']]);
            setFlash('success','Realisasi berhasil dicatat.');
        }
        redirect(BASE_URL.'/admin/realisasi_dana.php');
    }
}

// ── DATA EDIT ─────────────────────────────────────────────────
$editData = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $s = $db->prepare("SELECT * FROM realisasi WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $editData = $s->fetch();
}

// ── LIST ──────────────────────────────────────────────────────
$tahunF = (int)($_GET['tahun'] ?? date('Y'));
$katF   = $_GET['kat'] ?? '';
$where  = ["YEAR(r.tanggal)=?"]; $params = [$tahunF];
if ($katF) { $where[] = "r.kategori=?"; $params[] = $katF; }
$listStmt = $db->prepare(
    "SELECT r.*, u.nama AS user_nama, v.nama AS validator_nama
     FROM realisasi r
     LEFT JOIN users u ON u.id=r.dibuat_oleh
     LEFT JOIN users v ON v.id=r.divalidasi_oleh
     WHERE ".implode(' AND ',$where)." ORDER BY r.tanggal DESC"
);
$listStmt->execute($params);
$list = $listStmt->fetchAll();

$menunggu = array_filter($list, fn($r)=>$r['status']==='Proses'&&!$r['divalidasi_oleh']);

// Chart serapan
$chartStmt = $db->prepare(
    "SELECT a.kategori, a.jumlah AS anggaran, COALESCE(SUM(r.jumlah),0) AS realisasi
     FROM anggaran a
     LEFT JOIN realisasi r ON r.kategori=a.kategori AND r.status='Selesai' AND YEAR(r.tanggal)=?
     WHERE a.tahun=? GROUP BY a.kategori, a.jumlah"
);
$chartStmt->execute([$tahunF,$tahunF]);
$chartData = $chartStmt->fetchAll();

require_once __DIR__ . '/../includes/admin_layout.php';
?>

<!-- SERAPAN CHART -->
<div class="grid-2" style="margin-bottom:24px">
  <div class="card">
    <h3>Realisasi per Sektor <?= $tahunF ?></h3>
    <div style="height:240px"><canvas id="realBar"></canvas></div>
  </div>
  <div class="card">
    <h3>Detail Serapan Anggaran</h3>
    <?php foreach ($chartData as $c):
      $pct = $c['anggaran'] > 0 ? round(($c['realisasi']/$c['anggaran'])*100,1) : 0;
    ?>
    <div class="progress-item">
      <div class="progress-header">
        <span class="progress-label"><?= $c['kategori'] ?></span>
        <span class="progress-pct"><?= $pct ?>%</span>
      </div>
      <div class="progress-bar"><div class="progress-fill" style="width:<?= min($pct,100) ?>%"></div></div>
      <div class="progress-amounts"><span><?= rupiah($c['realisasi']) ?></span><span><?= rupiah($c['anggaran']) ?></span></div>
    </div>
    <?php endforeach; ?>

    <?php if (count($menunggu) > 0 && hasRole('kades')): ?>
    <div class="validasi-box" style="margin-top:12px">
      <h4>⚠️ <?= count($menunggu) ?> Transaksi Menunggu Validasi</h4>
      <p>Validasi semua realisasi dana yang sudah selesai agar dapat dipublikasikan.</p>
      <a href="?validasi_all=1" class="btn btn-primary btn-sm"
         data-confirm="Validasi semua realisasi yang menunggu?">✅ Validasi Semua</a>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- FORM TAMBAH/EDIT -->
<?php if (hasRole(['bendahara','sekdes','kades'])): ?>
<div class="card" style="margin-bottom:24px">
  <h3><?= $editData ? '✏️ Edit Realisasi' : '➕ Catat Realisasi Dana Baru' ?></h3>

  <?php if (!empty($errors)): ?>
  <div class="alert alert-error"><?= implode('<br>', array_map('clean', $errors)) ?></div>
  <?php endif; ?>

  <form method="POST" action="" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="action"     value="<?= $editData ? 'edit' : 'tambah' ?>">
    <?php if ($editData): ?><input type="hidden" name="id" value="<?= $editData['id'] ?>"><?php endif; ?>

    <div class="form-grid">
      <div class="form-group full">
        <label>Nama Kegiatan <span class="required">*</span></label>
        <input type="text" name="nama_kegiatan"
               value="<?= clean($editData['nama_kegiatan'] ?? '') ?>"
               placeholder="Contoh: Pembangunan Jalan Desa RT 05" required>
      </div>
      <div class="form-group">
        <label>Tanggal <span class="required">*</span></label>
        <input type="date" name="tanggal"
               value="<?= $editData['tanggal'] ?? date('Y-m-d') ?>" required>
      </div>
      <div class="form-group">
        <label>Kategori <span class="required">*</span></label>
        <select name="kategori" required>
          <option value="">— Pilih Kategori —</option>
          <?php foreach (['Infrastruktur','Pendidikan','Kesehatan','Administrasi','Pemberdayaan'] as $k):
            $sel = ($editData['kategori']??'')===$k?'selected':'';
          ?>
          <option value="<?= $k ?>" <?= $sel ?>><?= $k ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Jumlah (Rp) <span class="required">*</span></label>
        <input type="number" name="jumlah" min="1"
               value="<?= $editData['jumlah'] ?? '' ?>" required>
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status">
          <?php foreach (['Proses','Selesai','Batal'] as $s):
            $sel = ($editData['status']??'Proses')===$s?'selected':'';
          ?>
          <option value="<?= $s ?>" <?= $sel ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Upload Bukti (Opsional)</label>
        <input type="file" name="file_bukti" accept=".jpg,.jpeg,.png,.pdf">
        <div class="form-hint">Format: JPG, PNG, PDF. Maks 5MB.</div>
        <?php if (!empty($editData['file_bukti'])): ?>
        <div class="form-hint">File saat ini: <a href="<?= BASE_URL.'/public/uploads/'.$editData['file_bukti'] ?>" target="_blank">Lihat Bukti</a></div>
        <?php endif; ?>
      </div>
      <div class="form-group full">
        <label>Keterangan</label>
        <textarea name="keterangan"><?= clean($editData['keterangan'] ?? '') ?></textarea>
      </div>
      <?php if (hasRole('kades')): ?>
      <div class="form-group full">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
          <input type="checkbox" name="is_publik" value="1" <?= ($editData['is_publik']??0)?'checked':'' ?>>
          <span>Publikasikan ke halaman publik (hanya Kepala Desa)</span>
        </label>
      </div>
      <?php endif; ?>
    </div>

    <div style="display:flex;gap:10px">
      <button type="submit" class="btn btn-primary">
        <?= $editData ? '💾 Simpan Perubahan' : '➕ Catat Realisasi' ?>
      </button>
      <?php if ($editData): ?>
      <a href="<?= BASE_URL ?>/admin/realisasi_dana.php" class="btn btn-warning">✕ Batal</a>
      <?php endif; ?>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- DAFTAR REALISASI -->
<div class="card">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
    <h3 style="border:none;padding:0;margin:0">Daftar Realisasi</h3>
    <form method="GET" style="display:flex;gap:8px;align-items:center">
      <select name="tahun" onchange="this.form.submit()" style="padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:.85rem">
        <?php foreach ([2026,2025,2024] as $y): ?>
        <option value="<?= $y ?>" <?= $tahunF==$y?'selected':'' ?>><?= $y ?></option>
        <?php endforeach; ?>
      </select>
      <select name="kat" onchange="this.form.submit()" style="padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:.85rem">
        <option value="">Semua Kategori</option>
        <?php foreach (['Infrastruktur','Pendidikan','Kesehatan','Administrasi','Pemberdayaan'] as $k): ?>
        <option value="<?= $k ?>" <?= $katF===$k?'selected':'' ?>><?= $k ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th><th>Tanggal</th><th>Nama Kegiatan</th><th>Kategori</th>
          <th>Jumlah</th><th>Status</th><th>Validasi</th><th>Publik</th><th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($list as $i => $r):
          $bc = ['Selesai'=>'badge-success','Proses'=>'badge-warning','Batal'=>'badge-danger'];
        ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
          <td>
            <strong><?= clean($r['nama_kegiatan']) ?></strong>
            <?php if ($r['file_bukti']): ?>
            <br><a href="<?= BASE_URL.'/public/uploads/'.$r['file_bukti'] ?>" target="_blank" style="font-size:.74rem;color:var(--primary-light)">📎 Lihat Bukti</a>
            <?php endif; ?>
          </td>
          <td><?= $r['kategori'] ?></td>
          <td><?= rupiah($r['jumlah']) ?></td>
          <td><span class="badge <?= $bc[$r['status']]??'badge-info' ?>"><?= $r['status'] ?></span></td>
          <td style="font-size:.78rem">
            <?php if ($r['validator_nama']): ?>
            <span style="color:var(--success)">✅ <?= clean($r['validator_nama']) ?></span>
            <?php elseif ($r['status']==='Selesai' && hasRole('kades')): ?>
            <a href="?validasi=<?= $r['id'] ?>" class="btn btn-success btn-sm">Validasi</a>
            <?php else: ?>
            <span style="color:var(--text-muted)">Menunggu</span>
            <?php endif; ?>
          </td>
          <td><?= $r['is_publik'] ? '<span class="badge badge-success">Publik</span>' : '<span class="badge badge-info">Draft</span>' ?></td>
          <td>
            <div class="action-btns">
              <a href="?edit=<?= $r['id'] ?>" class="btn btn-warning btn-sm">✏️</a>
              <a href="?hapus=<?= $r['id'] ?>" class="btn btn-danger btn-sm"
                 data-confirm="Hapus transaksi ini?">🗑️</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($list)): ?>
        <tr><td colspan="9" style="text-align:center;padding:32px;color:var(--text-muted)">Belum ada data realisasi.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_layout_close.php'; ?>

<script>
makeBarChart('realBar',
  <?= json_encode(array_column($chartData,'kategori')) ?>,
  [
    { label:'Anggaran',  data:<?= json_encode(array_map(fn($r)=>(int)$r['anggaran'],  $chartData)) ?>, backgroundColor:'#1a3a6b', borderRadius:6 },
    { label:'Realisasi', data:<?= json_encode(array_map(fn($r)=>(int)$r['realisasi'], $chartData)) ?>, backgroundColor:'#2a9d8f', borderRadius:6 }
  ]
);
</script>
