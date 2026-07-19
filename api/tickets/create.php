<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

$user = require_user($pdo);
$body = read_json_body();

$title = str_field($body, 'title', 150);
$msgBody = str_field($body, 'body', 4000);

if ($title === '' || $msgBody === '') {
    json_error('عنوان و متن پیام رو وارد کن.');
}

if (!check_rate_limit($pdo, 'ticket_create:' . $user['id'], 5, 600)) {
    json_error('تعداد تیکت‌های ارسالی زیاده. کمی بعد دوباره امتحان کن.', 429);
}

try {
    $pdo->beginTransaction();

    $ins = $pdo->prepare('INSERT INTO tickets (user_id, title, status) VALUES (:uid, :title, "open")');
    $ins->execute(['uid' => $user['id'], 'title' => $title]);
    $ticketId = (int)$pdo->lastInsertId();

    $msgIns = $pdo->prepare(
        'INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, body) VALUES (:tid, "user", :sid, :body)'
    );
    $msgIns->execute(['tid' => $ticketId, 'sid' => $user['id'], 'body' => $msgBody]);

    $pdo->commit();
    json_response(['ok' => true, 'ticket_id' => $ticketId]);
} catch (Throwable $e) {
    $pdo->rollBack();
    log_error('ticket_create', $e);
    json_error('ارسال تیکت انجام نشد. دوباره تلاش کن.', 500);
}
