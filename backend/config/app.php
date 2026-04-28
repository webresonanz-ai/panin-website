<?php

return [
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
    'frontend_url' => $_ENV['APP_FRONTEND_URL'] ?? 'http://localhost:5173',
    'auth_token_ttl_hours' => (int) ($_ENV['AUTH_TOKEN_TTL_HOURS'] ?? 12),
];
