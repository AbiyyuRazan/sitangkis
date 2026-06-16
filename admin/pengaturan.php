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
            // Proses ganti password
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
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f4f6fb;color:#1a2332;min-height:100vh;display:flex}
.sidebar{width:240px;background:#1a3a6b;min-height:100vh;display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100}
.sb-top{padding:24px 20px 16px;border-bottom:1px solid rgba(255,255,255,.08)}
.sb-logo{display:flex;align-items:center;gap:10px}
.sb-logo-icon{width:36px;height:36px;background:#e63946;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
.sb-logo-text{font-weight:800;font-size:1.05rem;color:#fff}
.sb-logo-sub{font-size:.62rem;color:rgba(255,255,255,.4);margin-top:1px}
.sb-nav{flex:1;padding:16px 12px;display:flex;flex-direction:column;gap:2px}
.sb-link{display:flex;align-items:center;gap:11px;padding:11px 14px;border-radius:10px;color:rgba(255,255,255,.65);text-decoration:none;font-size:.875rem;font-weight:500;transition:all .18s}
.sb-link:hover{background:rgba(255,255,255,.08);color:#fff}
.sb-link.active{background:rgba(255,255,255,.15);color:#fff;font-weight:700}
.sb-divider{height:1px;background:rgba(255,255,255,.08);margin:8px 0}
.sb-foot{padding:12px}
.sb-user{display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:10px;background:rgba(255,255,255,.07)}
.sb-avatar{width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.2);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.8rem;flex-shrink:0}
.sb-uname{font-size:.8rem;font-weight:600;color:#fff}
.sb-urole{font-size:.68rem;color:rgba(255,255,255,.45)}
.main{margin-left:240px;flex:1;display:flex;flex-direction:column}
.topbar{background:#fff;padding:0 32px;height:64px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e8edf5;position:sticky;top:0;z-index:50}
.topbar h2{font-size:1.15rem;font-weight:800}
.top-avatar{width:38px;height:38px;border-radius:50%;background:#1a3a6b;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.82rem}
.body{padding:28px 32px;max-width:760px}
/* CARDS */
.card{background:#fff;border-radius:16px;padding:28px;border:1px solid #e8edf5;box-shadow:0 1px 4px rgba(0,0,0,.04);margin-bottom:24px}
.card-head{margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid #f0f4f8}
.card-head h3{font-size:1rem;font-weight:800;color:#1a2332;margin-bottom:3px}
.card-head p{font-size:.8rem;color:#64748b}
/* FORM */
.fg{margin-bottom:16px}
.fg label{display:block;font-size:.78rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:7px}
.fg input,.fg select{width:100%;padding:12px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.9rem;font-family:inherit;outline:none;transition:all .2s;background:#fafbff;color:#1a2332}
.fg input:focus,.fg select:focus{border-color:#1a3a6b;background:#fff;box-shadow:0 0 0 3px rgba(26,58,107,.07)}
.fg input[readonly]{background:#f4f6fb;color:#94a3b8;cursor:not-allowed}
.fg .hint{font-size:.73rem;color:#94a3b8;margin-top:4px}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.full{grid-column:1/-1}
/* PASSWORD SECTION */
.pass-section{background:#f8faff;border:1px solid #e0e8ff;border-radius:12px;padding:20px;margin-top:8px}
.pass-section h4{font-size:.85rem;font-weight:700;color:#1a3a6b;margin-bottom:4px}
.pass-section p{font-size:.78rem;color:#64748b;margin-bottom:16px}
.pass-strength{height:5px;border-radius:99px;background:#e2e8f0;margin-top:6px;overflow:hidden}
.pass-fill{height:100%;border-radius:99px;transition:all .3s}
.pass-label{font-size:.72rem;margin-top:4px;font-weight:600}
/* BUTTONS */
.btn{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:10px;font-size:.88rem;font-weight:700;cursor:pointer;transition:all .2s;border:none;font-family:inherit}
.btn-primary{background:#1a3a6b;color:#fff}
.btn-primary:hover{background:#2451a3}
/* ALERT */
.alert{padding:12px 16px;border-radius:10px;font-size:.85rem;font-weight:600;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.alert-success{background:#d1fae5;color:#065f46}
.alert-error{background:#fee2e2;color:#991b1b}
/* BADGE ROLE */
.role-badge{display:inline-block;padding:5px 14px;border-radius:20px;font-size:.78rem;font-weight:700;background:#dbeafe;color:#1e40af}
</style>
</head>
<body>
<aside class="sidebar">
  <div class="sb-top">
    <div class="sb-logo">
      <div class="sb-logo-icon">🏛️</div>
      <div><div class="sb-logo-text"><?= APP_NAME ?></div><div class="sb-logo-sub">Admin Panel</div></div>
    </div>
  </div>
  <nav class="sb-nav">
    <a class="sb-link" href="<?= BASE_URL ?>/admin/index.php"><span>📊</span> Dashboard</a>
    <?php if(hasRole(['bendahara','sekdes'])): ?>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/kelola_anggaran.php"><span>📋</span> Kelola Anggaran</a>
    <?php endif; ?>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/realisasi_dana.php"><span>💸</span> Realisasi Dana</a>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/laporan.php"><span>📄</span> Laporan Publik</a>
    <?php if(hasRole(['sekdes','kades'])): ?>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/grafik.php"><span>📈</span> Grafik</a>
    <?php endif; ?>
    <div class="sb-divider"></div>
    <a class="sb-link active" href="<?= BASE_URL ?>/admin/pengaturan.php"><span>⚙️</span> Pengaturan</a>
    <a class="sb-link" href="<?= BASE_URL ?>/admin/logout.php"><span>🚪</span> Keluar</a>
  </nav>
  <div class="sb-foot">
    <div class="sb-user">
      <div class="sb-avatar"><?= $avatar ?></div>
      <div><div class="sb-uname"><?= htmlspecialchars($user['nama']) ?></div><div class="sb-urole"><?= $roleDisplay ?></div></div>
    </div>
  </div>
</aside>

<div class="main">
  <header class="topbar">
    <h2>⚙️ Pengaturan Akun</h2>
    <div class="top-avatar"><?= $avatar ?></div>
  </header>
  <div class="body">

    <!-- FORM AKUN + PASSWORD -->
    <div class="card">
      <div class="card-head">
        <h3>👤 Informasi Akun & Keamanan</h3>
        <p>Perbarui data pribadi dan ganti password login Anda</p>
      </div>

      <?php if($successAkun): ?><div class="alert alert-success">✅ <?= htmlspecialchars($successAkun) ?></div><?php endif; ?>
      <?php if($errorAkun):   ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($errorAkun) ?></div><?php endif; ?>

      <form method="POST">
        <input type="hidden" name="form" value="akun">
        <div class="grid2">
          <div class="fg">
            <label>Nama Lengkap</label>
            <input type="text" name="nama" value="<?= htmlspecialchars($userData['nama']) ?>" required>
          </div>
          <div class="fg">
            <label>Email / Username Login</label>
            <input type="email" name="email" value="<?= htmlspecialchars($userData['email']) ?>" required>
          </div>
          <div class="fg">
            <label>Role</label>
            <input type="text" value="<?= htmlspecialchars($roleDisplay) ?>" readonly>
            <div class="hint">Role tidak dapat diubah sendiri. Hubungi administrator sistem.</div>
          </div>
          <div class="fg">
            <label>Status Akun</label>
            <input type="text" value="✅ Aktif" readonly>
          </div>
        </div>

        <!-- GANTI PASSWORD -->
        <div class="pass-section">
          <h4>🔐 Ganti Password</h4>
          <p>Kosongkan semua field password jika tidak ingin menggantinya</p>
          <div class="grid2">
            <div class="fg full">
              <label>Password Lama</label>
              <input type="password" name="pass_lama" id="passLama" placeholder="Masukkan password saat ini">
            </div>
            <div class="fg">
              <label>Password Baru</label>
              <input type="password" name="pass_baru" id="passBaru" placeholder="Min. 6 karakter" oninput="checkStrength(this.value)">
              <div class="pass-strength"><div class="pass-fill" id="passFill"></div></div>
              <div class="pass-label" id="passLabel" style="color:#94a3b8">Masukkan password baru</div>
            </div>
            <div class="fg">
              <label>Konfirmasi Password Baru</label>
              <input type="password" name="pass_konfirmasi" id="passKonfirm" placeholder="Ulangi password baru" oninput="checkMatch()">
              <div class="pass-label" id="matchLabel" style="color:#94a3b8"></div>
            </div>
          </div>
        </div>

        <div style="margin-top:20px">
          <button type="submit" class="btn btn-primary">💾 Simpan Perubahan Akun</button>
        </div>
      </form>
    </div>

    <!-- FORM DESA (khusus kades/sekdes) -->
    <?php if(hasRole(['kades','sekdes'])): ?>
    <div class="card">
      <div class="card-head">
        <h3>🏡 Informasi Desa</h3>
        <p>Ubah data desa yang tampil di halaman publik</p>
      </div>
      <?php if($successDesa): ?><div class="alert alert-success">✅ <?= htmlspecialchars($successDesa) ?></div><?php endif; ?>
      <?php if($errorDesa):   ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($errorDesa) ?></div><?php endif; ?>
      <form method="POST">
        <input type="hidden" name="form" value="desa">
        <div class="grid2">
          <div class="fg"><label>Nama Desa</label><input type="text" name="nama_desa" value="<?= htmlspecialchars($desa['nama_desa']) ?>"></div>
          <div class="fg"><label>Kepala Desa</label><input type="text" name="kepala_desa" value="<?= htmlspecialchars($desa['kepala_desa']) ?>"></div>
          <div class="fg">
            <label>Tahun Anggaran Aktif</label>
            <select name="tahun_anggaran">
              <?php foreach([2026,2025,2024] as $y): ?>
              <option value="<?= $y ?>" <?= $desa['tahun_anggaran']==$y?'selected':'' ?>><?= $y ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fg"><label>Total APBDes (Rp)</label><input type="number" name="total_apbdes" value="<?= $desa['total_apbdes'] ?>"></div>
        </div>
        <button type="submit" class="btn btn-primary">💾 Simpan Data Desa</button>
      </form>
    </div>
    <?php endif; ?>

  </div>
</div>

<script>
function checkStrength(val) {
  const fill = document.getElementById('passFill');
  const label = document.getElementById('passLabel');
  if (!val) { fill.style.width='0'; fill.style.background=''; label.textContent='Masukkan password baru'; label.style.color='#94a3b8'; return; }
  let score = 0;
  if (val.length >= 6)  score++;
  if (val.length >= 10) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const levels = [
    {w:'20%', c:'#ef4444', t:'Sangat Lemah'},
    {w:'40%', c:'#f97316', t:'Lemah'},
    {w:'60%', c:'#eab308', t:'Cukup'},
    {w:'80%', c:'#22c55e', t:'Kuat'},
    {w:'100%',c:'#16a34a', t:'Sangat Kuat'},
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
  if (baru === konfirm) { label.textContent='✅ Password cocok'; label.style.color='#16a34a'; }
  else { label.textContent='❌ Password tidak cocok'; label.style.color='#ef4444'; }
}
</script>
</body>
</html>
