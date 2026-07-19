<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

$user = require_user($pdo);
$body = read_json_body();
$ticketId = (int)($body['id'] ?? 0);

if ($ticketId <= 0) {
    json_error('شناسه تیکت نامعتبره.');
}

try {
    $stmt = $pdo->prepare(
        'SELECT id, title, status, created_at FROM tickets WHERE id = :id AND user_id = :uid'
    );
    $stmt->execute(['id' => $ticketId, 'uid' => $user['id']]);
    $ticket = $stmt->fetch();

    // Returning the same "not found" for both a missing ticket and someone
    // else's ticket, so users can't probe which ticket IDs exist.
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
    log_error('ticket_view', $e);
    json_error('خطا در دریافت تیکت.', 500);
}
