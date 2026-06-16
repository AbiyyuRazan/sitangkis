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
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* GLOBAL SPECIFICATION */
*, *::before, *::after {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}
body {
  font-family: 'Plus Jakarta Sans', sans-serif;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: #F1E3D6; /* Krem Utama dari grafik.php */
  color: #475569;
}

/* CONTAINER FORM CERAH & GLOSSY (MENIRU NAVBAR LIQUID-GLASS) */
.login-container {
  width: 100%;
  max-width: 440px;
  background: rgba(255, 255, 255, 0.85); /* Putih cerah transparan */
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.5);
  border-radius: 24px;
  padding: 44px 36px;
  box-shadow: 0 12px 30px rgba(74, 112, 156, 0.12);
  text-align: center;
}

/* PORTAL BADGE DENGAN DETAIL KECIL WARNA EMAS/ORANYE */
.top-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(145, 185, 182, 0.15); /* Hijau lembut transparan */
  border: 1px solid rgba(145, 185, 182, 0.3);
  padding: 6px 16px;
  border-radius: 20px;
  margin-bottom: 20px;
}
.badge-dot {
  width: 7px;
  height: 7px;
  background: #DFB868; /* Oranye/Emas hanya untuk detail kecil titik aktif */
  border-radius: 50%;
}
.badge-text {
  font-size: 0.75rem;
  font-weight: 700;
  color: #475569;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.form-title {
  font-size: 2rem;
  font-weight: 800;
  color: #475569; /* Teks abu gelap dari judul grafik.php */
  margin-bottom: 32px;
  letter-spacing: -0.03em;
}

/* FORM ELEMENTS STYLING */
.field-group {
  margin-bottom: 24px;
  text-align: left;
}
.field-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #475569;
  margin-bottom: 8px;
  display: block;
}
.field-input {
  position: relative;
}
.field-input input {
  width: 100%;
  padding: 14px 16px 14px 44px;
  border: 1.5px solid #cbd5e1;
  border-radius: 12px;
  font-size: 0.95rem;
  font-family: inherit;
  outline: none;
  transition: all 0.2s;
  color: #0f172a;
  background: #ffffff;
}
.field-input input:focus {
  border-color: #91B9B6; /* Fokus menggunakan Hijau Teal */
  box-shadow: 0 0 0 4px rgba(145, 185, 182, 0.2);
}
.field-input input::placeholder {
  color: #94a3b8;
}
.field-icon {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: 1.1rem;
  pointer-events: none;
}
.toggle-pass {
  position: absolute;
  right: 16px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  color: #94a3b8;
  font-size: 1rem;
  padding: 0;
}

/* TOMBOL MASUK UTAMA MENGGUNAKAN HIJAU TEAL AGAR CERAH DAN SELARAS */
.btn-masuk {
  width: 100%;
  padding: 16px;
  background: #91B9B6; /* Hijau Teal dari komponen grafik.php */
  color: #ffffff;
  border: none;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.2s;
  margin-top: 12px;
  box-shadow: 0 6px 16px rgba(145, 185, 182, 0.25);
}
.btn-masuk:hover {
  background: #7ca8a4;
  transform: translateY(-1px);
  box-shadow: 0 10px 20px rgba(145, 185, 182, 0.35);
}

/* LINK BALIK KE BERANDA */
.back-link {
  font-size: 0.875rem;
  color: #64748b;
  margin-top: 24px;
}
.back-link a {
  color: #475569;
  font-weight: 700;
  text-decoration: none;
  border-bottom: 1.5px solid rgba(71, 85, 105, 0.3);
  transition: border-color 0.2s;
}
.back-link a:hover {
  border-color: #475569;
}

/* ERROR MESSAGE */
.alert-box {
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 10px;
  padding: 12px 16px;
  font-size: 0.875rem;
  color: #dc2626;
  margin-bottom: 24px;
  text-align: left;
  font-weight: 500;
}
</style>
</head>
<body>

<div class="login-container">
  
  <div class="top-badge">
    <div class="badge-dot"></div>
    <span class="badge-text">Panel Autentikasi</span>
  </div>
  
  <h1 class="form-title">Welcome Admin!</h1>

  <?php if ($error): ?>
    <div class="alert-box">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    
    <div class="field-group">
      <label class="field-label">Email Address</label>
      <div class="field-input">
        <span class="field-icon">✉️</span>
        <input type="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="admin@desa.go.id" required autofocus>
      </div>
    </div>

    <div class="field-group">
      <label class="field-label">Password</label>
      <div class="field-input">
        <span class="field-icon">🔒</span>
        <input type="password" name="password" id="passInput" placeholder="••••••••" required>
        <button type="button" class="toggle-pass" onclick="togglePass()">👁️</button>
      </div>
    </div>

    <button type="submit" class="btn-masuk">Log In Account</button>
  </form>

  <p class="back-link">Kembali ke <a href="<?= BASE_URL ?>/index.php">Dashboard Publik</a></p>

</div>

<script>
function togglePass() {
  const input = document.getElementById('passInput');
  input.type = input.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>