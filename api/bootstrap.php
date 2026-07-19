<?php
// bootstrap.php — included first by every endpoint. Wires up CORS, config, and helpers.

declare(strict_types=1);

require_once __DIR__ . '/../cors.php';
apply_cors();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed.', 405);
}
