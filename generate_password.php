<?php
// File ini untuk generate hash password
// Akses sekali di browser: http://localhost/cobaan/sitangkis/generate_password.php
// Setelah selesai, HAPUS file ini

$password = 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "<h2>Hash untuk password: $password</h2>";
echo "<code style='font-size:14px;background:#eee;padding:10px;display:block;margin:10px 0'>$hash</code>";
echo "<br>";
echo "<h3>Jalankan SQL ini di phpMyAdmin:</h3>";
echo "<pre style='background:#1a2332;color:#fff;padding:20px;border-radius:8px'>";
echo "UPDATE users SET password = '$hash' WHERE email = 'bendahara@desa.go.id';\n";
echo "UPDATE users SET password = '$hash' WHERE email = 'sekdes@desa.go.id';\n";
echo "UPDATE users SET password = '$hash' WHERE email = 'kades@desa.go.id';\n";
echo "</pre>";
echo "<br><p style='color:red'><strong>PENTING: Hapus file ini setelah digunakan!</strong></p>";
?>
