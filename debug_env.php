<?php
// debug_env.php — TEMPORARY diagnostic tool. Shows only whether each expected
// environment variable is visible to PHP (true/false), never the actual value.
// DELETE THIS FILE after you're done debugging — don't leave it live.

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

function is_set(string $key): bool {
    $val = getenv($key);
    if ($val !== false && $val !== '') return true;
    if (($_SERVER[$key] ?? '') !== '') return true;
    if (($_ENV[$key] ?? '') !== '') return true;
    return false;
}

$keys = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'ALLOWED_ORIGINS', 'APP_SECRET', 'ADMIN_SETUP_KEY'];
$result = [];
foreach ($keys as $k) {
    $result[$k] = is_set($k);
}

echo json_encode(['visible_vars' => $result, 'php_version' => PHP_VERSION], JSON_PRETTY_PRINT);
