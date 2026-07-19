<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

$body = read_json_body();
$identity = str_field($body, 'identity', 190);
$password = (string)($body['password'] ?? '');

if ($identity === '' || $password === '') {
    json_error('نام کاربری/ایمیل و رمز عبور رو وارد کن.');
}

$rateKey = 'login:' . client_ip() . ':' . mb_strtolower($identity);
if (!check_rate_limit($pdo, $rateKey, 8, 300)) {
    json_error('تعداد تلاش‌های ورود ناموفق زیاده. چند دقیقه دیگه دوباره امتحان کن.', 429);
}

try {
    $stmt = $pdo->prepare('SELECT id, username, email, password_hash FROM users WHERE username = :i OR email = :i2');
    $stmt->execute(['i' => $identity, 'i2' => mb_strtolower($identity)]);
    $user = $stmt->fetch();

    // Deliberately generic error for both "no such user" and "wrong password"
    // so the API never reveals which emails/usernames exist.
    if (!$user || !password_verify($password, $user['password_hash'])) {
        json_error('نام کاربری یا رمز عبور اشتباهه.', 401);
    }

    $token = random_token();
    $tokenIns = $pdo->prepare(
        'INSERT INTO api_tokens (user_id, token, expires_at) VALUES (:uid, :t, DATE_ADD(NOW(), INTERVAL 30 DAY))'
    );
    $tokenIns->execute(['uid' => $user['id'], 't' => $token]);

    json_response(['token' => $token]);
} catch (Throwable $e) {
    log_error('login', $e);
    json_error('ورود انجام نشد. دوباره تلاش کن.', 500);
}
