<?php

use Illuminate\Support\Str;

return [
    'driver' => env('SESSION_DRIVER', env('APP_ENV') === 'production' ? 'file' : 'redis'),
    'lifetime' => env('SESSION_LIFETIME', 120),
    'expire_on_close' => filter_var(env('SESSION_EXPIRE_ON_CLOSE', false), FILTER_VALIDATE_BOOLEAN),
    'encrypt' => filter_var(env('SESSION_ENCRYPT', false), FILTER_VALIDATE_BOOLEAN),
    'files' => storage_path('framework/sessions'),
    'connection' => env('SESSION_CONNECTION'),
    'table' => env('SESSION_TABLE', 'sessions'),
    'store' => env('SESSION_STORE'),
    'lottery' => [2, 100],
    'cookie' => env('SESSION_COOKIE', Str::slug(env('APP_NAME', 'laravel'), '_').'_session'),
    'path' => env('SESSION_PATH', '/'),
    'domain' => env('SESSION_DOMAIN'),
    'secure' => filter_var(env('SESSION_SECURE_COOKIE', env('APP_ENV') === 'production'), FILTER_VALIDATE_BOOLEAN),
    'http_only' => filter_var(env('SESSION_HTTP_ONLY', true), FILTER_VALIDATE_BOOLEAN),
    'same_site' => env('SESSION_SAME_SITE', 'lax'),
    'partitioned' => filter_var(env('SESSION_PARTITIONED_COOKIE', false), FILTER_VALIDATE_BOOLEAN),
];
