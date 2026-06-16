<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

// FIX PERMANENT: Menghapus proteksi isLoggedIn() agar warga umum tidak terlempar ke login.php
$pageTitle  = 'Beranda';
$activePage = 'home';
$db = getDB();
$year = date('Y');

$desa = $db->query("SELECT * FROM desa_info LIMIT 1")->fetch();

// SINKRONISASI MAKRO KAS PUBLIC: Total anggaran masuk hanya membaca Pagu yang sudah disetujui Kades
$totalAnggaran  = (int)$db->query("SELECT SUM(jumlah) FROM anggaran WHERE tahun=$year AND is_validasi=1")->fetchColumn();
$totalRealisasi = (int)$db->query("SELECT SUM(jumlah) FROM realisasi WHERE YEAR(tanggal)=$year AND status='Selesai'")->fetchColumn();
$serapan        = $totalAnggaran > 0 ? round(($totalRealisasi/$totalAnggaran)*100,1) : 0;

$recent = $db->query("SELECT * FROM realisasi WHERE is_publik=1 ORDER BY tanggal DESC LIMIT 6")->fetchAll();

$barData = [];
try {
    // FIXED BUG DOUBLE KATEGORI: Menggunakan SUM(a.jumlah) dan GROUP BY murni pada a.kategori
    $barStmt = $db->prepare(
      "SELECT a.kategori, SUM(a.jumlah) AS anggaran, 
       COALESCE((SELECT SUM(r.jumlah) FROM realisasi r WHERE r.kategori = a.kategori AND r.status = 'Selesai' AND YEAR(r.tanggal) = ?), 0) AS realisasi
       FROM anggaran a
       WHERE a.tahun=? AND a.is_validasi=1 GROUP BY a.kategori"
    );
    $barStmt->execute([$year, $year]);
    $barData = $barStmt->fetchAll();
} catch (Exception $e) {
    $barData = [];
}

$pieRows = [];
try {
    // SINKRONISASI GRAFIK LINGKARAN BERANDA: Membaca nama program dana masuk dinamis dari database yang sudah valid
    $pieRows = $db->prepare("SELECT nama_program AS sumber, jumlah FROM anggaran WHERE tahun=? AND is_validasi=1");
    $pieRows->execute([$year]);
    $pieRows = $pieRows->fetchAll();
} catch (Exception $e) {
    $pieRows = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Beranda — <?= APP_NAME ?></title>

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
body {
  font-family: 'Inter', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  background: #F1E3D6; 
  color: #ffffff; 
  min-height: 100vh;
}

/* MASTER THEME: ABU GLOSSY LIQUID GLASS (KHUSUS UNTUK NAVBAR BAR MENU) */
.liquid-glass {
  background: rgba(85, 116, 153, 0.75); 
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: none;
  box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.2), 0 8px 24px rgba(0, 0, 0, 0.1);
  position: relative;
  overflow: hidden;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
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

/* SCREEN LAYOUT VIEWPORT */
.hero-viewport {
  position: relative;
  width: 100%;
  height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  background-image: url('mandeh.jpg'); 
  background-size: cover;
  background-position: center bottom; 
  background-repeat: no-repeat;
  padding: 24px 16px 48px 16px;
}
@media (min-width: 768px) { .hero-viewport { padding: 24px 48px 48px 48px; } }
@media (min-width: 1024px) { .hero-viewport { padding: 24px 64px 64px 64px; } }

/* NAVBAR SYSTEM */
.nav-container { width: 100%; }
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
  transition: background 0.2s ease;
}
.nav-btn:hover { background: #f1f5f9; }

/* HERO LOWER COMPONENTS */
.hero-bottom {
  width: 100%;
  display: flex;
  flex-direction: column;
}
@media (min-width: 1024px) {
  .hero-bottom {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    align-items: flex-end;
  }
}

.hero-left-col h1 {
  font-size: 2.25rem;
  font-weight: 400;
  margin-bottom: 12px;
  line-height: 1.15;
  letter-spacing: -0.03em;
  color: #ffffff;
}
@media (min-width: 768px) { .hero-left-col h1 { font-size: 3.25rem; } }
@media (min-width: 1024px) { .hero-left-col h1 { font-size: 3.75rem; } }

.char {
  display: inline-block;
  opacity: 0;
  transform: translateX(-18px);
  transition: opacity 0.5s ease, transform 0.5s ease;
}

.hero-left-col p {
  font-size: 0.95rem;
  color: #f1f5f9;
  margin-bottom: 24px;
  font-weight: 400;
  max-width: 580px;
  line-height: 1.5;
  opacity: 0;
  transition: opacity 1s ease;
}

/* SEARCH ENGINE COMPONENT */
.hero-search-box {
  display: flex;
  max-width: 480px;
  background: rgba(15, 23, 42, 0.35);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 10px;
  overflow: hidden;
  margin-bottom: 20px;
  opacity: 0;
  transition: opacity 1s ease;
}
.hero-search-box input {
  flex: 1;
  padding: 12px 16px;
  background: transparent;
  border: none;
  outline: none;
  color: #ffffff;
  font-family: inherit;
  font-size: 0.875rem;
}
.hero-search-box input::placeholder { color: rgba(255, 255, 255, 0.5); }
.hero-search-box button {
  background: #ffffff;
  color: #0f172a;
  border: none;
  padding: 0 24px;
  font-weight: 500;
  cursor: pointer;
  font-size: 0.875rem;
}

.btn-row {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  opacity: 0;
  transition: opacity 1s ease;
}
.btn-primary {
  background: #ffffff;
  color: #0f172a;
  padding: 12px 28px;
  border-radius: 8px;
  font-weight: 500;
  text-decoration: none;
  font-size: 0.875rem;
}
.btn-secondary {
  color: #ffffff;
  padding: 12px 28px;
  border-radius: 8px;
  font-weight: 500;
  text-decoration: none;
  font-size: 0.875rem;
  border: 1px solid rgba(255, 255, 255, 0.25);
  transition: background 0.2s, color 0.2s;
}
.btn-secondary:hover { background: #ffffff; color: #0f172a; }

.hero-right-col {
  display: flex;
  align-items: flex-end;
  justify-content: flex-start;
  margin-top: 32px;
  opacity: 0;
  transition: opacity 1s ease;
}
@media (min-width: 1024px) { .hero-right-col { justify-content: flex-end; margin-top: 0; } }
.glass-tag {
  background: rgba(15, 23, 42, 0.25);
  padding: 12px 24px;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #ffffff;
}

/* DASHBOARD AREA WITH THEME-MATCHED BACKGROUND */
.dashboard-section {
  padding: 48px 16px;
  background: #F1E3D6; 
}
@media (min-width: 768px) { .dashboard-section { padding: 48px; } }
@media (min-width: 1024px) { .dashboard-section { padding: 48px 64px; } }

.stat-grid {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 20px;
  margin-top: -80px;
  position: relative;
  z-index: 20;
  margin-bottom: 40px;
}
@media (min-width: 768px) { .stat-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }

.stat-card, .card {
  background: #91B9B6; 
  border-radius: 14px;
  padding: 24px;
  box-shadow: 0 4px 14px rgba(74, 112, 156, 0.15);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.stat-card:hover, .card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 30px rgba(74, 112, 156, 0.3);
}

.stat-label {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.85); 
  font-weight: 600;
  margin-bottom: 6px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.stat-val {
  font-size: 1.6rem;
  font-weight: 700;
  color: #ffffff; 
  line-height: 1;
}

.grid2 {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 24px;
  margin-bottom: 24px;
}
@media (min-width: 1024px) { .grid2 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

.card-title, .sec-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #ffffff; 
}
.sec-title { font-size: 1.1rem; }

/* DATA TABLES MINIMALIST DESIGN */
.tw { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th {
  font-size: 0.75rem;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.9);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 14px 16px;
  border-bottom: 2px solid rgba(255, 255, 255, 0.2);
  text-align: left;
}
td {
  padding: 16px 16px;
  font-size: 0.875rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  color: #ffffff; 
}
tr:last-child td { border-bottom: none; }

.badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
}
.bs { background: rgba(255, 255, 255, 0.25); color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.4); }
.bw { background: #DFB868; color: #0f172a; } 
.bd { background: rgba(239, 68, 68, 0.4); color: #ffffff; }

.sec-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}
.sec-link { font-size: 0.85rem; color: rgba(255, 255, 255, 0.85); text-decoration: none; }
.sec-link:hover { color: #ffffff; text-decoration: underline; }

.footer {
  flex-shrink: 0; 
  background: rgba(30, 41, 59, 0.1);
  color: #475569; 
  padding: 32px 16px;
  border-top: 1px solid rgba(0, 0, 0, 0.05); 
}
@media (min-width: 768px) { .footer { padding: 32px 48px; } }
@media (min-width: 1024px) { .footer { padding: 32px 64px; } }
</style>
</head>
<body>

<div class="hero-viewport">
  
  <div class="nav-container">
    <nav class="nav-bar liquid-glass">
      <a class="nav-brand" href="<?= BASE_URL ?>/index.php">SITANGKIS</a>
      <div class="nav-links">
        <a class="nav-a active" href="<?= BASE_URL ?>/index.php">Beranda</a>
        <a class="nav-a" href="<?= BASE_URL ?>/pages/laporan.php">Laporan</a>
        <a class="nav-a" href="<?= BASE_URL ?>/pages/grafik.php">Grafik</a>
        <a class="nav-a" href="<?= BASE_URL ?>/pages/tentang.php">Tentang</a>
      </div>
      <a class="nav-btn" href="<?= BASE_URL ?>/login.php">Login Admin</a>
    </nav>
  </div>

  <div class="hero-bottom">
    <div class="hero-left-col">
      <h1 id="animated-heading">Transparansi Dana Desa</h1>
      <p id="subheading">Sistem Informasi Transparansi Anggaran Keuangan Keuangan Pendapatan dan Belanja Desa</p>
      
      <form class="hero-search-box" id="search-engine" action="<?= BASE_URL ?>/pages/laporan.php" method="GET">
        <input type="text" name="cari" placeholder="Cari laporan kegiatan, kategori, atau APBD Desa...">
        <button type="submit">Cari</button>
      </form>

      <div class="btn-row" id="buttons-row">
        <a href="<?= BASE_URL ?>/pages/laporan.php" class="btn-primary">Lihat Laporan</a>
        <a href="<?= BASE_URL ?>/pages/grafik.php" class="btn-secondary liquid-glass">Explore Grafik</a>
      </div>
    </div>

    <div class="hero-right-col" id="right-tag">
      <div class="glass-tag liquid-glass">
        Desa Ampang Pulai — TA <?= htmlspecialchars($desa['tahun_anggaran'] ?? date('Y')) ?>
      </div>
    </div>
  </div>

</div>

<div class="dashboard-section">
  
  <div class="stat-grid">
    <div class="stat-card">
      <div>
        <div class="stat-label">Total Dana Masuk (Disetujui)</div>
        <div class="stat-val"><?= rupiah($totalAnggaran) ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div>
        <div class="stat-label">Total Realisasi (Selesai)</div>
        <div class="stat-val"><?= rupiah($totalRealisasi) ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div>
        <div class="stat-label">Serapan Anggaran</div>
        <div class="stat-val"><?= $serapan ?>%</div>
      </div>
    </div>
  </div>

  <div class="grid2">
    <div class="card">
      <div class="card-title">Grafik Komparasi Dana Sektor (Masuk vs Belanja)</div>
      <div style="height:260px; position: relative;"><canvas id="barChart"></canvas></div>
    </div>
    <div class="card">
      <div class="card-title">Komposisi Makro Sumber Pendapatan</div>
      <div style="height:260px; position: relative;"><canvas id="pieChart"></canvas></div>
    </div>
  </div>

  <div class="card">
    <div class="sec-head">
      <span class="sec-title">Pengeluaran Terbaru — Desa Ampang Pulai</span>
      <a class="sec-link" href="<?= BASE_URL ?>/pages/laporan.php">Lihat semua →</a>
    </div>
    <div class="tw">
      <table>
        <thead>
          <tr><th>Tanggal</th><th>Nama Kegiatan</th><th>Kategori</th><th>Jumlah</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php foreach($recent as $r):
            $bc=['Selesai'=>'bs','Proses'=>'bw','Batal'=>'bd'];
          ?>
          <tr>
            <td><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
            <td><strong><?= htmlspecialchars($r['nama_kegiatan']) ?></strong></td>
            <td><?= htmlspecialchars($r['kategori']) ?></td>
            <td><?= rupiah($r['jumlah']) ?></td>
            <td><span class="badge <?= $bc[$r['status']]??'bi' ?>"><?= htmlspecialchars($r['status']) ?></span></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($recent)): ?>
          <tr><td colspan="5" style="text-align:center;padding:32px;color:rgba(255, 255, 255, 0.8)">Belum ada data laporan publik.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<footer class="footer">
  <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
    <div>
      <strong style="color:#475569;">SITANGKIS</strong> — Desa Ampang Pulai
      <p style="font-size:0.8rem; margin-top:4px; color: #64748b;">© 2026 Pemerintah Desa Ampang Pulai. Kelompok 6 — 4A Informatika UNSIKA.</p>
    </div>
  </div>
</footer>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const heading = document.getElementById("animated-heading");
  const text = heading.innerHTML;
  heading.innerHTML = "";

  const lines = text.split("<br>");
  let charGlobalIndex = 0;
  const charDelay = 30; 
  const initialDelay = 200; 

  lines.forEach((lineText, lineIndex) => {
    const lineSpan = document.createElement("span");
    lineSpan.style.display = "block";
    
    const chars = Array.from(lineText);
    chars.forEach((char) => {
      const charSpan = document.createElement("span");
      charSpan.classList.add("char");
      charSpan.innerHTML = char === " " ? "&nbsp;" : char;
      
      const delay = initialDelay + (charGlobalIndex * charDelay);
      charSpan.style.transitionDelay = delay + "ms";
      
      lineSpan.appendChild(charSpan);
      charGlobalIndex++;
    });

    heading.appendChild(lineSpan);
  });

  setTimeout(() => {
    document.querySelectorAll(".char").forEach(c => {
      c.style.opacity = "1";
      c.style.transform = "translateX(0)";
    });
  }, 50);

  setTimeout(() => {
    const sub = document.getElementById("subheading");
    sub.style.opacity = "1";
  }, 600);

  setTimeout(() => {
    const search = document.getElementById("search-engine");
    search.style.opacity = "1";
  }, 900);

  setTimeout(() => {
    const btns = document.getElementById("buttons-row");
    btns.style.opacity = "1";
  }, 1200);

  setTimeout(() => {
    const tag = document.getElementById("right-tag");
    tag.style.opacity = "1";
  }, 1400);
});

const commonFont = { family: 'Inter', size: 12, weight: '500' };

new Chart(document.getElementById('barChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($barData, 'kategori')) ?>,
    datasets: [
      { 
        label: 'Dana Masuk (Pagu)', 
        data: <?= json_encode(array_map(fn($r) => (int)$r['anggaran'], $barData)) ?>, 
        backgroundColor: '#1a3a6b', 
        borderRadius: 4 
      },
      { 
        label: 'Uang Keluar (Realisasi)', 
        data: <?= json_encode(array_map(fn($r) => (int)$r['realisasi'], $barData)) ?>, 
        backgroundColor: '#2a9d8f', 
        borderRadius: 4 
      }
    ]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { labels: { color: '#ffffff', font: commonFont }, position: 'bottom' } },
    scales: {
      y: { ticks: { color: '#ffffff', font: { family: 'Inter' }, callback: v => 'Rp ' + (v/1e6) + 'jt' }, grid: { color: 'rgba(255, 255, 255, 0.2)' } },
      x: { ticks: { color: '#ffffff', font: { family: 'Inter' } }, grid: { display: false } }
    }
  }
});

new Chart(document.getElementById('pieChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_column($pieRows, 'sumber')) ?>,
    datasets: [{ 
      data: <?= json_encode(array_map(fn($r) => (int)$r['jumlah'], $pieRows)) ?>, 
      backgroundColor: ['#FFAE34', '#FF6B6B', '#2a9d8f', '#4a90e2', '#8a56f2', '#f15bb5'], 
      borderWidth: 0 
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { labels: { color: '#ffffff', font: commonFont }, position: 'bottom' } },
    cutout: '72%'
  }
});
</script>
</body>
</html>