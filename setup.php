<?php
// setup.php — Jalankan SEKALI untuk set password dan data awal
// Akses Resmi: http://localhost/RPL LAST SOLID/setup.php
// ATAU jika pakai Virtual Host Laragon: http://rpl-last-solid.test/setup.php
// PENTING: HAPUS file ini dari folder setelah berhasil login dashboard!

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

$db = getDB();
$hash = password_hash('admin123', PASSWORD_BCRYPT);

try {
    // Update semua password user dari 'GANTI_HASH' menjadi Bcrypt asli dari admin123
    $db->prepare("UPDATE users SET password = ? WHERE email = 'bendahara@desa.go.id'")->execute([$hash]);
    $db->prepare("UPDATE users SET password = ? WHERE email = 'sekdes@desa.go.id'")->execute([$hash]);
    $db->prepare("UPDATE users SET password = ? WHERE email = 'kades@desa.go.id'")->execute([$hash]);
    
    // Memastikan baris data akun demo berstatus aktif (aktif = 1) agar lolos validasi di login.php
    $db->query("UPDATE users SET aktif = 1 WHERE email IN ('bendahara@desa.go.id', 'sekdes@desa.go.id', 'kades@desa.go.id')");

    // Ambil data user terupdate untuk diverifikasi pada tabel di browser
    $users = $db->query("SELECT nama, email, role, aktif FROM users")->fetchAll();
    $setupSuccess = true;
} catch (PDOException $e) {
    $setupSuccess = false;
    $errorMessage = $e->getMessage();
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>
<title>Setup Database — SITANGKIS Kelompok 6</title>
<style>
body{font-family:sans-serif;max-width:600px;margin:60px auto;padding:20px;background:#f8fafc;color:#334155}
.container{background:#ffffff;padding:32px;border-radius:14px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);border:1px solid #e2e8f0}
.ok{background:#d1fae5;color:#065f46;padding:16px;border-radius:10px;margin:8px 0;font-weight:500}
.warn{background:#fff7ed;color:#9a3412;padding:16px;border-radius:10px;margin-top:24px;border:1px solid #fed7aa;font-size:0.87rem}
table{width:100%;border-collapse:collapse;margin-top:16px}
th,td{padding:12px 14px;text-align:left;border-bottom:1px solid #e2e8f0}
th{background:#f1f5f9;font-size:.82rem;text-transform:uppercase;letter-spacing:0.05em;color:#475569}
td{font-size:0.9rem}
code{background:#e2e8f0;padding:3px 8px;border-radius:4px;font-family:monospace;font-size:0.85rem;color:#0f172a}
.btn-action{display:inline-block;background:#1a3adb;color:#fff;padding:14px 28px;border-radius:10px;text-decoration:none;font-weight:600;font-size:0.95rem;margin-top:20px;transition:background 0.2s}
.btn-action:hover{background:#1530bb}
</style></head><body>";

echo "<div class='container'>";

if ($setupSuccess) {
    echo "<h2>✅ Setup Database Berhasil!</h2>";
    echo "<div class='ok'>Password semua akun demo berhasil di-enkripsi menjadi <strong>admin123</strong> dan status diaktifkan!</div>";

    echo "<h3 style='margin-top:28px;color:#1e293b;'>Akun Kelompok 6 yang Siap Digunakan:</h3>";
    echo "<table><tr><th>Nama Warga</th><th>Email Login</th><th>Hak Akses / Role</th><th>Status</th></tr>";
    foreach ($users as $u) {
        $statusBadge = $u['aktif'] == 1 ? "<span style='color:#16a34a;font-weight:bold;'>Sesuai (Aktif)</span>" : "<span style='color:#dc2626;font-weight:bold;'>Nonaktif</span>";
        echo "<tr><td>{$u['nama']}</td><td><code>{$u['email']}</code></td><td><strong>{$u['role']}</strong></td><td>{$u['aktif']} — $statusBadge</td></tr>";
    }
    echo "</table>";

    echo "<br><p style='font-size:0.95rem;'>Kata Sandi Penguji: <code>admin123</code></p>";
    
    // PENGIKAT UTAMA LINK AMAN: Memakai BASE_URL global agar link tombol dinamis otomatis ke /RPL LAST SOLID/login.php
    $loginUrl = defined('BASE_URL') ? BASE_URL . '/login.php' : 'login.php';
    echo "<a href='{$loginUrl}' class='btn-action'>→ Masuk ke Halaman Login Admin</a>";
} else {
    echo "<h2 style='color:#dc2626;'>❌ Setup Gagal Terkoneksi</h2>";
    echo "<div style='background:#fef2f2;color:#991b1b;padding:16px;border-radius:10px;margin:8px 0;'>Error: <code>{$errorMessage}</code></div>";
    echo "<p style='margin-top:16px;'>Periksa kembali setelan konfigurasi kredensial database kamu di folder <code>config/database.php</code>.</p>";
}

echo "<div class='warn'>⚠️ <strong>PENTING SEBELUM PUSH:</strong> Demi keamanan nilai proyek kelompok di GitHub, silakan hapus file <code>setup.php</code> ini setelah kamu berhasil uji coba login ke dashboard!</div>";
echo "</div>";
echo "</body></html>";
?>