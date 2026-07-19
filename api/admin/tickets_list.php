<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

require_admin($pdo);
$body = read_json_body();
$status = str_field($body, 'status', 20);

try {
    if (in_array($status, ['open', 'answered', 'closed'], true)) {
        $stmt = $pdo->prepare(
            'SELECT t.id, t.title, t.status, t.created_at, u.username
             FROM tickets t JOIN users u ON u.id = t.user_id
             WHERE t.status = :status
             ORDER BY t.created_at DESC'
        );
        $stmt->execute(['status' => $status]);
    } else {
        $stmt = $pdo->query(
            'SELECT t.id, t.title, t.status, t.created_at, u.username
             FROM tickets t JOIN users u ON u.id = t.user_id
             ORDER BY t.created_at DESC'
        );
    }
    $tickets = $stmt->fetchAll();

    json_response(['tickets' => $tickets]);
} catch (Throwable $e) {
    log_error('admin_tickets_list', $e);
    json_error('خطا در دریافت تیکت‌ها.', 500);
}
