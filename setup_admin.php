<?php
// setup_admin.php — creates the FIRST admin account. Gated by ADMIN_SETUP_KEY
// so a random visitor can't create their own admin login.
//
// Usage: after deploying, POST to /setup_admin.php with:
//   { "setup_key": "...", "username": "...", "password": "..." }
//
// IMPORTANT: after you've created your admin account, remove the
// ADMIN_SETUP_KEY variable in Railway (or delete this file entirely) so it
// can never be used again.

declare(strict_types=1);
require_once __DIR__ . '/cors.php';
apply_cors();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed.', 405);
}

if ($ADMIN_SETUP_KEY === '') {
    json_error('این مسیر غیرفعاله. (ADMIN_SETUP_KEY تنظیم نشده)', 403);
}

$body = read_json_body();
$setupKey = (string)($body['setup_key'] ?? '');
$username = str_field($body, 'username', 32);
$password = (string)($body['password'] ?? '');

if (!hash_equals($ADMIN_SETUP_KEY, $setupKey)) {
    json_error('کلید راه‌اندازی اشتباهه.', 403);
}
if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $username)) {
    json_error('نام کاربری نامعتبره.');
}
if (strlen($password) < 8) {
    json_error('رمز عبور باید حداقل ۸ کاراکتر باشه.');
}

try {
    $check = $pdo->prepare('SELECT id FROM admins WHERE username = :u');
    $check->execute(['u' => $username]);
    if ($check->fetch()) {
        json_error('ادمینی با این نام کاربری از قبل وجود داره.');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (:u, :p)');
    $ins->execute(['u' => $username, 'p' => $hash]);

    json_response(['ok' => true, 'message' => 'ادمین ساخته شد. حالا ADMIN_SETUP_KEY رو حذف کن.']);
} catch (Throwable $e) {
    log_error('setup_admin', $e);
    json_error('ساخت ادمین انجام نشد.', 500);
}
