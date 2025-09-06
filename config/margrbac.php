<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;

return [
    'should_cache' => true,
    'teams' => true,

    'models' => [
        'user' => User::class,
        'tenant' => Tenant::class,
    ],

    'database' => [
        'connection' => env('RBAC_DB_CONNECTION', 'rbac'),
        'driver' => env('RBAC_DB_DRIVER', 'mysql'),
        'host' => env('RBAC_DB_HOST', '127.0.0.1'),
        'port' => env('RBAC_DB_PORT', '3306'),
        'database' => env('RBAC_DB_DATABASE', 'marg-rbac'),
        'username' => env('RBAC_DB_USERNAME', 'root'),
        'password' => env('RBAC_DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],

    'session' => [
        'driver' => env('RBAC_SESSION_DRIVER', 'redis'),
        'connection' => env('RBAC_SESSION_CONNECTION', 'default'),
    ],

    'cache' => [
        'enabled' => env('RBAC_CACHE_ENABLED', true),
        'ttl' => env('RBAC_CACHE_TTL', 3600),
        'connection' => env('RBAC_CACHE_CONNECTION', 'redis'),
        'expiration_time' => \DateInterval::createFromDateString('24 hours'),
        'key' => 'spatie.permission.cache',
        'store' => 'default',
    ],

    'username' => [
        'enabled' => true,
        'min_length' => 3,
        'max_length' => 15,
        'allow_numbers' => true,
        'allow_underscores' => true,
        'force_lowercase' => true,
        'uniqueness_scope' => 'global', // 'global' or 'tenant'
        'max_attempts' => 10,
        'reserved_names' => [
            'admin', 'administrator', 'root', 'api', 'www', 'mail', 'ftp',
            'support', 'help', 'info', 'contact', 'about', 'terms', 'privacy',
            'blog', 'news', 'app', 'mobile', 'web', 'system', 'null', 'undefined',
        ],
    ],

    'filament' => [
        'tenant_switching' => env('AUTH_RBAC_FILAMENT_TENANT_SWITCHING', true),
    ],

];
