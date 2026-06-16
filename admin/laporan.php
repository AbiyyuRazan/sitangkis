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

// Export CSV
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan-admin-'.$tahun.'.csv');
    $out = fopen('php://output','w');
    fputcsv($out, ['No','Tanggal','Nama Kegiatan','Kategori','Jumlah','Status','Publik','Dibuat Oleh']);
    foreach ($rows as $i=>$r) fputcsv($out,[
        $i+1, date('d/m/Y',strtotime($r['tanggal'])),
        $r['nama_kegiatan'], $r['kategori'], $r['jumlah'],
        $r['status'], $r['is_publik']?'Ya':'Tidak', $r['user_nama']
    ]);
    fclose($out); exit;
}

// Summary per kategori
$summary = $db->prepare(
    "SELECT kategori, SUM(jumlah) AS total, COUNT(*) AS jml
     FROM realisasi WHERE YEAR(tanggal)=? GROUP BY kategori"
);
$summary->execute([$tahun]);
$summaryData = $summary->fetchAll();

require_once __DIR__ . '/../includes/admin_layout.php';
?>

<!-- SUMMARY CARDS -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:24px">
  <?php
  $colors = ['Infrastruktur'=>'#dbeafe','Pendidikan'=>'#d1fae5','Kesehatan'=>'#fef3c7','Administrasi'=>'#f3e8ff','Pemberdayaan'=>'#fee2e2'];
  foreach ($summaryData as $s): ?>
  <div style="background:#fff;border-radius:12px;padding:18px;border:1px solid var(--border);box-shadow:var(--shadow)">
    <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:4px"><?= $s['kategori'] ?></div>
    <div style="font-family:'Plus Jakarta Sans',sans-serif;font-size:1.1rem;font-weight:800"><?= rupiah($s['total']) ?></div>
    <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px"><?= $s['jml'] ?> kegiatan</div>
  </div>
  <?php endforeach; ?>
</div>

<!-- FILTER -->
<div style="margin-bottom:18px">
  <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <select name="tahun" onchange="this.form.submit()" style="padding:9px 12px;border:1.5px solid var(--border);border-radius:9px;font-family:inherit;font-size:.87rem">
      <?php foreach ([2026,2025,2024] as $y): ?>
      <option value="<?= $y ?>" <?= $tahun==$y?'selected':'' ?>><?= $y ?></option>
      <?php endforeach; ?>
    </select>
    <select name="kat" onchange="this.form.submit()" style="padding:9px 12px;border:1.5px solid var(--border);border-radius:9px;font-family:inherit;font-size:.87rem">
      <option value="">Semua Kategori</option>
      <?php foreach (['Infrastruktur','Pendidikan','Kesehatan','Administrasi','Pemberdayaan'] as $k): ?>
      <option value="<?= $k ?>" <?= $kat===$k?'selected':'' ?>><?= $k ?></option>
      <?php endforeach; ?>
    </select>
    <input type="search" name="cari" value="<?= clean($cari) ?>" placeholder="🔍 Cari..."
           style="padding:9px 14px;border:1.5px solid var(--border);border-radius:9px;font-family:inherit;font-size:.87rem">
    <button type="submit" class="btn btn-primary btn-sm">Cari</button>
    <a href="?<?= http_build_query(array_merge($_GET,['export'=>'1'])) ?>" class="btn btn-success btn-sm">⬇️ Export CSV</a>
    <?php if ($cari||$kat): ?>
    <a href="?tahun=<?= $tahun ?>" class="btn btn-warning btn-sm">✕ Reset</a>
    <?php endif; ?>
  </form>
</div>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <h3 style="border:none;padding:0;margin:0">Semua Transaksi <?= $tahun ?></h3>
    <span style="font-size:.82rem;color:var(--text-muted)"><?= count($rows) ?> record</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th><th>Tanggal</th><th>Nama Kegiatan</th><th>Kategori</th>
          <th>Jumlah</th><th>Status</th><th>Publik</th><th>Dibuat Oleh</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $i=>$r):
          $bc=['Selesai'=>'badge-success','Proses'=>'badge-warning','Batal'=>'badge-danger'];
        ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
          <td><strong><?= clean($r['nama_kegiatan']) ?></strong></td>
          <td><?= $r['kategori'] ?></td>
          <td><?= rupiah($r['jumlah']) ?></td>
          <td><span class="badge <?= $bc[$r['status']]??'badge-info' ?>"><?= $r['status'] ?></span></td>
          <td><?= $r['is_publik']?'<span class="badge badge-success">Publik</span>':'<span class="badge badge-info">Draft</span>' ?></td>
          <td><?= clean($r['user_nama']??'-') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
        <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text-muted)">Tidak ada data.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_layout_close.php'; ?>
