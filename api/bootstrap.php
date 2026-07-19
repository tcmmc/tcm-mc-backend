<?php
// bootstrap.php — included first by every endpoint. Wires up config, CORS, and helpers.

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../functions.php';

apply_cors($ALLOWED_ORIGINS);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed.', 405);
}
