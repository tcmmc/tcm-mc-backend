<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

$admin = require_admin($pdo);
$body = read_json_body();
$ticketId = (int)($body['id'] ?? 0);
$msgBody = str_field($body, 'body', 4000);

if ($ticketId <= 0 || $msgBody === '') {
    json_error('متن پاسخ رو وارد کن.');
}

try {
    $check = $pdo->prepare('SELECT id FROM tickets WHERE id = :id');
    $check->execute(['id' => $ticketId]);
    if (!$check->fetch()) {
        json_error('این تیکت پیدا نشد.', 404);
    }

    $pdo->beginTransaction();

    $ins = $pdo->prepare(
        'INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, body) VALUES (:tid, "admin", :aid, :body)'
    );
    $ins->execute(['tid' => $ticketId, 'aid' => $admin['id'], 'body' => $msgBody]);

    $upd = $pdo->prepare('UPDATE tickets SET status = "answered" WHERE id = :id');
    $upd->execute(['id' => $ticketId]);

    $pdo->commit();
    json_response(['ok' => true]);
} catch (Throwable $e) {
    $pdo->rollBack();
    log_error('admin_reply', $e);
    json_error('ارسال پاسخ انجام نشد. دوباره تلاش کن.', 500);
}
