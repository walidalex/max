<?php

declare(strict_types=1);

return [
    'session_name' => env('SESSION_NAME', 'contracting_erp_session'),
    'secure' => env_bool('SESSION_SECURE', false),
    'same_site' => env('SESSION_SAME_SITE', 'Lax'),
    'idle_timeout' => 7200,
];
