<?php
// cors.php — locks cross-origin access down to the configured frontend origin(s)
// and handles the browser's CORS preflight (OPTIONS) request.
//
// IMPORTANT: this must be called BEFORE config.php (which can exit early on
// a missing env var or failed DB connection). Otherwise an early failure
// would send a response with no CORS headers, and the browser would report
// a confusing "Failed to fetch" instead of the real error message.

declare(strict_types=1);

function _env_lookup(string $key): string {
    $val = getenv($key);
    if ($val !== false && $val !== '') return $val;
    if (($_SERVER[$key] ?? '') !== '') return $_SERVER[$key];
    if (($_ENV[$key] ?? '') !== '') return $_ENV[$key];
    return '';
}

function apply_cors(): void {
    $allowedOrigins = array_filter(array_map('trim', explode(',', _env_lookup('ALLOWED_ORIGINS'))));
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    }

    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 600');

    // Security headers (defense in depth even though this is a pure JSON API).
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header("Content-Security-Policy: default-src 'none'");
    header('Referrer-Policy: no-referrer');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
