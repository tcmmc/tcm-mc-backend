<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

$token = bearer_token();
if ($token) {
    try {
        $del = $pdo->prepare('DELETE FROM admin_tokens WHERE token = :t');
        $del->execute(['t' => $token]);
    } catch (Throwable $e) {
        log_error('admin_logout', $e);
    }
}

json_response(['ok' => true]);
