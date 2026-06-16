<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
if (!isLoggedIn()) redirect(BASE_URL . '/login.php');

$db = getDB();
$adminPage  = 'pengaturan';
$adminTitle = 'Pengaturan';
$user = currentUser();
$roleLabel = ['bendahara'=>'Bendahara Desa','sekdes'=>'Sekretaris Desa','kades'=>'Kepala Desa'];
$roleDisplay = $roleLabel[$user['role']] ?? $user['role'];
$avatar = strtoupper(substr($user['nama'], 0, 2));

$successAkun = $successDesa = '';
$errorAkun = $errorDesa = '';

// ── UPDATE AKUN & GANTI PASSWORD ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'akun') {
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $passLama = $_POST['pass_lama'] ?? '';
    $passBaru = $_POST['pass_baru'] ?? '';
    $passKonfirmasi = $_POST['pass_konfirmasi'] ?? '';

    if (!$nama)  $errorAkun = 'Nama tidak boleh kosong.';
    elseif (!$email) $errorAkun = 'Email tidak boleh kosong.';
    else {
        // Cek email unik
        $cek = $db->prepare("SELECT id FROM users WHERE email=? AND id!=?");
        $cek->execute([$email, $user['id']]);
        if ($cek->fetch()) {
            $errorAkun = 'Email sudah digunakan akun lain.';
        } else {
            // Process ganti password
            if ($passBaru !== '') {
                $hashRow = $db->prepare("SELECT password FROM users WHERE id=?");
                $hashRow->execute([$user['id']]);
                $currentHash = $hashRow->fetchColumn();
                if (!$passLama) {
                    $errorAkun = 'Masukkan password lama untuk mengganti password.';
                } elseif (!password_verify($passLama, $currentHash)) {
                    $errorAkun = 'Password lama tidak sesuai.';
                } elseif (strlen($passBaru) < 6) {
                    $errorAkun = 'Password baru minimal 6 karakter.';
                } elseif ($passBaru !== $passKonfirmasi) {
                    $errorAkun = 'Konfirmasi password tidak cocok.';
                } else {
                    $newHash = password_hash($passBaru, PASSWORD_BCRYPT);
                    $db->prepare("UPDATE users SET nama=?,email=?,password=? WHERE id=?")
                       ->execute([$nama, $email, $newHash, $user['id']]);
                    $_SESSION['user']['nama']  = $nama;
                    $_SESSION['user']['email'] = $email;
                    $successAkun = 'Akun dan password berhasil diperbarui!';
                }
            } else {
                $db->prepare("UPDATE users SET nama=?,email=? WHERE id=?")
                   ->execute([$nama, $email, $user['id']]);
                $_SESSION['user']['nama']  = $nama;
                $_SESSION['user']['email'] = $email;
                $successAkun = 'Informasi akun berhasil diperbarui!';
            }
        }
    }
}

// ── UPDATE INFO DESA ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'desa') {
    if (hasRole(['kades','sekdes'])) {
        $db->prepare("UPDATE desa_info SET nama_desa=?,kepala_desa=?,tahun_anggaran=?,total_apbdes=? WHERE id=1")
           ->execute([
               trim($_POST['nama_desa'] ?? ''),
               trim($_POST['kepala_desa'] ?? ''),
               (int)($_POST['tahun_anggaran'] ?? date('Y')),
               (int)str_replace(['.',',' ,' '], '', $_POST['total_apbdes'] ?? '')
           ]);
        $successDesa = 'Informasi desa berhasil disimpan!';
    }
}

$u2 = $db->prepare("SELECT * FROM users WHERE id=?"); $u2->execute([$user['id']]); $userData = $u2->fetch();
$desa = $db->query("SELECT * FROM desa_info LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pengaturan — <?= APP_NAME ?> Admin</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Material+Icons+Round&display=swap" rel="stylesheet">

<style>
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
body {
  font-family: 'Plus Jakarta Sans', sans-serif;
  background-color: #FFF9F1; 
  color: #1E2229;
  min-height: 100vh;
  display: flex;
}

/* SIDEBAR RE-DESIGN */
.sidebar {
  width: 80px; 
  background: #FFAE34; 
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0; left: 0; bottom: 0;
  z-index: 100;
  box-shadow: 4px 0 20px rgba(243, 230, 211, 0.4);
  align-items: center; 
}
.sb-top {
  padding: 24px 0 16px;
  width: 100%;
  text-align: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}
.sb-logo-text { 
  font-weight: 800; 
  font-size: 0.85rem; 
  color: #1E2229;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}
.sb-nav { 
  flex: 1; 
  padding: 20px 0; 
  display: flex; 
  flex-direction: column; 
  gap: 8px; 
  width: 100%;
  align-items: center;
}
.sb-link {
  display: flex; 
  align-items: center; 
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 14px;
  color: rgba(30, 34, 41, 0.7); 
  text-decoration: none;
  transition: all .2s ease;
}
.sb-link .material-icons-round { font-size: 1.4rem; color: rgba(30, 34, 41, 0.75); }
.sb-link:hover { background: rgba(255, 255, 255, 0.2); color: #1E2229; }
.sb-link.active {
  background: #FF6B6B; 
  color: #FFFFFF;
  position: relative;
}
.sb-link.active .material-icons-round { color: #FFFFFF; }
.sb-link.active::after {
  content: '';
  position: absolute;
  right: -16px; top: 50%;
  transform: translateY(-50%);
  border-style: solid;
  border-width: 6px 6px 6px 0;
  border-color: transparent #FFF9F1 transparent transparent;
}
.sb-divider { width: 70%; height: 1px; background: rgba(255, 255, 255, 0.2); margin: 8px 0; }
.sb-foot { padding: 20px 0; width: 100%; display: flex; justify-content: center; }
.sb-avatar {
  width: 40px; height: 40px; border-radius: 50%;
  background: #FFFFFF; color: #1E2229;
  display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-size: .85rem;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

/* MAIN AREA ADJUSTMENT */
.main { margin-left: 80px; flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
.topbar {
  padding: 0 40px; height: 80px;
  display: flex; align-items: center; justify-content: space-between;
  background: transparent;
}
.topbar-left h2 { font-size: 2rem; font-weight: 800; color: #1E2229; letter-spacing: -0.03em; }
.topbar-left p { font-size: .78rem; color: #FF6B6B; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; margin-top: 2px; }
.topbar-right { display: flex; align-items: center; gap: 16px; }
.top-avatar {
  width: 40px; height: 40px; border-radius: 50%;
  background: #FFAE34; color: #1E2229;
  display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-size: .85rem;
}

/* BODY CONTAINER */
.body { padding: 12px 40px 40px 40px; flex: 1; max-width: 900px; }

/* COMPONENT CARD */
.card {
  background: #FFFFFF; border-radius: 20px;
  padding: 28px; border: none;
  box-shadow: 0 10px 30px rgba(243, 230, 211, 0.4);
  margin-bottom: 32px;
}
.card-head { margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #FFF9F0; }
.card-title { font-size: 1.05rem; font-weight: 800; color: #1E2229; letter-spacing: -0.02em; }
.card-head p { font-size: .82rem; color: #8A929A; font-weight: 500; margin-top: 4px; }

/* FORM FIELD STYLE */
.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px; }
.form-group.full { grid-column: span 2; }
.form-group label { display: block; font-size: 0.85rem; font-weight: 700; color: #1E2229; margin-bottom: 8px; }
.form-group input, .form-group select, .form-group textarea {
  width: 100%; padding: 14px 18px; border: 1.5px solid #F3E6D3;
  background-color: #FFFDF9; border-radius: 12px; font-family: inherit;
  font-size: 0.92rem; color: #1E2229; outline: none; transition: all 0.2s ease;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
  border-color: #FFAE34; background-color: #FFFFFF; box-shadow: 0 0 0 4px rgba(255, 174, 52, 0.1);
}
.form-group input[readonly] { background-color: #FFF9F1; color: #8A929A; cursor: not-allowed; border-color: #F3E6D3; }
.form-hint { font-size: 0.78rem; color: #8A929A; margin-top: 6px; font-weight: 500; }

/* PASSWORD SECTION */
.pass-section { background: #FFFDF9; border: 1.5px solid #F3E6D3; border-radius: 16px; padding: 24px; margin-top: 8px; }
.pass-section h4 { font-size: .92rem; font-weight: 800; color: #1E2229; margin-bottom: 4px; }
.pass-section p { font-size: .8rem; color: #8A929A; margin-bottom: 16px; font-weight: 500; }
.pass-strength { height: 6px; border-radius: 20px; background: #FFF9F0; margin-top: 8px; overflow: hidden; }
.pass-fill { height: 100%; border-radius: 20px; transition: all .3s ease; width: 0; }
.pass-label { font-size: .78rem; margin-top: 6px; font-weight: 700; }

/* ACTION BUTTONS MOCKUP */
.btn-mockup-primary {
  background: #FF6B6B; color: #FFFFFF; padding: 14px 28px;
  border-radius: 12px; font-weight: 700; font-size: 0.92rem; border: none;
  cursor: pointer; transition: all 0.2s ease; box-shadow: 0 6px 16px rgba(255, 107, 107, 0.25);
}
.btn-mockup-primary:hover { background: #E85555; transform: translateY(-1px); }

/* ALERTS */
.alert { padding: 14px 18px; border-radius: 12px; font-size: 0.88rem; font-weight: 600; margin-bottom: 24px; }
.alert-success { background: #E6F7F4; border: 1px solid #CEF3EC; color: #2a9d8f; }
.alert-error { background: #FFF0F0; border: 1px solid #FFE1E1; color: #FF6B6B; }

@media(max-width:1024px){
  .sidebar { transform: translateX(-100%); }
  .main { margin-left: 0; }
  .form-grid { grid-template-columns: 1fr; }
  .body { padding: 24px; }
}
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sb-top">
    <div class="sb-logo-text">STK</div>
  </div>
  <nav class="sb-nav">
    <a class="sb-link" href="<?= BASE_URL ?>/admin/index.php" title="Dashboard">
      <span class="material-icons-round">space_dashboard</span>
    </a>
    <?php if(hasRole(['bendahara','sekdes'])): ?>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/kelola_anggaran.php" title="Kelola Anggaran">
      <span class="material-icons-round">assignment</span>
    </a>
    <?php endif; ?>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/realisasi_dana.php" title="Realisasi Dana">
      <span class="material-icons-round">payments</span>
    </a>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/laporan.php" title="Laporan Publik">
      <span class="material-icons-round">description</span>
    </a>
    <?php if(hasRole(['sekdes','kades'])): ?>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/grafik.php" title="Grafik">
      <span class="material-icons-round">insert_chart</span>
    </a>
    <?php endif; ?>
    <div class="sb-divider"></div>
    <a class="sb-link active" href="<?= BASE_URL ?>/admin/pengaturan.php" title="Pengaturan">
      <span class="material-icons-round">settings</span>
    </a>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/logout.php" title="Keluar">
      <span class="material-icons-round">logout</span>
    </a>
  </nav>
  <div class="sb-foot">
    <div class="sb-avatar" title="<?= htmlspecialchars($user['nama']) ?> (<?= $roleDisplay ?>)"><?= $avatar ?></div>
  </div>
</aside>

<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <h2><?= $adminTitle ?></h2>
      <p>SITANGKIS — Konfigurasi Profil Pengguna & Informasi Instansi</p>
    </div>
    <div class="topbar-right">
      <div class="top-avatar"><?= $avatar ?></div>
    </div>
  </header>

  <div class="body">

    <div class="card">
      <div class="card-head">
        <span class="card-title">Informasi Akun & Keamanan</span>
        <p>Perbarui data pribadi dan ganti password login Anda secara berkala</p>
      </div>

      <?php if($successAkun): ?><div class="alert alert-success">Selesai: <?= htmlspecialchars($successAkun) ?></div><?php endif; ?>
      <?php if($errorAkun):   ?><div class="alert alert-error">Peringatan: <?= htmlspecialchars($errorAkun) ?></div><?php endif; ?>

      <form method="POST">
        <input type="hidden" name="form" value="akun">
        <div class="form-grid">
          <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" name="nama" value="<?= htmlspecialchars($userData['nama']) ?>" required>
          </div>
          <div class="form-group">
            <label>Email / Username Login</label>
            <input type="email" name="email" value="<?= htmlspecialchars($userData['email']) ?>" required>
          </div>
          <div class="form-group">
            <label>Role Operasional</label>
            <input type="text" value="<?= htmlspecialchars($roleDisplay) ?>" readonly>
            <div class="form-hint">Role tidak dapat diubah sendiri. Hubungi administrator sistem.</div>
          </div>
          <div class="form-group">
            <label>Status Otentikasi</label>
            <input type="text" value="Aktif / Terverifikasi" readonly>
          </div>
        </div>

        <div class="pass-section">
          <h4>Ganti Password</h4>
          <p>Kosongkan semua field password di bawah jika Anda tidak ingin menggantinya</p>
          <div class="form-grid" style="margin-bottom: 0;">
            <div class="form-group full">
              <label>Password Lama</label>
              <input type="password" name="pass_lama" id="passLama" placeholder="Masukkan password saat ini">
            </div>
            <div class="form-group">
              <label>Password Baru</label>
              <input type="password" name="pass_baru" id="passBaru" placeholder="Min. 6 karakter" oninput="checkStrength(this.value)">
              <div class="pass-strength"><div class="pass-fill" id="passFill"></div></div>
              <div class="pass-label" id="passLabel" style="color:#8A929A">Masukkan password baru</div>
            </div>
            <div class="form-group">
              <label>Konfirmasi Password Baru</label>
              <input type="password" name="pass_konfirmasi" id="passKonfirm" placeholder="Ulangi password baru" oninput="checkMatch()">
              <div class="pass-label" id="matchLabel" style="color:#8A929A; min-height: 18px;"></div>
            </div>
          </div>
        </div>

        <div style="margin-top:24px">
          <button type="submit" class="btn-mockup-primary">Simpan Perubahan Akun</button>
        </div>
      </form>
    </div>

    <?php if(hasRole(['kades','sekdes'])): ?>
    <div class="card">
      <div class="card-head">
        <span class="card-title">Informasi Wilayah Desa</span>
        <p>Ubah identitas kelolaan desa yang akan didistribusikan ke halaman publik masyarakat</p>
      </div>
      
      <?php if($successDesa): ?><div class="alert alert-success">Selesai: <?= htmlspecialchars($successDesa) ?></div><?php endif; ?>
      <?php if($errorDesa):   ?><div class="alert alert-error">Peringatan: <?= htmlspecialchars($errorDesa) ?></div><?php endif; ?>
      
      <form method="POST">
        <input type="hidden" name="form" value="desa">
        <div class="form-grid">
          <div class="form-group">
            <label>Nama Desa</label>
            <input type="text" name="nama_desa" value="<?= htmlspecialchars($desa['nama_desa']) ?>">
          </div>
          <div class="form-group">
            <label>Kepala Desa Aktif</label>
            <input type="text" name="kepala_desa" value="<?= htmlspecialchars($desa['kepala_desa']) ?>">
          </div>
          <div class="form-group">
            <label>Tahun Anggaran Aktif</label>
            <select name="tahun_anggaran">
              <?php foreach([2026,2025,2024] as $y): ?>
              <option value="<?= $y ?>" <?= $desa['tahun_anggaran']==$y?'selected':'' ?>><?= $y ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Total APBDes Pagu (Rp)</label>
            <input type="number" name="total_apbdes" value="<?= $desa['total_apbdes'] ?>">
          </div>
        </div>
        <button type="submit" class="btn-mockup-primary">Simpan Data Desa</button>
      </form>
    </div>
    <?php endif; ?>

  </div>
</div>

<script>
function checkStrength(val) {
  const fill = document.getElementById('passFill');
  const label = document.getElementById('passLabel');
  if (!val) { fill.style.width='0'; fill.style.background=''; label.textContent='Masukkan password baru'; label.style.color='#8A929A'; return; }
  let score = 0;
  if (val.length >= 6)  score++;
  if (val.length >= 10) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const levels = [
    {w:'20%', c:'#FF6B6B', t:'Sangat Lemah'},
    {w:'40%', c:'#FFAE34', t:'Lemah'},
    {w:'60%', c:'#FFAE34', t:'Cukup'},
    {w:'80%', c:'#2a9d8f', t:'Kuat'},
    {w:'100%',c:'#2a9d8f', t:'Sangat Kuat'},
  ];
  const lvl = levels[Math.min(score-1, 4)];
  fill.style.width = lvl.w;
  fill.style.background = lvl.c;
  label.textContent = lvl.t;
  label.style.color = lvl.c;
}

function checkMatch() {
  const baru = document.getElementById('passBaru').value;
  const konfirm = document.getElementById('passKonfirm').value;
  const label = document.getElementById('matchLabel');
  if (!konfirm) { label.textContent=''; return; }
  if (baru === konfirm) { label.textContent='Password cocok'; label.style.color='#2a9d8f'; }
  else { label.textContent='Password tidak cocok'; label.style.color='#FF6B6B'; }
}
</script>
</body>
</html>