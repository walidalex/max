<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Contracting & Interior Design ERP'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env_bool('APP_DEBUG', false),
    'url' => rtrim((string) env('APP_URL', 'http://localhost:8000'), '/'),
    'timezone' => env('APP_TIMEZONE', 'Africa/Cairo'),
    'key' => env('APP_KEY', ''),
    'storage_path' => dirname(__DIR__) . '/storage',
];
