<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle  = 'Grafik Transparansi';
$activePage = 'grafik';
$db = getDB();

$tahunDipilih = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$desa = $db->query("SELECT * FROM desa_info LIMIT 1")->fetch();

// 1. DATA GRAFIK BATANG (ANGGARAN VS REALISASI)
$barStmt = $db->prepare("SELECT a.kategori, a.jumlah AS anggaran, COALESCE(SUM(r.jumlah),0) AS realisasi
  FROM anggaran a LEFT JOIN realisasi r ON r.kategori=a.kategori AND r.status='Selesai' AND YEAR(r.tanggal)=?
  WHERE a.tahun=? GROUP BY a.kategori, a.jumlah");
$barStmt->execute([$tahunDipilih, $tahunDipilih]);
$barData = $barStmt->fetchAll();

// 2. DATA GRAFIK PIE (SUMBER PENDAPATAN)
$pieStmt = $db->prepare("SELECT sumber, jumlah FROM sumber_pendapatan WHERE tahun=:tahun");
$pieStmt->execute(['tahun' => $tahunDipilih]);
$pieRows = $pieStmt->fetchAll();

// 3. DATA TREN REALISASI BULANAN
$trendStmt = $db->prepare("SELECT MONTH(tanggal) as bulan, SUM(jumlah) as total 
  FROM realisasi WHERE status='Selesai' AND YEAR(tanggal)=:tahun 
  GROUP BY MONTH(tanggal) ORDER BY bulan ASC");
$trendStmt->execute(['tahun' => $tahunDipilih]);
$trendData = $trendStmt->fetchAll();

$bulanan = array_fill(1, 12, 0);
foreach ($trendData as $row) {
    $bulanan[(int)$row['bulan']] = (int)$row['total'];
}

// 4. DATA SERAPAN ANGGARAN PER SEKTOR (PROGRESS BARS)
$serapanData = [];
$totalAnggaranSektor = 0;
$totalRealisasiSektor = 0;

foreach ($barData as $b) {
    $anggaran = (int)$b['anggaran'];
    $realisasi = (int)$b['realisasi'];
    $persen = $anggaran > 0 ? round(($realisasi / $anggaran) * 100, 1) : 0;
    
    $totalAnggaranSektor += $anggaran;
    $totalRealisasiSektor += $realisasi;

    $serapanData[] = [
        'kategori' => $b['kategori'],
        'anggaran' => $anggaran,
        'realisasi' => $realisasi,
        'persen' => $persen
    ];
}

$totalPersenSerapan = $totalAnggaranSektor > 0 ? round(($totalRealisasiSektor / $totalAnggaranSektor) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?> — <?= APP_NAME ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>

<style>
/* GLOBAL SPECIFICATION */
*, *::before, *::after {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}
html, body {
  height: 100%;
}
body {
  font-family: 'Inter', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  background: #b8b8b8; /* BACKGROUND MATTE GREY SESUAI LAPORAN PUBLIK */
  color: #ffffff; /* FONT DIUBAH MENJADI PUTIH AGAR KONTRAS TINGGI DI ATAS KACA ABU KEBIRUAN */
  display: flex;
  flex-direction: column;
}

/* MASTER THEME: SLATE STEEL BLUE GLASS (SENADA DENGAN IMAGE_3CDA7C.JPG) */
.liquid-glass {
  background: rgba(85, 116, 153, 0.75); 
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
  border: none;
  box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.2), 0 8px 24px rgba(0, 0, 0, 0.1);
  position: relative;
  overflow: hidden;
}
.liquid-glass::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: inherit;
  padding: 1px;
  background: linear-gradient(180deg,
    rgba(255, 255, 255, 0.4) 0%, 
    rgba(255, 255, 255, 0.05) 40%,
    rgba(0, 0, 0, 0.1) 100%);
  -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  pointer-events: none;
}

/* NAVBAR HEADER */
.header-nav-section {
  width: 100%;
  padding: 24px 16px 12px 16px;
}
@media (min-width: 768px) { .header-nav-section { padding: 24px 48px 12px 48px; } }
@media (min-width: 1024px) { .header-nav-section { padding: 24px 64px 12px 64px; } }

.nav-bar {
  border-radius: 12px;
  padding: 8px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 54px;
}
.nav-brand {
  font-size: 1.35rem;
  font-weight: 700;
  letter-spacing: -0.03em;
  text-decoration: none;
  color: #ffffff;
}
.nav-links {
  display: flex;
  gap: 32px;
  align-items: center;
}
@media (max-width: 767px) { .nav-links { display: none; } }

.nav-a {
  color: rgba(255, 255, 255, 0.85);
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
  transition: color 0.2s ease;
}
.nav-a:hover, .nav-a.active { color: #ffffff; }

.nav-btn {
  background: #ffffff;
  color: #3b587c;
  padding: 8px 20px;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  text-decoration: none;
}

/* CONTENT CONTAINER */
.content-wrapper {
  flex: 1 0 auto; 
  padding: 0 16px 48px 16px;
}
@media (min-width: 768px) { .content-wrapper { padding: 0 48px 48px 48px; } }
@media (min-width: 1024px) { .content-wrapper { padding: 0 64px 64px 64px; } }

.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-top: 24px;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 16px;
}
.page-title-box h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b; /* Abu-abu gelap elegan agar judul pop-out */
  letter-spacing: -0.03em;
  margin-bottom: 4px;
}
.page-title-box p {
  font-size: 0.9rem;
  color: #475569;
}

.filter-select {
  background: rgba(255, 255, 255, 0.4);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  padding: 8px 16px;
  border-radius: 8px;
  color: #0f172a;
  font-family: inherit;
  font-size: 0.875rem;
  font-weight: 600;
  outline: none;
  cursor: pointer;
}
.filter-select option {
  background: #557499;
  color: #ffffff;
}

/* CHARTS LAYOUT SYSTEM */
.charts-main-grid {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 24px;
  margin-bottom: 24px;
}
@media (min-width: 768px) { .charts-main-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

.chart-card {
  border-radius: 14px;
  padding: 24px;
  display: flex;
  flex-direction: column;
}
.chart-card-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 20px;
}
.chart-container-box {
  width: 100%;
  height: 280px;
  position: relative;
}

/* INSIGHT TEXT AREA (PENJELASAN DINAMIS BEBAS EMOJI) */
.insight-box {
  margin-top: auto;
  padding-top: 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
  font-size: 0.85rem;
  line-height: 1.5;
  color: #f1f5f9;
}
.insight-title {
  font-weight: 700;
  color: #ffffff;
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 6px;
}

/* PROGRESS BAR STYLING */
.progress-container {
  margin-bottom: 20px;
}
.progress-meta {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
  font-weight: 600;
  color: #ffffff;
  margin-bottom: 6px;
}
.progress-meta .percent-val {
  color: #ffffff;
}
.progress-bar-bg {
  width: 100%;
  height: 10px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 20px;
  overflow: hidden;
  margin-bottom: 4px;
}
.progress-bar-fill {
  height: 100%;
  background: #ffffff; 
  border-radius: 20px;
}
.progress-subtext {
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
  color: #e2e8f0;
}

/* FIXED FOOTER */
.footer {
  flex-shrink: 0; 
  background: rgba(30, 41, 59, 0.1);
  color: #475569;
  padding: 32px 16px;
  border-top: 1px solid rgba(15, 23, 42, 0.05);
}
@media (min-width: 768px) { .footer { padding: 32px 48px; } }
@media (min-width: 1024px) { .footer { padding: 32px 64px; } }
</style>
</head>
<body>

<div class="header-nav-section">
  <nav class="nav-bar liquid-glass">
    <a class="nav-brand" href="<?= BASE_URL ?>/index.php">SITANGKIS</a>
    <div class="nav-links">
      <a class="nav-a" href="<?= BASE_URL ?>/index.php">Beranda</a>
      <a class="nav-a" href="<?= BASE_URL ?>/pages/laporan.php">Laporan</a>
      <a class="nav-a active" href="<?= BASE_URL ?>/pages/grafik.php">Grafik</a>
      <a class="nav-a" href="<?= BASE_URL ?>/pages/tentang.php">Tentang</a>
    </div>
    <a class="nav-btn" href="<?= BASE_URL ?>/login.php">Login Admin</a>
  </nav>
</div>

<div class="content-wrapper">
  
  <div class="page-header">
    <div class="page-title-box">
      <h1>Visualisasi Keuangan</h1>
      <p>Representasi visual data keuangan desa untuk kemudahan pemahaman masyarakat</p>
    </div>
    
    <form action="" method="GET">
      <select name="tahun" class="filter-select" onchange="this.form.submit()">
        <?php 
        $yearNow = (int)date('Y');
        for($y = $yearNow; $y >= $yearNow - 4; $y--): 
          $sel = ($y === $tahunDipilih) ? 'selected' : '';
        ?>
          <option value="<?= $y ?>" <?= $sel ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
    </form>
  </div>

  <div class="charts-main-grid">
    
    <div class="chart-card liquid-glass">
      <h2 class="chart-card-title">Anggaran vs Realisasi per Sektor</h2>
      <div class="chart-container-box">
        <canvas id="barChart"></canvas>
      </div>
      <div class="insight-box" id="barInsight">
        <div class="insight-title">Analisis Pagu Sektor:</div>
        <p id="barInsightText">Memproses data...</p>
      </div>
    </div>

    <div class="chart-card liquid-glass">
      <h2 class="chart-card-title">Komposisi Sumber Pendapatan</h2>
      <div class="chart-container-box">
        <canvas id="pieChart"></canvas>
      </div>
      <div class="insight-box" id="pieInsight">
        <div class="insight-title">Analisis Pendapatan:</div>
        <p id="pieInsightText">Memproses data...</p>
      </div>
    </div>

    <div class="chart-card liquid-glass">
      <h2 class="chart-card-title">Tren Realisasi Bulanan <?= $tahunDipilih ?></h2>
      <div class="chart-container-box">
        <canvas id="lineChart"></canvas>
      </div>
      <div class="insight-box" id="lineInsight">
        <div class="insight-title">Analisis Tren Bulanan:</div>
        <p id="lineInsightText">Memproses data...</p>
      </div>
    </div>

    <div class="chart-card liquid-glass">
      <h2 class="chart-card-title">Serapan Anggaran per Sektor</h2>
      <div style="flex: 1; display: flex; flex-direction: column; justify-content: center;">
        <?php foreach($serapanData as $s): ?>
          <div class="progress-container">
            <div class="progress-meta">
              <span><?= htmlspecialchars($s['kategori']) ?></span>
              <span class="percent-val"><?= $s['persen'] ?>%</span>
            </div>
            <div class="progress-bar-bg">
              <div class="progress-bar-fill" style="width: <?= min($s['persen'], 100) ?>%;"></div>
            </div>
            <div class="progress-subtext">
              <span>Realisasi: <?= rupiah($s['realisasi']) ?></span>
              <span>Anggaran: <?= rupiah($s['anggaran']) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
        
        <div class="insight-box" style="margin-top: 10px;">
          <div class="insight-title">Kesimpulan Serapan Dana:</div>
          <p>Total serapan APBDes Ampang Pulai pada tahun <?= $tahunDipilih ?> saat ini menyentuh angka <strong><?= $totalPersenSerapan ?>%</strong>. 
          <?php if($totalPersenSerapan >= 75): ?>
             Kinerja pengelolaan dana desa dinilai **Sangat Efektif** dan berjalan maksimal mendekati target akhir tahun anggaran.
          <?php elseif($totalPersenSerapan >= 40): ?>
             Penyerapan dana masuk kategori **Cukup Baik**, beberapa program kerja sedang dalam masa konstruksi atau administrasi berjalan.
          <?php else: ?>
             Laju penyerapan tergolong **Rendah**. Diperlukan evaluasi berkala pada unit pelaksana kegiatan agar anggaran tidak mengendap di sisa tahun.
          <?php endif; ?>
          </p>
        </div>
      </div>
    </div>

  </div>

</div>

<footer class="footer">
  <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
    <div>
      <strong style="color:#1e293b;">SITANGKIS</strong> — Desa Ampang Pulai
      <p style="font-size:0.8rem; margin-top:4px;">© 2026 Pemerintah Desa Ampang Pulai. Kelompok 6 — 4A Informatika UNSIKA.</p>
    </div>
  </div>
</footer>

<script>
const commonFont = { family: 'Inter', size: 12, weight: '500' };

const barRawData = <?= json_encode($barData) ?>;
const pieRawData = <?= json_encode($pieRows) ?>;
const lineRawData = <?= json_encode(array_values($bulanan)) ?>;

// 1. ENGINE BAR CHART (Diselaraskan dengan warna kontras asli fungsional dashboard saat ini)
new Chart(document.getElementById('barChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($barData, 'kategori')) ?>,
    datasets: [
      { 
        label: 'Anggaran', 
        data: <?= json_encode(array_map(fn($r) => (int)$r['anggaran'], $barData)) ?>, 
        backgroundColor: '#1a3a6b', // DEEP NAVY ASLI
        borderRadius: 4 
      },
      { 
        label: 'Realisasi', 
        data: <?= json_encode(array_map(fn($r) => (int)$r['realisasi'], $barData)) ?>, 
        backgroundColor: '#2a9d8f', // TEAL ASLI
        borderRadius: 4 
      }
    ]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { labels: { color: '#ffffff', font: commonFont }, position: 'bottom' } },
    scales: {
      y: { ticks: { color: '#ffffff', font: { family: 'Inter' }, callback: v => 'Rp ' + (v/1e6) + 'jt' }, grid: { color: 'rgba(255, 255, 255, 0.15)' } },
      x: { ticks: { color: '#ffffff', font: { family: 'Inter' } }, grid: { display: false } }
    }
  }
});

// 2. ENGINE PIE CHART (Warna Fungsional Dashboard Saat Ini)
new Chart(document.getElementById('pieChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_column($pieRows, 'sumber')) ?>,
    datasets: [{ 
      data: <?= json_encode(array_map(fn($r) => (int)$r['jumlah'], $pieRows)) ?>, 
      backgroundColor: ['#1a3a6b', '#2a9d8f', '#f4a261', '#e63946'], // NAVY, TEAL, ORANGE, CORAL ASLI
      borderWidth: 0 
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { labels: { color: '#ffffff', font: commonFont }, position: 'bottom' } },
    cutout: '72%'
  }
});

// 3. ENGINE LINE CHART (Warna Fungsional Dashboard Saat Ini)
new Chart(document.getElementById('lineChart'), {
  type: 'line',
  data: {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
    datasets: [{ 
      label: 'Realisasi (jt)', 
      data: lineRawData, 
      borderColor: '#1a3a6b', 
      backgroundColor: 'rgba(26, 58, 107, 0.05)', 
      fill: true, 
      tension: 0.4, 
      pointBackgroundColor: '#ffffff', 
      pointRadius: 4 
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { labels: { color: '#ffffff', font: commonFont }, position: 'bottom' } },
    scales: {
      y: { ticks: { color: '#ffffff', font: { family: 'Inter' }, callback: v => 'Rp ' + (v/1e6) + 'jt' }, grid: { color: 'rgba(255, 255, 255, 0.15)' } },
      x: { ticks: { color: '#ffffff', font: { family: 'Inter' } }, grid: { display: false } }
    }
  }
});

// INSIGHT GENERATOR DINAMIS (BEBAS EMOJI)
document.addEventListener("DOMContentLoaded", function() {
  if (barRawData.length > 0) {
    let tertinggi = barRawData.reduce((max, p) => parseInt(p.anggaran) > parseInt(max.anggaran) ? p : max, barRawData[0]);
    document.getElementById("barInsightText").innerHTML = `Alokasi pagu anggaran terbesar difokuskan pada sektor <strong>${tertinggi.kategori}</strong> dengan nilai perencanaan mencapai Rp ${parseInt(tertinggi.anggaran).toLocaleString('id-ID')}.`;
  } else {
    document.getElementById("barInsightText").innerText = "Belum ada rekapitulasi realisasi anggaran pada sektor kerja tahun ini.";
  }

  if (pieRawData.length > 0) {
    let danaTerbesar = pieRawData.reduce((max, p) => parseInt(p.jumlah) > parseInt(max.jumlah) ? p : max, pieRawData[0]);
    document.getElementById("pieInsightText").innerHTML = `Struktur APBD desa didominasi oleh pasokan dana dari <strong>${danaTerbesar.sumber}</strong> dengan kontribusi pemasukan sebesar Rp ${parseInt(danaTerbesar.jumlah).toLocaleString('id-ID')}.`;
  } else {
    document.getElementById("pieInsightText").innerText = "Data rincian sumber pendapatan desa belum terinput pada server database.";
  }

  let maxDana = 0;
  let bulanPuncak = -1;
  const namaBulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
  
  lineRawData.forEach((val, idx) => {
    if (val > maxDana) {
      maxDana = val;
      bulanPuncak = idx;
    }
  });

  if (maxDana > 0) {
    document.getElementById("lineInsightText").innerHTML = `Grafik menunjukkan aktivitas realisasi pengeluaran kas desa tertinggi terjadi pada bulan <strong>${namaBulan[bulanPuncak]}</strong> dengan akumulasi pencairan sebesar Rp ${maxDana.toLocaleString('id-ID')}.`;
  } else {
    document.getElementById("lineInsightText").innerText = "Belum terdeteksi adanya pergerakan arus kas keluar pada bulan-bulan di tahun ini.";
  }
});
</script>
</body>
</html>