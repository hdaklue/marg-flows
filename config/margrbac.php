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
        'driver' => env('RBAC_DB_DRIVER', 'pgsql'),
        'host' => env('RBAC_DB_HOST', '127.0.0.1'),
        'port' => env('RBAC_DB_PORT', '5432'),
        'database' => env('RBAC_DB_DATABASE', 'marg-rbac'),
        'username' => env('RBAC_DB_USERNAME', 'home'),
        'password' => env('RBAC_DB_PASSWORD', ''),
        'charset' => 'utf8',
        'prefix' => '',
        'schema' => 'public',
        'sslmode' => 'prefer',
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
            // System & Security
            'admin', 'administrator', 'root', 'superuser', 'superadmin', 'owner', 'moderator', 'mod',
            'manager', 'guest', 'public', 'private', 'test', 'demo', 'example', 'sample',

            // Authentication & Routes
            'login', 'logout', 'signin', 'signout', 'signup', 'register', 'auth', 'password',
            'dashboard', 'settings', 'profile', 'account', 'user', 'users',

            // Services & Protocols
            'api', 'www', 'mail', 'ftp', 'smtp', 'pop3', 'imap', 'ssh', 'sftp', 'cdn',
            'dns', 'ntp', 'http', 'https', 'graphql', 'rest', 'webhook',

            // Common Pages & Endpoints
            'support', 'help', 'info', 'contact', 'about', 'terms', 'privacy', 'legal',
            'blog', 'news', 'home', 'index', 'search', 'download', 'upload',

            // Resources & Assets
            'assets', 'static', 'media', 'images', 'files', 'css', 'js', 'fonts',

            // Technical & Database
            'app', 'mobile', 'web', 'system', 'database', 'db', 'cache', 'redis',
            'mysql', 'postgres', 'sql', 'backup', 'queue', 'jobs',

            // Reserved Words
            'null', 'undefined', 'true', 'false', 'none', 'nil', 'void',
            'delete', 'update', 'create', 'select', 'insert',

            // Package Specific
            'margrbac', 'rbac', 'rbac-admin', 'rbac-user', 'rbac-team', 'rbac-tenant',
            'rbac-member', 'rbac-invitation', 'rbac-role', 'rbac-permission', 'rbac-team-role',
            'margineer', 'porter',

            // Arabic Equivalents
            'مارجينير', 'ادمن', 'مدير', 'المدير', 'مشرف', 'المشرف', 'مستخدم', 'مستخدمين',
            'الدعم', 'المساعدة', 'معلومات', 'اتصل', 'الاتصال', 'النظام', 'الجذر',
            'الرئيسية', 'حساب', 'الحساب', 'اعدادات', 'الاعدادات', 'دخول', 'خروج',
            'تسجيل', 'بحث', 'صفحة', 'رئيسي',
        ],
    ],

    'filament' => [
        'tenant_switching' => env('AUTH_RBAC_FILAMENT_TENANT_SWITCHING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Porter 2.0.0 Multitenancy Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for Porter 2.0.0 multitenancy features.
    | These options enhance tenant isolation and scoping capabilities.
    |
    */
    'tenant_isolation' => env('RBAC_TENANT_ISOLATION', true),

    'multitenancy' => [
        'enabled' => env('RBAC_MULTITENANCY_ENABLED', true),
        'tenant_aware_caching' => env('RBAC_TENANT_AWARE_CACHING', true),
        'default_tenant_resolver' => null,
        'strict_tenant_scoping' => env('RBAC_STRICT_TENANT_SCOPING', false),
    ],

];
