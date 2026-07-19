<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

$user = require_user($pdo);
$body = read_json_body();

$currentPassword = (string)($body['current_password'] ?? '');
$newPassword = (string)($body['new_password'] ?? '');

if (strlen($newPassword) < 8 || strlen($newPassword) > 72) {
    json_error('رمز جدید باید بین ۸ تا ۷۲ کاراکتر باشه.');
}

try {
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
    $stmt->execute(['id' => $user['id']]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
        json_error('رمز فعلی اشتباهه.', 401);
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $upd = $pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
    $upd->execute(['h' => $newHash, 'id' => $user['id']]);

    // Invalidate all existing sessions for this user except nothing special is
    // needed client-side since the current token stays valid; this is a
    // reasonable tradeoff for simplicity. To force re-login everywhere,
    // uncomment the next two lines.
    // $del = $pdo->prepare('DELETE FROM api_tokens WHERE user_id = :id');
    // $del->execute(['id' => $user['id']]);

    json_response(['ok' => true]);
} catch (Throwable $e) {
    log_error('change_password', $e);
    json_error('تغییر رمز انجام نشد. دوباره تلاش کن.', 500);
}
