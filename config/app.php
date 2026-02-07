<?php

return [
    'name' => env('APP_NAME', 'eggcavity'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),

    // Canonical base URL for links/redirects (e.g. https://eggcavity.com). When set, nav and
    // redirects use this instead of the request host so the IP is never shown.
    'canonical_url' => env('APP_CANONICAL_URL', env('APP_URL')),
    'timezone' => env('APP_TIMEZONE', 'UTC'),

    // Timezone used for ECT display (game runs on Eastern)
    'eggcave_timezone' => env('APP_EGGCAVE_TIMEZONE', 'America/New_York'),
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => array_filter(explode(',', env('APP_PREVIOUS_KEYS', ''))),
    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    // Optional: used by UserSeeder to create initial admin (only when no users exist)
    'admin_email' => env('ADMIN_EMAIL', 'admin@eggcavity.local'),
    'admin_password' => env('ADMIN_PASSWORD', 'password'),
];
