<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

$pageTitle  = 'Beranda';
$activePage = 'home';
$db = getDB();

$desa           = $db->query("SELECT * FROM desa_info LIMIT 1")->fetch();
$totalAnggaran  = (int)$db->query("SELECT SUM(jumlah) FROM anggaran WHERE tahun=YEAR(NOW())")->fetchColumn();
$totalRealisasi = (int)$db->query("SELECT SUM(jumlah) FROM realisasi WHERE YEAR(tanggal)=YEAR(NOW()) AND status='Selesai'")->fetchColumn();
$serapan        = $totalAnggaran > 0 ? round(($totalRealisasi/$totalAnggaran)*100,1) : 0;

$recent = $db->query("SELECT * FROM realisasi WHERE is_publik=1 ORDER BY tanggal DESC LIMIT 6")->fetchAll();

$barStmt = $db->prepare("SELECT a.kategori, a.jumlah AS anggaran, COALESCE(SUM(r.jumlah),0) AS realisasi
  FROM anggaran a LEFT JOIN realisasi r ON r.kategori=a.kategori AND r.status='Selesai' AND YEAR(r.tanggal)=YEAR(NOW())
  WHERE a.tahun=YEAR(NOW()) GROUP BY a.kategori, a.jumlah");
$barStmt->execute();
$barData = $barStmt->fetchAll();

$pieRows = $db->query("SELECT sumber, jumlah FROM sumber_pendapatan WHERE tahun=YEAR(NOW())")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Beranda — <?= APP_NAME ?></title>

<!-- INTER GOOGLE FONT -->
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
  background: #b8b8b8; /* TONE WARNA DIUBAH PERSIS SESUAI IMAGE_6885FA.PNG (PREMIUM MATTE LIGHT GREY) */
  color: #0f172a; 
  min-height: 100vh;
}

/* MASTER THEME: PROFESSIONAL LIQUID GLASS (TRANSPARANSI DI ATAS BACKGROUND ABU-ABU MURNI) */
.liquid-glass {
  background: rgba(255, 255, 255, 0.4); 
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
  border: none;
  box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.4), 0 8px 24px rgba(15, 23, 42, 0.04);
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
    rgba(255, 255, 255, 0.5) 0%, 
    rgba(15, 23, 42, 0.02) 40%,
    rgba(15, 23, 42, 0.06) 100%);
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
  background: rgba(15, 23, 42, 0.25); /* Lapisan gelap tipis agar navbar di atas foto tetap elegan */
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
  font-weight: 400;
  transition: color 0.2s ease;
}
.nav-a:hover, .nav-a.active { color: #ffffff; }

.nav-btn {
  background: #ffffff;
  color: #0f172a;
  padding: 8px 20px;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  text-decoration: none;
  transition: background 0.2s ease;
}
.nav-btn:hover { background: #f8fafc; }

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
  background: #b8b8b8; /* SELARAS DENGAN WARNA BACKGROUND UTAMA ATAS */
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

.stat-card {
  border-radius: 14px;
  padding: 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.stat-label {
  font-size: 0.75rem;
  color: #475569; 
  font-weight: 600;
  margin-bottom: 6px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.stat-val {
  font-size: 1.6rem;
  font-weight: 700;
  color: #0f172a;
  line-height: 1;
}

.grid2 {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 24px;
  margin-bottom: 24px;
}
@media (min-width: 1024px) { .grid2 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

.card {
  border-radius: 14px;
  padding: 24px;
}
.card-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 20px;
}

/* DATA TABLES MINIMALIST DESIGN */
.tw { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th {
  font-size: 0.75rem;
  font-weight: 600;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 14px 16px;
  border-bottom: 2px solid rgba(15, 23, 42, 0.08);
  text-align: left;
}
td {
  padding: 16px 16px;
  font-size: 0.875rem;
  border-bottom: 1px solid rgba(15, 23, 42, 0.04);
  color: #334155;
}
tr:last-child td { border-bottom: none; }
.badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
}
.bs { background: rgba(16, 185, 129, 0.15); color: #065f46; }
.bw { background: rgba(245, 158, 11, 0.15); color: #92400e; }
.bd { background: rgba(239, 68, 68, 0.15); color: #991b1b; }

.sec-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}
.sec-title { font-size: 1.1rem; font-weight: 700; color: #0f172a; }
.sec-link { font-size: 0.85rem; color: #475569; text-decoration: none; }
.sec-link:hover { color: #0f172a; }

/* REFINED HIGH-END FOOTER */
.footer {
  background: rgba(15, 23, 42, 0.05);
  color: #475569;
  padding: 32px 16px;
  border-top: 1px solid rgba(15, 23, 42, 0.05);
}
@media (min-width: 768px) { .footer { padding: 32px 48px; } }
@media (min-width: 1024px) { .footer { padding: 32px 64px; } }
</style>
</head>
<body>

<!-- HERO SEGMENT PLATFORM -->
<div class="hero-viewport">
  
  <!-- TOP NAVBAR AREA -->
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

  <!-- HERO ANCHOR ROW -->
  <div class="hero-bottom">
    <div class="hero-left-col">
      <h1 id="animated-heading">Transparansi Dana Desa</h1>
      <p id="subheading">Sistem Informasi Transparansi Anggaran Keuangan Keuangan Pendapatan dan Belanja Desa</p>
      
      <!-- FORM PENCARIAN -->
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

<!-- DATA TRANSITIONAL CORE DASHBOARD -->
<div class="dashboard-section">
  
  <!-- REAL-TIME DATA CARDS MODULE -->
  <div class="stat-grid">
    <div class="stat-card liquid-glass">
      <div>
        <div class="stat-label">Total Anggaran Desa</div>
        <div class="stat-val"><?= rupiah($totalAnggaran ?: ($desa['total_apbdes'] ?? 0)) ?></div>
      </div>
    </div>
    <div class="stat-card liquid-glass">
      <div>
        <div class="stat-label">Total Realisasi (Selesai)</div>
        <div class="stat-val"><?= rupiah($totalRealisasi) ?></div>
      </div>
    </div>
    <div class="stat-card liquid-glass">
      <div>
        <div class="stat-label">Serapan Anggaran</div>
        <div class="stat-val"><?= $serapan ?>%</div>
      </div>
    </div>
  </div>

  <!-- CHART CONFIGURATION -->
  <div class="grid2">
    <div class="card liquid-glass">
      <div class="card-title">Alokasi Anggaran per Sektor</div>
      <div style="height:260px; position: relative;"><canvas id="barChart"></canvas></div>
    </div>
    <div class="card liquid-glass">
      <div class="card-title">Sumber Pendapatan Desa</div>
      <div style="height:260px; position: relative;"><canvas id="pieChart"></canvas></div>
    </div>
  </div>

  <!-- FINANCIAL REPORT DATATABLE WITH LIGHT LIQUID-GLASS -->
  <div class="card liquid-glass">
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
          <tr><td colspan="5" style="text-align:center;padding:32px;color:#475569">Belum ada data laporan publik.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<footer class="footer">
  <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
    <div>
      <strong style="color:#0f172a;">SITANGKIS</strong> — Desa Ampang Pulai
      <p style="font-size:0.8rem; margin-top:4px;">© <?= date('Y') ?> Pemerintah Desa Ampang Pulai. Kelompok 6 — 4A Informatika UNSIKA.</p>
    </div>
  </div>
</footer>

<!-- SYSTEM ENGINES -->
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

/* RE-COLOR DIAGRAM SYSTEM: ADAPTIVE PROFESSIONAL LIGHT CONTRAST */
new Chart(document.getElementById('barChart'),{
  type:'bar',
  data:{
    labels:<?= json_encode(array_column($barData,'kategori')) ?>,
    datasets:[
      {
        label:'Anggaran',
        data:<?= json_encode(array_map(fn($r)=>(int)$r['anggaran'],$barData)) ?>,
        backgroundColor:'rgba(71, 85, 105, 0.2)', 
        borderColor: '#64748b',
        borderWidth: 1,
        borderRadius: 5
      },
      {
        label:'Realisasi',
        data:<?= json_encode(array_map(fn($r)=>(int)$r['realisasi'],$barData)) ?>,
        backgroundColor:'#0f766e', 
        borderRadius: 5
      }
    ]
  },
  options:{
    responsive:true,
    maintainAspectRatio:false,
    plugins:{
      legend:{
        labels:{color:'#334155', font:{family:'Inter', size:12, weight:'600'}},
        position:'bottom'
      }
    },
    scales:{
      y:{
        ticks:{color:'#475569', font:{family:'Inter'}, callback:v=>'Rp '+(v/1e6)+'jt'},
        grid:{color:'rgba(15, 23, 42, 0.06)'}
      },
      x:{
        ticks:{color:'#475569', font:{family:'Inter'}},
        grid:{display:false}
      }
    }
  }
});

new Chart(document.getElementById('pieChart'),{
  type:'doughnut',
  data:{
    labels:<?= json_encode(array_column($pieRows,'sumber')) ?>,
    datasets:[{
      data:<?= json_encode(array_map(fn($r)=>(int)$r['jumlah'],$pieRows)) ?>,
      backgroundColor:['#0284c7','#0f766e','#e11d48','#ea580c'], 
      borderWidth: 0
    }]
  },
  options:{
    responsive:true,
    maintainAspectRatio:false,
    plugins:{
      legend:{
        labels:{color:'#334155', font:{family:'Inter', size:12, weight:'600'}},
        position:'bottom'
      }
    },
    cutout:'72%'
  }
});
</script>
</body>
</html>