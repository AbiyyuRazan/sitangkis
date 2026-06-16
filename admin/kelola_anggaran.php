<?php
// admin/kelola_anggaran.php — Kelola Anggaran (Bendahara & Sekdes)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn()) redirect(BASE_URL . '/login.php');
if (!hasRole(['bendahara','sekdes'])) {
    setFlash('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect(BASE_URL . '/admin/index.php');
}

$db = getDB();
$adminPage  = 'kelola';
$adminTitle = 'Kelola Anggaran';

// ── HANDLE POST (Tambah / Edit) ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action      = $_POST['action'] ?? 'tambah';
    $nama        = trim($_POST['nama_program'] ?? '');
    $kategori    = $_POST['kategori'] ?? '';
    $jumlah      = (int)str_replace(['.',',',' '], '', $_POST['jumlah'] ?? '');
    $tahun       = (int)($_POST['tahun'] ?? date('Y'));
    $keterangan  = trim($_POST['keterangan'] ?? '');

    $errors = [];
    if (!$nama)     $errors[] = 'Nama program wajib diisi.';
    if (!$kategori) $errors[] = 'Kategori wajib dipilih.';
    if ($jumlah <= 0) $errors[] = 'Jumlah harus lebih dari 0.';

    if (empty($errors)) {
        if ($action === 'edit' && !empty($_POST['id'])) {
            $stmt = $db->prepare(
                "UPDATE anggaran SET nama_program=?,kategori=?,jumlah=?,tahun=?,keterangan=? WHERE id=?"
            );
            $stmt->execute([$nama,$kategori,$jumlah,$tahun,$keterangan,(int)$_POST['id']]);
            setFlash('success', 'Anggaran berhasil diperbarui.');
        } else {
            $stmt = $db->prepare(
                "INSERT INTO anggaran (nama_program,kategori,jumlah,tahun,keterangan,dibuat_oleh) VALUES (?,?,?,?,?,?)"
            );
            $stmt->execute([$nama,$kategori,$jumlah,$tahun,$keterangan,$_SESSION['user_id']]);
            setFlash('success', 'Anggaran berhasil ditambahkan.');
        }
        redirect(BASE_URL . '/admin/kelola_anggaran.php');
    }
}

// ── HANDLE HAPUS ──────────────────────────────────────────────
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $db->prepare("DELETE FROM anggaran WHERE id=?")->execute([(int)$_GET['hapus']]);
    setFlash('success', 'Anggaran berhasil dihapus.');
    redirect(BASE_URL . '/admin/kelola_anggaran.php');
}

// ── DATA EDIT ─────────────────────────────────────────────────
$editData = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editData = $db->prepare("SELECT * FROM anggaran WHERE id=?")->execute([(int)$_GET['edit']]);
    $s = $db->prepare("SELECT * FROM anggaran WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $editData = $s->fetch();
}

// ── LIST ──────────────────────────────────────────────────────
$tahunFilter = (int)($_GET['tahun'] ?? date('Y'));
$anggaranList = $db->prepare(
    "SELECT a.*, u.nama AS dibuat_nama FROM anggaran a
     LEFT JOIN users u ON u.id=a.dibuat_oleh
     WHERE a.tahun=? ORDER BY a.kategori, a.nama_program"
);
$anggaranList->execute([$tahunFilter]);
$list = $anggaranList->fetchAll();

$totalAnggaran = array_sum(array_column($list,'jumlah'));

require_once __DIR__ . '/../includes/admin_layout.php';
?>

<!-- FORM TAMBAH/EDIT -->
<div class="card" style="margin-bottom:24px">
  <h3><?= $editData ? '✏️ Edit Anggaran' : '➕ Tambah Anggaran Baru' ?></h3>

  <?php if (!empty($errors)): ?>
  <div class="alert alert-error"><?= implode('<br>', array_map('clean', $errors)) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="action"     value="<?= $editData ? 'edit' : 'tambah' ?>">
    <?php if ($editData): ?>
    <input type="hidden" name="id" value="<?= $editData['id'] ?>">
    <?php endif; ?>

    <div class="form-grid">
      <div class="form-group full">
        <label>Nama Program / Kegiatan <span class="required">*</span></label>
        <input type="text" name="nama_program"
               value="<?= clean($editData['nama_program'] ?? $_POST['nama_program'] ?? '') ?>"
               placeholder="Contoh: Pembangunan Jalan Desa RT 05" required>
      </div>
      <div class="form-group">
        <label>Kategori <span class="required">*</span></label>
        <select name="kategori" required>
          <option value="">— Pilih Kategori —</option>
          <?php foreach (['Infrastruktur','Pendidikan','Kesehatan','Administrasi','Pemberdayaan'] as $k):
            $sel = ($editData['kategori'] ?? '') === $k ? 'selected' : '';
          ?>
          <option value="<?= $k ?>" <?= $sel ?>><?= $k ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Jumlah Anggaran (Rp) <span class="required">*</span></label>
        <input type="number" name="jumlah" min="1"
               value="<?= $editData['jumlah'] ?? '' ?>"
               placeholder="0" required>
        <div class="form-hint">Contoh: 125000000 untuk Rp 125.000.000</div>
      </div>
      <div class="form-group">
        <label>Tahun Anggaran <span class="required">*</span></label>
        <select name="tahun">
          <?php foreach ([2026,2025,2024] as $y): ?>
          <option value="<?= $y ?>" <?= ($editData['tahun'] ?? date('Y')) == $y ? 'selected':'' ?>><?= $y ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group full">
        <label>Keterangan (Opsional)</label>
        <textarea name="keterangan" placeholder="Deskripsi tambahan..."><?= clean($editData['keterangan'] ?? '') ?></textarea>
      </div>
    </div>

    <div style="display:flex;gap:10px">
      <button type="submit" class="btn btn-primary">
        <?= $editData ? '💾 Simpan Perubahan' : '➕ Tambah Anggaran' ?>
      </button>
      <?php if ($editData): ?>
      <a href="<?= BASE_URL ?>/admin/kelola_anggaran.php" class="btn btn-warning">✕ Batal</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- DAFTAR ANGGARAN -->
<div class="card">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
    <h3 style="border:none;padding:0;margin:0">Daftar Anggaran</h3>
    <div style="display:flex;gap:10px;align-items:center">
      <form method="GET" style="display:flex;gap:8px">
        <select name="tahun" onchange="this.form.submit()"
                style="padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:.85rem">
          <?php foreach ([2026,2025,2024] as $y): ?>
          <option value="<?= $y ?>" <?= $tahunFilter==$y?'selected':'' ?>><?= $y ?></option>
          <?php endforeach; ?>
        </select>
      </form>
      <span style="font-size:.82rem;color:var(--text-muted)">Total: <strong><?= rupiah($totalAnggaran) ?></strong></span>
    </div>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Nama Program</th>
          <th>Kategori</th>
          <th>Jumlah</th>
          <th>Tahun</th>
          <th>Dibuat Oleh</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($list as $i => $a): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><strong><?= clean($a['nama_program']) ?></strong>
            <?php if ($a['keterangan']): ?>
            <div style="font-size:.75rem;color:var(--text-muted)"><?= clean(substr($a['keterangan'],0,60)) ?>...</div>
            <?php endif; ?>
          </td>
          <td><?= $a['kategori'] ?></td>
          <td><?= rupiah($a['jumlah']) ?></td>
          <td><?= $a['tahun'] ?></td>
          <td><?= clean($a['dibuat_nama'] ?? '-') ?></td>
          <td>
            <div class="action-btns">
              <a href="?edit=<?= $a['id'] ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
              <a href="?hapus=<?= $a['id'] ?>" class="btn btn-danger btn-sm"
                 data-confirm="Hapus anggaran '<?= clean($a['nama_program']) ?>'?">🗑️ Hapus</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($list)): ?>
        <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--text-muted)">Belum ada data anggaran untuk tahun <?= $tahunFilter ?>.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_layout_close.php'; ?>
