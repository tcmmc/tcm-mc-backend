<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

$body = read_json_body();

$username = str_field($body, 'username', 32);
$email = mb_strtolower(str_field($body, 'email', 190));
$password = (string)($body['password'] ?? '');

if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $username)) {
    json_error('نام کاربری باید بین ۳ تا ۳۲ کاراکتر و فقط شامل حروف انگلیسی، عدد و زیرخط باشه.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_error('ایمیل معتبر نیست.');
}
if (strlen($password) < 8 || strlen($password) > 72) {
    json_error('رمز عبور باید بین ۸ تا ۷۲ کاراکتر باشه.');
}

// Basic anti-spam throttle per IP for registration attempts.
if (!check_rate_limit($pdo, 'register:' . client_ip(), 10, 600)) {
    json_error('تعداد تلاش‌های ثبت‌نام زیاده. کمی بعد دوباره امتحان کن.', 429);
}

try {
    $check = $pdo->prepare('SELECT id FROM users WHERE username = :u OR email = :e');
    $check->execute(['u' => $username, 'e' => $email]);
    if ($check->fetch()) {
        json_error('این نام کاربری یا ایمیل قبلاً ثبت شده.');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $ins = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (:u, :e, :p)');
    $ins->execute(['u' => $username, 'e' => $email, 'p' => $hash]);
    $userId = (int)$pdo->lastInsertId();

    $token = random_token();
    $tokenIns = $pdo->prepare(
        'INSERT INTO api_tokens (user_id, token, expires_at) VALUES (:uid, :t, DATE_ADD(NOW(), INTERVAL 30 DAY))'
    );
    $tokenIns->execute(['uid' => $userId, 't' => $token]);

    json_response(['token' => $token]);
} catch (Throwable $e) {
    log_error('register', $e);
    json_error('ثبت‌نام انجام نشد. دوباره تلاش کن.', 500);
}
