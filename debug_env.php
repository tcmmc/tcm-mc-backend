<?php
// debug_env2.php — TEMPORARY. Lists every environment variable NAME (never a
// value) that PHP can see, from getenv(), $_SERVER, and $_ENV combined.
// DELETE after debugging.

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

$fromGetenv = array_keys(getenv() ?: []);
$fromServer = array_keys($_SERVER ?: []);
$fromEnvSuperglobal = array_keys($_ENV ?: []);

echo json_encode([
    'php_version' => PHP_VERSION,
    'sapi' => php_sapi_name(),
    'getenv_count' => count($fromGetenv),
    'getenv_keys' => $fromGetenv,
    'server_count' => count($fromServer),
    'server_keys' => $fromServer,
    'env_superglobal_count' => count($fromEnvSuperglobal),
    'env_superglobal_keys' => $fromEnvSuperglobal,
], JSON_PRETTY_PRINT);
