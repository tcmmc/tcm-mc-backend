<?php
// functions.php — shared helpers used across every endpoint.

declare(strict_types=1);

function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, int $status = 400): void {
    json_response(['message' => $message], $status);
}

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '[]', true);
    return is_array($data) ? $data : [];
}

function str_field(array $body, string $key, int $maxLen = 255): string {
    $val = isset($body[$key]) ? (string)$body[$key] : '';
    $val = trim($val);
    if (mb_strlen($val) > $maxLen) {
        $val = mb_substr($val, 0, $maxLen);
    }
    return $val;
}

function random_token(int $bytes = 32): string {
    return bin2hex(random_bytes($bytes));
}

/**
 * Reads the Bearer token from the Authorization header. Some hosts strip this
 * header, so we also check a couple of common fallbacks that Railway/Apache
 * may expose it under.
 */
function bearer_token(): ?string {
    $header = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';

    if ($header === '' && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
        return $m[1];
    }
    return null;
}

/**
 * Validates a user auth token against api_tokens, returns the user row or null.
 * Also enforces token expiry.
 */
function authenticate_user(PDO $pdo): ?array {
    $token = bearer_token();
    if (!$token) return null;

    $stmt = $pdo->prepare(
        'SELECT u.id, u.username, u.email
         FROM api_tokens t
         JOIN users u ON u.id = t.user_id
         WHERE t.token = :token AND t.expires_at > NOW()'
    );
    $stmt->execute(['token' => $token]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function require_user(PDO $pdo): array {
    $user = authenticate_user($pdo);
    if (!$user) {
        json_error('لطفاً دوباره وارد حساب بشو.', 401);
    }
    return $user;
}

/** Same idea, but for the separate admins table / admin_tokens. */
function authenticate_admin(PDO $pdo): ?array {
    $token = bearer_token();
    if (!$token) return null;

    $stmt = $pdo->prepare(
        'SELECT a.id, a.username
         FROM admin_tokens t
         JOIN admins a ON a.id = t.admin_id
         WHERE t.token = :token AND t.expires_at > NOW()'
    );
    $stmt->execute(['token' => $token]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function require_admin(PDO $pdo): array {
    $admin = authenticate_admin($pdo);
    if (!$admin) {
        json_error('دسترسی ادمین لازمه.', 401);
    }
    return $admin;
}

/**
 * Simple sliding-window rate limiter backed by the `rate_limits` table.
 * $key should uniquely identify the actor+action, e.g. "login:1.2.3.4:user@x.com".
 * Returns true if the action is allowed, false if the caller should be blocked.
 */
function check_rate_limit(PDO $pdo, string $key, int $maxAttempts, int $windowSeconds): bool {
    $stmt = $pdo->prepare('SELECT attempts, window_started_at FROM rate_limits WHERE rate_key = :k');
    $stmt->execute(['k' => $key]);
    $row = $stmt->fetch();

    $now = time();

    if (!$row) {
        $ins = $pdo->prepare(
            'INSERT INTO rate_limits (rate_key, attempts, window_started_at) VALUES (:k, 1, NOW())'
        );
        $ins->execute(['k' => $key]);
        return true;
    }

    $windowStart = strtotime($row['window_started_at']);
    if ($now - $windowStart > $windowSeconds) {
        // window expired, reset it
        $upd = $pdo->prepare(
            'UPDATE rate_limits SET attempts = 1, window_started_at = NOW() WHERE rate_key = :k'
        );
        $upd->execute(['k' => $key]);
        return true;
    }

    if ((int)$row['attempts'] >= $maxAttempts) {
        return false;
    }

    $upd = $pdo->prepare('UPDATE rate_limits SET attempts = attempts + 1 WHERE rate_key = :k');
    $upd->execute(['k' => $key]);
    return true;
}

function client_ip(): string {
    return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function log_error(string $context, \Throwable $e): void {
    // Log to PHP's error log (visible in Railway's deploy logs) without ever
    // echoing internals back to the client.
    error_log('[' . $context . '] ' . $e->getMessage());
}
