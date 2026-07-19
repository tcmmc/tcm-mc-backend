<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

$user = require_user($pdo);

json_response(['user' => [
    'username' => $user['username'],
    'email' => $user['email'],
]]);
