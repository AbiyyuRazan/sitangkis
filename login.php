<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) redirect(BASE_URL . '/admin/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$email || !$password) {
        $error = 'Username dan password wajib diisi.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND aktif = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user']    = ['id'=>$user['id'],'nama'=>$user['nama'],'email'=>$user['email'],'role'=>$user['role']];
            redirect(BASE_URL . '/admin/index.php');
        } else {
            $error = 'Username atau password salah. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Admin — <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Plus Jakarta Sans',sans-serif;min-height:100vh;display:flex;background:#fff}

/* LEFT PANEL */
.left{width:45%;background:linear-gradient(160deg,#1a3adb 0%,#1a3a6b 60%,#0f2248 100%);min-height:100vh;display:flex;flex-direction:column;justify-content:space-between;padding:40px 48px;position:relative;overflow:hidden}
.left::before{content:'';position:absolute;top:-80px;right:-80px;width:320px;height:320px;border-radius:50%;background:rgba(255,255,255,.04)}
.left::after{content:'';position:absolute;bottom:-60px;left:-60px;width:240px;height:240px;border-radius:50%;background:rgba(255,255,255,.04)}
.left-top{}
.portal-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);padding:7px 16px;border-radius:20px;margin-bottom:48px}
.portal-dot{width:8px;height:8px;background:#4ade80;border-radius:50%;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
.portal-text{font-size:.75rem;font-weight:700;color:#fff;letter-spacing:.08em}
.brand-row{display:flex;align-items:center;gap:14px;margin-bottom:24px}
.brand-icon{font-size:1.6rem}
.brand-name{font-size:2.6rem;font-weight:800;color:#fff;font-style:italic;letter-spacing:-1px}
.brand-desc{font-size:1rem;color:rgba(255,255,255,.75);line-height:1.6;max-width:380px;font-style:italic}
.left-bottom{}
.security-card{background:rgba(255,255,255,.1);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.15);border-radius:16px;padding:20px 24px;display:flex;align-items:center;gap:16px;margin-bottom:28px}
.sec-icon{width:52px;height:52px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0}
.sec-title{font-weight:700;color:#fff;font-size:.95rem;margin-bottom:3px}
.sec-sub{font-size:.78rem;color:rgba(255,255,255,.6)}
.left-copy{font-size:.72rem;color:rgba(255,255,255,.4);letter-spacing:.06em}

/* RIGHT PANEL */
.right{flex:1;display:flex;align-items:center;justify-content:center;padding:40px;background:#fff}
.form-box{width:100%;max-width:440px}
.form-title{font-size:2rem;font-weight:800;color:#0f172a;margin-bottom:6px;font-style:italic}
.form-underline{width:48px;height:4px;background:#1a3adb;border-radius:2px;margin-bottom:36px}
.field-group{margin-bottom:20px}
.field-label{font-size:.72rem;font-weight:700;color:#64748b;letter-spacing:.07em;text-transform:uppercase;margin-bottom:8px;display:block}
.field-input{position:relative}
.field-input input{width:100%;padding:14px 16px 14px 48px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.92rem;font-family:inherit;outline:none;transition:all .2s;color:#0f172a;background:#fafbff}
.field-input input:focus{border-color:#1a3adb;background:#fff;box-shadow:0 0 0 3px rgba(26,58,219,.08)}
.field-input input::placeholder{color:#cbd5e1}
.field-icon{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:1rem;pointer-events:none}
.toggle-pass{position:absolute;right:16px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;font-size:.95rem;padding:0}
.field-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
.lupa{font-size:.78rem;color:#1a3adb;font-weight:600;text-decoration:none;font-style:italic}
.lupa:hover{text-decoration:underline}
.alert-box{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;font-size:.85rem;color:#dc2626;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.demo-box{background:#f0f7ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px 16px;margin-bottom:24px;font-size:.8rem;color:#1e40af;line-height:1.8}
.demo-box strong{font-weight:700;display:block;margin-bottom:4px}
.demo-box code{background:#dbeafe;padding:2px 7px;border-radius:4px;font-size:.78rem}
.btn-masuk{width:100%;padding:15px;background:#1a3adb;color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:700;font-style:italic;cursor:pointer;font-family:inherit;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:20px}
.btn-masuk:hover{background:#1530bb;transform:translateY(-1px);box-shadow:0 8px 24px rgba(26,58,219,.3)}
.btn-masuk .arr{font-size:1.1rem;font-style:normal}
.back-link{text-align:center;font-size:.85rem;color:#64748b;font-style:italic}
.back-link a{color:#1a3adb;font-weight:700;text-decoration:none}
.back-link a:hover{text-decoration:underline}

@media(max-width:768px){
  .left{display:none}
  .right{padding:32px 24px}
}
</style>
</head>
<body>

<!-- LEFT -->
<div class="left">
  <div class="left-top">
    <div class="portal-badge">
      <div class="portal-dot"></div>
      <span class="portal-text">PORTAL RESMI DESA</span>
    </div>
    <div class="brand-row">
      <span class="brand-icon">≡</span>
      <span class="brand-name">SITANGKIS</span>
    </div>
    <p class="brand-desc">Sistem Transparansi Keuangan Desa.<br>Kelola anggaran dengan cerdas, cepat, dan akuntabel.</p>
  </div>
  <div class="left-bottom">
    <div class="security-card">
      <div class="sec-icon">🔒</div>
      <div>
        <div class="sec-title">Data Protected</div>
        <div class="sec-sub">256-bit AES Encryption</div>
      </div>
    </div>
    <div class="left-copy">© KELOMPOK 6 RPL INFORMATIKA UNSIKA</div>
  </div>
</div>

<!-- RIGHT -->
<div class="right">
  <div class="form-box">
    <h1 class="form-title">Masuk Panel Admin</h1>
    <div class="form-underline"></div>

    <?php if ($error): ?>
    <div class="alert-box">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="field-group">
        <label class="field-label">Username</label>
        <div class="field-input">
          <span class="field-icon">👤</span>
          <input type="email" name="email"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 placeholder="admin_sitangkis" required autofocus>
        </div>
      </div>

      <div class="field-group">
        <div class="field-row">
          <label class="field-label" style="margin-bottom:0">Password</label>
          <a href="#" class="lupa">Lupa Sandi?</a>
        </div>
        <div class="field-input">
          <span class="field-icon">🔑</span>
          <input type="password" name="password" id="passInput" placeholder="••••••••" required>
          <button type="button" class="toggle-pass" onclick="togglePass()">👁️</button>
        </div>
      </div>

      <div class="demo-box">
        <strong>🔑 Akun Demo:</strong>
        🏦 Bendahara: <code>bendahara@desa.go.id</code><br>
        📋 Sekdes: <code>sekdes@desa.go.id</code><br>
        👨‍💼 Kades: <code>kades@desa.go.id</code><br>
        🔒 Password: <code>admin123</code>
      </div>

      <button type="submit" class="btn-masuk">
        <em>Buka Dashboard SITANGKIS</em>
        <span class="arr">→</span>
      </button>
    </form>

    <p class="back-link">Kembali ke halaman utama <a href="<?= BASE_URL ?>/index.php">Beranda Publik</a></p>
  </div>
</div>

<script>
function togglePass() {
  const input = document.getElementById('passInput');
  input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
