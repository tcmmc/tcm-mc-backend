<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

$body = read_json_body();
$username = str_field($body, 'username', 32);
$password = (string)($body['password'] ?? '');

if ($username === '' || $password === '') {
    json_error('نام کاربری و رمز عبور رو وارد کن.');
}

if (!check_rate_limit($pdo, 'admin_login:' . client_ip(), 6, 300)) {
    json_error('تعداد تلاش‌های ورود ناموفق زیاده. چند دقیقه دیگه دوباره امتحان کن.', 429);
}

try {
    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = :u');
    $stmt->execute(['u' => $username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        json_error('نام کاربری یا رمز عبور اشتباهه.', 401);
    }

    $token = random_token();
    $ins = $pdo->prepare(
        'INSERT INTO admin_tokens (admin_id, token, expires_at) VALUES (:aid, :t, DATE_ADD(NOW(), INTERVAL 7 DAY))'
    );
    $ins->execute(['aid' => $admin['id'], 't' => $token]);

    json_response(['token' => $token]);
} catch (Throwable $e) {
    log_error('admin_login', $e);
    json_error('ورود انجام نشد. دوباره تلاش کن.', 500);
}
