<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter([
        env('FRONTEND_URL'),
        env('APP_URL'),
        'http://localhost:5173',
    ])),

    'allowed_origins_patterns' => array_values(array_filter([
        env('CORS_ALLOWED_ORIGINS_PATTERN'),
    ])),

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
