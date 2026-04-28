<?php

return [
    'driver' => 'mysql',
    'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
    'database' => $_ENV['DB_NAME'] ?? 'luxury_guest_dashboard',
    'username' => $_ENV['DB_USER'] ?? 'root',
    'password' => $_ENV['DB_PASS'] ?? '',
    'charset' => 'utf8mb4',
];
