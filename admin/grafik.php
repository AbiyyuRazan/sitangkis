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

require_once __DIR__ . '/../includes/admin_layout.php';
?>

<div style="display:flex;justify-content:flex-end;margin-bottom:20px">
  <form method="GET" style="display:flex;gap:8px;align-items:center">
    <label style="font-size:.88rem;color:var(--text-muted)">Tahun:</label>
    <select name="tahun" onchange="this.form.submit()" style="padding:9px 14px;border:1.5px solid var(--border);border-radius:9px;font-family:inherit;font-size:.88rem">
      <?php foreach ([2026,2025,2024] as $y): ?>
      <option value="<?= $y ?>" <?= $year==$y?'selected':'' ?>><?= $y ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<div class="grid-2">
  <div class="card">
    <h3>Anggaran vs Realisasi per Sektor</h3>
    <div style="height:280px"><canvas id="g-bar"></canvas></div>
  </div>
  <div class="card">
    <h3>Komposisi Sumber Pendapatan</h3>
    <div style="height:280px"><canvas id="g-pie"></canvas></div>
  </div>
  <div class="card">
    <h3>Tren Realisasi Bulanan <?= $year ?></h3>
    <div style="height:280px"><canvas id="g-line"></canvas></div>
  </div>
  <div class="card">
    <h3>Serapan Anggaran per Sektor</h3>
    <?php foreach ($barData as $c):
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
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_layout_close.php'; ?>

<script>
const bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
makeBarChart('g-bar',
  <?= json_encode(array_column($barData,'kategori')) ?>,
  [
    {label:'Anggaran',  data:<?= json_encode(array_map(fn($r)=>(int)$r['anggaran'],  $barData)) ?>, backgroundColor:'#1a3a6b', borderRadius:6},
    {label:'Realisasi', data:<?= json_encode(array_map(fn($r)=>(int)$r['realisasi'], $barData)) ?>, backgroundColor:'#2a9d8f', borderRadius:6}
  ]
);
makePieChart('g-pie',
  <?= json_encode(array_column($pieData,'sumber')) ?>,
  <?= json_encode(array_map(fn($r)=>(int)$r['jumlah'], $pieData)) ?>,
  ['#1a3a6b','#2a9d8f','#f4a261']
);
makeLineChart('g-line', bulan, <?= json_encode(array_values($lineData)) ?>.map(v=>Math.round(v/1e6)));
</script>
