<?php
// setup.php — Jalankan SEKALI untuk set password dan data awal
// Akses: http://localhost/cobaan/sitangkis/setup.php
// HAPUS file ini setelah selesai!

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

$db = getDB();
$hash = password_hash('admin123', PASSWORD_BCRYPT);

// Update semua password user
$db->prepare("UPDATE users SET password = ? WHERE email = 'bendahara@desa.go.id'")->execute([$hash]);
$db->prepare("UPDATE users SET password = ? WHERE email = 'sekdes@desa.go.id'")->execute([$hash]);
$db->prepare("UPDATE users SET password = ? WHERE email = 'kades@desa.go.id'")->execute([$hash]);

// Verifikasi
$users = $db->query("SELECT nama, email, role FROM users")->fetchAll();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>
<style>body{font-family:sans-serif;max-width:600px;margin:60px auto;padding:20px}
.ok{background:#d1fae5;color:#065f46;padding:16px;border-radius:10px;margin:8px 0}
.warn{background:#fee2e2;color:#991b1b;padding:16px;border-radius:10px;margin-top:20px}
table{width:100%;border-collapse:collapse;margin-top:16px}
th,td{padding:10px 14px;text-align:left;border-bottom:1px solid #e2e8f0}
th{background:#f0f4f8;font-size:.85rem}
code{background:#e2e8f0;padding:3px 8px;border-radius:4px}
</style></head><body>";

echo "<h2>✅ Setup Berhasil!</h2>";
echo "<div class='ok'>Password semua akun berhasil di-set ke <strong>admin123</strong></div>";

echo "<h3 style='margin-top:24px'>Akun yang tersedia:</h3>";
echo "<table><tr><th>Nama</th><th>Email</th><th>Role</th></tr>";
foreach ($users as $u) {
    echo "<tr><td>{$u['nama']}</td><td><code>{$u['email']}</code></td><td>{$u['role']}</td></tr>";
}
echo "</table>";

echo "<br><p>Password: <code>admin123</code></p>";
echo "<br><a href='login.php' style='display:inline-block;background:#1a3a6b;color:#fff;padding:12px 24px;border-radius:10px;text-decoration:none;font-weight:600'>→ Pergi ke Halaman Login</a>";

echo "<div class='warn'>⚠️ <strong>PENTING:</strong> Hapus file <code>setup.php</code> ini setelah selesai digunakan!</div>";
echo "</body></html>";
?>
