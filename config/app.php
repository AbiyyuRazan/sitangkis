<?php
// config/app.php

define('APP_NAME', 'SITANGKIS');
define('APP_DESC', 'Sistem Transparansi Keuangan Desa');

// AUTO-DETECT BASE_URL — tidak perlu diubah manual
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script   = $_SERVER['SCRIPT_NAME'] ?? '';
// Cari folder root proyek (sitangkis)
$parts    = explode('/', trim($script, '/'));
$base     = '';
// Jika ada subfolder (misal /sitangkis/index.php), ambil foldernya
if (count($parts) > 1) {
    // Cari root folder project dari __DIR__
    $rootFolder = basename(dirname(dirname(__FILE__))); // nama folder sitangkis
    $idx = array_search($rootFolder, $parts);
    if ($idx !== false) {
        $base = '/' . implode('/', array_slice($parts, 0, $idx + 1));
    }
}
define('BASE_URL', $base);

define('UPLOAD_DIR', __DIR__ . '/../public/uploads/');
define('UPLOAD_URL', BASE_URL . '/public/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);

if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(): bool { return isset($_SESSION['user_id']); }
function currentUser(): array { return $_SESSION['user'] ?? []; }
function hasRole(string|array $roles): bool {
    $user = currentUser();
    if (empty($user)) return false;
    return in_array($user['role'], (array)$roles);
}
function redirect(string $url): void { header("Location: $url"); exit; }
function setFlash(string $type, string $msg): void { $_SESSION['flash'] = ['type'=>$type,'msg'=>$msg]; }
function getFlash(): array { $f=$_SESSION['flash']??[]; unset($_SESSION['flash']); return $f; }
function rupiah(int|float $n): string { return 'Rp '.number_format($n,0,',','.'); }
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function verifyCsrf(): void {
    if (!hash_equals($_SESSION['csrf_token']??'', $_POST['csrf_token']??'')) {
        setFlash('error','Token tidak valid.'); header('Location: '.BASE_URL.'/admin/index.php'); exit;
    }
}
function clean(string $val): string { return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8'); }
