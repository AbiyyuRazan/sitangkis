<?php
// pages/tentang.php — Profil Desa & Informasi Sistem (Premium Local Landscape Glass Theme)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle  = 'Tentang Kami';
$activePage = 'tentang';
$db = getDB();

$desa = $db->query("SELECT * FROM desa_info LIMIT 1")->fetch();
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

<style>
/* GLOBAL LAYOUT SPECIFICATION */
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
  color: #ffffff; 
  display: flex;
  flex-direction: column;
  position: relative;
  background: #b8b8b8; /* Fallback background */
  overflow-x: hidden;
}

/* FIXED: MEMASASNG GAMBAR BASE.JPG SECARA JERNIH TANPA EFEK BLUR */
body::before {
  content: '';
  position: fixed;
  inset: 0;
  z-index: -2;
  background: url('../base.jpg') no-repeat center center;
  background-size: cover;
}

/* MASTER THEME: LIGHT GREY LUXURY GLASS (KACA ABU KHAS BERANDA) */
.liquid-glass {
  background: rgba(100, 125, 150, 0.45); /* Mengembangkan warna abu transparan cerah */
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.25); 
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  border-radius: 14px;
}

/* NAVBAR HEADER STRUCTURE */
.header-nav-section {
  width: 100%;
  padding: 24px 16px 12px 16px;
  position: relative;
  z-index: 10;
}
@media (min-width: 768px) { .header-nav-section { padding: 24px 48px 12px 48px; } }
@media (min-width: 1024px) { .header-nav-section { padding: 24px 64px 12px 64px; } }

.nav-bar {
  padding: 0 24px;
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

/* CONTENT LAYER CONTAINER */
.content-wrapper {
  flex: 1 0 auto; 
  padding: 0 16px 48px 16px;
  position: relative;
  z-index: 10;
}
@media (min-width: 768px) { .content-wrapper { padding: 0 48px 48px 48px; } }
@media (min-width: 1024px) { .content-wrapper { padding: 0 64px 64px 64px; } }

.page-header {
  margin-top: 24px;
  margin-bottom: 32px;
}
.page-title-box h1 {
  font-size: 2.25rem;
  font-weight: 700;
  color: #ffffff; 
  letter-spacing: -0.03em;
  margin-bottom: 6px;
  text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.page-title-box p {
  font-size: 0.95rem;
  color: #f1f5f9;
  text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}

/* CARDS MESH GRID LAYOUT */
.about-layout-grid {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 24px;
  margin-bottom: 24px;
}
@media (min-width: 1024px) {
  .about-layout-grid {
    grid-template-columns: 1.5fr 1fr; 
  }
}

.about-card {
  padding: 28px;
}
.about-card-title {
  font-size: 1.2rem;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.25);
  padding-bottom: 8px;
}

.about-p {
  font-size: 0.925rem;
  line-height: 1.63;
  color: #ffffff;
  margin-bottom: 16px;
  text-align: justify;
}
.about-p:last-of-type { margin-bottom: 0; }

.section-subtitle {
  font-size: 1rem;
  font-weight: 600;
  color: #ffffff;
  margin-top: 16px;
  margin-bottom: 8px;
  text-decoration: underline;
  text-underline-offset: 4px;
}

.list-style {
  padding-left: 20px;
  margin-bottom: 16px;
}
.list-style li {
  font-size: 0.925rem;
  line-height: 1.6;
  color: #ffffff;
  margin-bottom: 6px;
}

/* SYSTEM BADGES CONTROL */
.badge-container {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 8px;
  margin-bottom: 16px;
}
.sys-badge {
  display: inline-flex;
  align-items: center;
  padding: 6px 14px;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
}
.badge-feature {
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #ffffff;
}
.badge-check {
  margin-right: 6px;
  color: #ffffff;
  font-weight: bold;
}

/* SYNCHRONIZED TECH BADGES SPECIFICATIONS */
.tech-php, .tech-mysql, .tech-htmlcss, .tech-js, 
.tech-chartjs, .tech-netfliy, .tech-github, .tech-vscode { 
  background-color: rgba(255, 255, 255, 0.25); 
  border: 1px solid rgba(255, 255, 255, 0.3); 
  color: #ffffff; 
}

/* PETA GEOGRAFIS */
.map-frame-box {
  width: 100%;
  height: 350px;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.3);
}

/* MEDIA SOSIAL ACTION CONTROLLER */
.contact-btn-grid {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 14px;
  margin-top: 12px;
}
@media (min-width: 640px) { .contact-btn-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (min-width: 1024px) { .contact-btn-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }

.social-button {
  display: flex;
  flex-direction: column;
  justify-content: center;
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  padding: 14px 18px;
  border-radius: 10px;
  text-decoration: none;
  transition: background 0.2s ease, transform 0.15s ease;
}
.social-button:hover {
  background: rgba(255, 255, 255, 0.35);
  transform: translateY(-2px);
}
.btn-platform-title {
  font-size: 0.725rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #f1f5f9;
  margin-bottom: 2px;
}
.btn-platform-value {
  font-size: 0.9rem;
  font-weight: 600;
  color: #ffffff;
}

/* FOOTER ARCHITECTURE */
.footer {
  flex-shrink: 0; 
  background: rgba(40, 55, 75, 0.4);
  backdrop-filter: blur(10px);
  color: #e2e8f0;
  padding: 32px 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
  position: relative;
  z-index: 10;
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
      <a class="nav-a" href="<?= BASE_URL ?>/pages/grafik.php">Grafik</a>
      <a class="nav-a active" href="<?= BASE_URL ?>/pages/tentang.php">Tentang</a>
    </div>
    <a class="nav-btn" href="<?= BASE_URL ?>/login.php">Login Admin</a>
  </nav>
</div>

<div class="content-wrapper">
  
  <div class="page-header">
    <div class="page-title-box">
      <h1>Tentang SITANGKIS & Desa</h1>
      <p>Informasi profil Pemerintah Desa Ampang Pulai dan keterbukaan tata kelola sistem</p>
    </div>
  </div>

  <div class="about-layout-grid">
    
    <div class="about-card liquid-glass">
      <h2 class="about-card-title">Profil Desa Ampang Pulai</h2>
      <p class="about-p">
        Desa Ampang Pulai merupakan salah satu pusat administrasi pemerintahan nagari yang terletak di Kecamatan Koto XI Tarusan, Kabupaten Pesisir Selatan, Provinsi Sumatera Barat. Berakar dari kawasan pesisir yang kaya akan potensi maritim and pertanian, desa ini terus bertransformasi menjadi kawasan yang adaptif terhadap perkembangan teknologi informasi demi meningkatkan mutu pelayanan sipil serta kesejahteraan masyarakat lokal.
      </p>

      <div class="section-subtitle">Visi Utama</div>
      <p class="about-p">
        "Mewujudkan tata kelola Pemerintahan Desa Ampang Pulai yang mandiri, transparan, akuntabel, dan berdaulat dalam pelayanan publik menuju masyarakat yang madani dan sejahtera."
      </p>

      <div class="section-subtitle">Misi Pembangunan</div>
      <ul class="list-style">
        <li>Menyelenggarakan reformasi birokrasi desa yang bersih melalui implementasi teknologi digital tepat guna.</li>
        <li>Mewujudkan transparansi penuh dalam pengelolaan Anggaran Pendapatan dan Belanja Desa (APBDes).</li>
        <li>Meningkatkan partisipasi aktif warga dalam monitoring pembangunan fisik maupun non-fisik di wilayah desa.</li>
        <li>Mengoptimalkan pengolahan potensi sumber daya alam maritim dan perkebunan demi kemandirian ekonomi desa.</li>
      </ul>
    </div>

    <div class="about-card liquid-glass">
      <h2 class="about-card-title">Tentang Aplikasi</h2>
      <p class="about-p">
        SITANGKIS (Sistem Informasi Transparansi Keuangan dan Akuntabilitas Publik) dirancang khusus sebagai jembatan digital publik untuk mempublikasikan pengelolaan keuangan desa secara berkala. Melalui integrasi visualisasi grafik yang interaktif dan tabel rekapitulasi realisasi, sistem ini berkomitmen penuh dalam mengikis batas birokrasi sejalan dengan amanat UU No. 14 Tahun 2008 tentang Keterbukaan Informasi Publik.
      </p>
      <p class="about-p">
        Aplikasi ini dikembangkan untuk memastikan setiap rupiah dana alokasi anggaran, baik dana desa maupun pendapatan asli daerah, dapat dipantau langsung proses penyerapannya oleh seluruh elemen masyarakat. Dengan adopsi arsitektur web modern yang responsif, SITANGKIS menghadirkan akurasi data tanpa manipulasi demi terciptanya pemerintahan desa yang berintegritas tinggi.
      </p>
    </div>

  </div>

  <!-- FIXED PERMANENT: MENGGUNAKAN ENCRYPTED EMBED URL RESMI GOOGLE MAPS BAGI DESA AMPANG PULAI -->
  <div class="about-card liquid-glass" style="margin-bottom: 24px;">
    <h2 class="about-card-title">Peta Wilayah Desa Ampang Pulai</h2>
    <p class="about-p" style="margin-bottom: 16px;">Berikut adalah visualisasi batas wilayah geografis administrasi Desa Ampang Pulai, Kecamatan Koto XI Tarusan:</p>
    
    <iframe 
      class="map-frame-box" 
      src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15956.326372551466!2d100.4190849!3d-1.2415174!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2fd26074fbff6cb7%3A0x6bfe76e2730303fd!2sAmpang%20Pulai%2C%20Kec.%20Koto%20XI%20Tarusan%2C%20Kabupaten%20Pesisir%20Selatan%2C%20Sumatera%20Barat!5e0!3m2!1sid!2sid!4v1718530000000!5m2!1sid!2sid" 
      allowfullscreen="" 
      loading="lazy"
      referrerpolicy="no-referrer-when-downgrade">
    </iframe>
  </div>

  <div class="about-layout-grid">
    
    <div class="about-card liquid-glass">
      <h2 class="about-card-title">Fitur Utama</h2>
      <div class="badge-container">
        <div class="sys-badge badge-feature"><span class="badge-check">✓</span> Login Multi-Role</div>
        <div class="sys-badge badge-feature"><span class="badge-check">✓</span> Kelola Anggaran (CRUD)</div>
        <div class="sys-badge badge-feature"><span class="badge-check">✓</span> Catat Realisasi Dana</div>
        <div class="sys-badge badge-feature"><span class="badge-check">✓</span> Validasi Kepala Desa</div>
        <div class="sys-badge badge-feature"><span class="badge-check">✓</span> Laporan Publik</div>
        <div class="sys-badge badge-feature"><span class="badge-check">✓</span> Grafik Visualisasi Real-time</div>
        <div class="sys-badge badge-feature"><span class="badge-check">✓</span> Ekspor CSV</div>
        <div class="sys-badge badge-feature"><span class="badge-check">✓</span> Responsif Mobile</div>
      </div>
    </div>

    <div class="about-card liquid-glass">
      <h2 class="about-card-title">Teknologi yang Digunakan</h2>
      <div class="badge-container">
        <div class="sys-badge tech-php">PHP 8+</div>
        <div class="sys-badge tech-mysql">MySQL</div>
        <div class="sys-badge tech-htmlcss">HTML5 + CSS3</div>
        <div class="sys-badge tech-js">JavaScript (ES6)</div>
        <div class="sys-badge tech-chartjs">Chart.js</div>
        <div class="sys-badge tech-netfliy">Netlify Framework</div>
        <div class="sys-badge tech-github">GitHub</div>
        <div class="sys-badge tech-vscode">VSCode</div>
      </div>
      <p style="font-size:0.8rem; color:#e2e8f0; margin-top: auto;">Dikembangkan oleh <strong>Kelompok 6, Kelas 4A Informatika — UNSIKA Karawang</strong></p>
    </div>

  </div>

  <div class="about-card liquid-glass" style="margin-top: 24px;">
    <h2 class="about-card-title">Contak & Media Sosial Resmi Desa</h2>
    <p class="about-p" style="margin-bottom: 8px;">Silakan klik pada tombol di bawah ini untuk terhubung langsung dengan kanal komunikasi interaktif milik pemerintah desa:</p>
    
    <div class="contact-btn-grid">
      <a href="mailto:pemdes@ampangpulai.desa.id" class="social-button">
        <span class="btn-platform-title">Surel / Email Resmi</span>
        <span class="btn-platform-value">pemdes@ampangpulai.desa.id</span>
      </a>
      <a href="https://instagram.com/desa.ampangpulai" target="_blank" class="social-button">
        <span class="btn-platform-title">Instagram</span>
        <span class="btn-platform-value">@desa.ampangpulai</span>
      </a>
      <a href="https://facebook.com/DesaAmpangPulaiResmi" target="_blank" class="social-button">
        <span class="btn-platform-title">Facebook Page</span>
        <span class="btn-platform-value">Pemerintah Desa Ampang Pulai</span>
      </a>
      <a href="https://youtube.com/@DesaAmpangPulaiTV" target="_blank" class="social-button">
        <span class="btn-platform-title">YouTube Channel</span>
        <span class="btn-platform-value">Desa Ampang Pulai TV</span>
      </a>
      <a href="https://wa.me/6281234567890" target="_blank" class="social-button">
        <span class="btn-platform-title">Layanan WhatsApp Terpadu</span>
        <span class="btn-platform-value">+62 812-3456-7890</span>
      </a>
      <div class="social-button" style="cursor: default;">
        <span class="btn-platform-title">Alamat Kantor Desa</span>
        <span class="btn-platform-value">Jl. Raya Ampang Pulai No. 06, Koto XI Tarusan</span>
      </div>
    </div>
  </div>

</div>

<footer class="footer">
  <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
    <div>
      <strong style="color:#ffffff;">SITANGKIS</strong> — Desa Ampang Pulai
      <p style="font-size:0.8rem; margin-top:4px;">© 2026 Pemerintah Desa Ampang Pulai. Kelompok 6 — 4A Informatika UNSIKA.</p>
    </div>
  </div>
</footer>

</body>
</html>