<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

$user = require_user($pdo);

try {
    $stmt = $pdo->prepare(
        'SELECT id, title, status, created_at FROM tickets WHERE user_id = :uid ORDER BY created_at DESC'
    );
    $stmt->execute(['uid' => $user['id']]);
    $tickets = $stmt->fetchAll();

    json_response(['tickets' => $tickets]);
} catch (Throwable $e) {
    log_error('tickets_list', $e);
    json_error('خطا در دریافت تیکت‌ها.', 500);
}
