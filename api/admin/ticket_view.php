<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

require_admin($pdo);
$body = read_json_body();
$ticketId = (int)($body['id'] ?? 0);

if ($ticketId <= 0) {
    json_error('شناسه تیکت نامعتبره.');
}

try {
    $stmt = $pdo->prepare(
        'SELECT t.id, t.title, t.status, t.created_at, u.username, u.email
         FROM tickets t JOIN users u ON u.id = t.user_id
         WHERE t.id = :id'
    );
    $stmt->execute(['id' => $ticketId]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        json_error('این تیکت پیدا نشد.', 404);
    }

    $msgStmt = $pdo->prepare(
        'SELECT sender_type, body, created_at FROM ticket_messages WHERE ticket_id = :tid ORDER BY created_at ASC'
    );
    $msgStmt->execute(['tid' => $ticketId]);
    $messages = $msgStmt->fetchAll();

    json_response(['ticket' => $ticket, 'messages' => $messages]);
} catch (Throwable $e) {
    log_error('admin_ticket_view', $e);
    json_error('خطا در دریافت تیکت.', 500);
}
