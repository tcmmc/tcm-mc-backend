<?php
// config.php — database connection + environment settings.
// Reads everything from environment variables (set these in Railway's
// service "Variables" tab). Never hardcode real credentials in this file.

declare(strict_types=1);

function env_or_fail(string $key): string {
    $val = getenv($key);
    if ($val === false || $val === '') { $val = $_SERVER[$key] ?? ''; }
    if ($val === '') { $val = $_ENV[$key] ?? ''; }
    if ($val === '') {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['message' => "Server misconfigured: missing env $key"]);
        exit;
    }
    return $val;
}

function env_optional(string $key, string $fallback = ''): string {
    $val = getenv($key);
    if ($val === false || $val === '') { $val = $_SERVER[$key] ?? ''; }
    if ($val === '') { $val = $_ENV[$key] ?? ''; }
    return $val !== '' ? $val : $fallback;
}

$DB_HOST = env_or_fail('DB_HOST');
$DB_NAME = env_or_fail('DB_NAME');
$DB_USER = env_or_fail('DB_USER');
$DB_PASS = env_or_fail('DB_PASS');
$DB_PORT = env_optional('DB_PORT', '3306');

// Comma-separated list of allowed frontend origins, e.g.
// "https://tcmmc.github.io" (no trailing slash). Set this in Railway.
$ALLOWED_ORIGINS = array_filter(array_map('trim', explode(',', env_optional('ALLOWED_ORIGINS'))));

// Secret used to derive/verify auth tokens. Set a long random string in Railway.
$APP_SECRET = env_or_fail('APP_SECRET');

// One-time setup key used only by setup_admin.php to create the first admin account.
$ADMIN_SETUP_KEY = env_optional('ADMIN_SETUP_KEY');

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['message' => 'Database connection failed.']);
    exit;
}
