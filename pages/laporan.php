<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle  = 'Laporan Publik';
$activePage = 'laporan';
$db = getDB();

// Handle fitur pencarian bawaan SITANGKIS agar tetap berfungsi
$keyword = isset($_GET['cari']) ? trim($_GET['cari']) : '';

if (!empty($keyword)) {
    $stmt = $db->prepare("SELECT * FROM realisasi WHERE is_publik = 1 AND (nama_kegiatan LIKE :kunci OR kategori LIKE :kunci) ORDER BY tanggal DESC");
    $stmt->execute(['kunci' => "%$keyword%"]);
    $laporanList = $stmt->fetchAll();
} else {
    $laporanList = $db->query("SELECT * FROM realisasi WHERE is_publik = 1 ORDER BY tanggal DESC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?> — <?= APP_NAME ?></title>

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
  background: #b8b8b8; /* BACKGROUND UTAMA MATTE GREY */
  color: #ffffff; /* DIUBAH MENJADI PUTIH AGAR SANGAT KONTRAS DENGAN SLATE GREY */
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

/* MASTER THEME: SLATE STEEL BLUE GLASS (AKURAT SESUAI IMAGE_681556.PNG) */
.liquid-glass {
  background: rgba(85, 116, 153, 0.75); /* Perpaduan abu-abu, biru langit, dan kaca transparan */
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

/* HEADER NAVIGATION SECTION */
.header-nav-section {
  width: 100%;
  padding: 24px 16px;
}
@media (min-width: 768px) { .header-nav-section { padding: 24px 48px; } }
@media (min-width: 1024px) { .header-nav-section { padding: 24px 64px; } }

/* NAV BAR RE-ALIGNMENT */
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
  color: #3b587c; /* Menyeimbangkan abu kebiruan */
  padding: 8px 20px;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  text-decoration: none;
  transition: background 0.2s ease;
}
.nav-btn:hover { background: #f1f5f9; }

/* MAIN CONTENT AREA */
.content-wrapper {
  flex: 1;
  padding: 0 16px 48px 16px;
}
@media (min-width: 768px) { .content-wrapper { padding: 0 48px 48px 48px; } }
@media (min-width: 1024px) { .content-wrapper { padding: 0 64px 64px 64px; } }

/* PAGE HEADER TITLE TYPE */
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 24px;
  margin-bottom: 32px;
}
.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b; /* Abu-abu gelap elegan agar judul halaman utama pop-out */
  letter-spacing: -0.03em;
}

/* SEARCH ENGINE SEARCH BAR */
.search-container {
  display: flex;
  background: rgba(255, 255, 255, 0.4);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 8px;
  overflow: hidden;
  max-width: 320px;
  width: 100%;
}
.search-container input {
  flex: 1;
  padding: 8px 14px;
  background: transparent;
  border: none;
  outline: none;
  font-family: inherit;
  font-size: 0.85rem;
  color: #0f172a;
}
.search-container input::placeholder { color: rgba(15, 23, 42, 0.6); }
.search-container button {
  background: #ffffff;
  color: #1e293b;
  border: none;
  padding: 0 16px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
}

/* CARDS ANNOUNCEMENT GRID SYSTEM */
.news-grid {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 24px;
}
@media (min-width: 640px) { .news-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (min-width: 1024px) { .news-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (min-width: 1280px) { .news-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }

.news-card {
  border-radius: 14px;
  display: flex;
  flex-direction: column;
  padding: 18px;
}
.news-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.15);
}

/* REAL NEWS PORTAL DUMMY IMAGE WRAPPER */
.news-image-wrapper {
  width: 100%;
  height: 170px;
  border-radius: 10px;
  margin-bottom: 16px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.news-image-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.news-date {
  font-size: 0.8rem;
  color: #e2e8f0; /* Kontras di atas abu kebiruan */
  font-weight: 500;
  margin-bottom: 8px;
}
.news-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #ffffff;
  line-height: 1.35;
  margin-bottom: 8px;
  min-height: 46px;
}
.news-excerpt {
  font-size: 0.875rem;
  color: #f1f5f9;
  line-height: 1.5;
  margin-bottom: 20px;
  flex: 1;
}
.news-meta-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-top: 1px solid rgba(255, 255, 255, 0.15);
  padding-top: 14px;
  margin-top: auto;
}
.news-amount {
  font-size: 1rem;
  font-weight: 700;
  color: #ffffff; 
}

.news-read-btn {
  font-size: 0.85rem;
  font-weight: 600;
  color: #ffffff;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  transition: transform 0.2s;
}
.news-read-btn:hover { transform: translateX(4px); }

.badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 700;
  background: rgba(255, 255, 255, 0.2);
  color: #ffffff;
}
/* Modifikasi warna teks badge status agar tetap aman dibaca di atas abu kebiruan */
.bs { background: rgba(52, 211, 153, 0.25); color: #a7f3d0; }
.bw { background: rgba(251, 191, 36, 0.25); color: #fde68a; }
.bd { background: rgba(248, 113, 113, 0.25); color: #fecaca; }

.empty-state {
  grid-column: 1 / -1;
  text-align: center;
  padding: 64px 16px;
  color: #475569;
  font-size: 0.95rem;
}

/* FOOTER ARCHITECTURE */
.footer {
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

<!-- TOP NAVIGATION BAR COMPONENT (SLATE STEEL BLUE TRANSPARENT) -->
<div class="header-nav-section">
  <nav class="nav-bar liquid-glass">
    <a class="nav-brand" href="<?= BASE_URL ?>/index.php">SITANGKIS</a>
    <div class="nav-links">
      <a class="nav-a" href="<?= BASE_URL ?>/index.php">Beranda</a>
      <a class="nav-a active" href="<?= BASE_URL ?>/pages/laporan.php">Laporan</a>
      <a class="nav-a" href="<?= BASE_URL ?>/pages/grafik.php">Grafik</a>
      <a class="nav-a" href="<?= BASE_URL ?>/pages/tentang.php">Tentang</a>
    </div>
    <a class="nav-btn" href="<?= BASE_URL ?>/login.php">Login Admin</a>
  </nav>
</div>

<!-- PORTAL NEWS CONTAINER -->
<div class="content-wrapper">
  
  <div class="page-header">
    <h1 class="page-title">Announcements</h1>
    
    <form class="search-container" action="" method="GET">
      <input type="text" name="cari" value="<?= htmlspecialchars($keyword) ?>" placeholder="Cari pengumuman kegiatan...">
      <button type="submit">Cari</button>
    </form>
  </div>

  <!-- ARTICLE NEWS LAYOUT GRID -->
  <div class="news-grid">
    <?php 
    $i = 10; 
    foreach($laporanList as $l): 
        $bc = ['Selesai' => 'bs', 'Proses' => 'bw', 'Batal' => 'bd'];
        $tanggalBerita = date('d M, Y', strtotime($l['tanggal']));
        
        // Memuat foto dummy alam/infrastruktur resolusi tinggi secara random
        $dummyImage = "https://picsum.photos/400/250?random=" . $i;
        $i++;
    ?>
    <div class="news-card liquid-glass">
      
      <!-- REAL DUMMY PORTAL IMAGE DISPLAY -->
      <div class="news-image-wrapper">
         <img src="<?= $dummyImage ?>" alt="Ilustrasi Kegiatan <?= htmlspecialchars($l['nama_kegiatan']) ?>">
      </div>

      <div class="news-date"><?= $tanggalBerita ?></div>
      <h2 class="news-title"><?= htmlspecialchars($l['nama_kegiatan']) ?></h2>
      
      <p class="news-excerpt">
        Pelaksanaan realisasi kerja bidang <?= htmlspecialchars($l['kategori']) ?> yang dialokasikan dari anggaran pendapatan dan belanja desa tahun ini demi peningkatan kualitas sarana publik.
      </p>

      <div class="news-meta-row">
        <div class="news-amount"><?= rupiah($l['jumlah']) ?></div>
        <span class="badge <?= $bc[$l['status']] ?? 'bi' ?>"><?= htmlspecialchars($l['status']) ?></span>
      </div>
      
      <div style="margin-top: 14px;">
        <a href="detail_laporan.php?id=<?= $l['id'] ?>" class="news-read-btn">
          Baca Selengkapnya →
        </a>
      </div>

    </div>
    <?php endforeach; ?>

    <?php if(empty($laporanList)): ?>
      <div class="empty-state">
        Belum ada publikasi pengumuman kegiatan saat ini atau data tidak ditemukan.
      </div>
    <?php endif; ?>
  </div>

</div>

<!-- INDUSTRIAL HIGH-END FOOTER -->
<footer class="footer">
  <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
    <div>
      <strong style="color:#1e293b;">SITANGKIS</strong> — Desa Ampang Pulai
      <p style="font-size:0.8rem; margin-top:4px;">© 2026 Pemerintah Desa Ampang Pulai. Kelompok 6 — 4A Informatika UNSIKA.</p>
    </div>
  </div>
</footer>

</body>
</html>