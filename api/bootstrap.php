<?php
// bootstrap.php — included first by every endpoint. Wires up CORS, config, and helpers.

declare(strict_types=1);

// Never let a stray PHP warning/notice/deprecation get echoed into the
// response body — that would corrupt the JSON and make the browser's
// fetch() throw a generic "failed to parse" error instead of showing the
// real message. Log issues instead, keep responses clean.
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../cors.php';
apply_cors();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json; charset=utf-8');

// Last-resort safety net: if anything blows up in a way our own try/catch
// blocks didn't anticipate, still return clean JSON instead of a raw PHP
// error page (which would break the frontend's res.json() parsing).
set_exception_handler(function (\Throwable $e) {
    error_log('[uncaught] ' . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['message' => 'یه خطای پیش‌بینی‌نشده رخ داد.']);
    exit;
});
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('[fatal] ' . $err['message']);
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['message' => 'یه خطای پیش‌بینی‌نشده رخ داد.']);
        }
    }
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed.', 405);
}
