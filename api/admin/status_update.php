<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

require_admin($pdo);
$body = read_json_body();
$ticketId = (int)($body['id'] ?? 0);
$status = str_field($body, 'status', 20);

if ($ticketId <= 0 || !in_array($status, ['open', 'answered', 'closed'], true)) {
    json_error('ورودی نامعتبره.');
}

try {
    $upd = $pdo->prepare('UPDATE tickets SET status = :s WHERE id = :id');
    $upd->execute(['s' => $status, 'id' => $ticketId]);

    json_response(['ok' => true]);
} catch (Throwable $e) {
    log_error('admin_status_update', $e);
    json_error('تغییر وضعیت انجام نشد.', 500);
}
