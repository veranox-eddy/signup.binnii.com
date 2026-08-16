<?php

/*
|--------------------------------------------------------------------------
| Two connections, on purpose (staged-registration spec §3.1)
|--------------------------------------------------------------------------
|
| `signup`   — the local SQLite staging store (the DEFAULT). All writes of
|              this application land here and only here.
| `mysql_ro` — a read-only peek at daycare.users, whose DB account is
|              GRANTed SELECT on exactly two columns (email, deleted_at).
|
| This repo has NO writable MySQL connection and must never gain one:
| tenant creation goes through api.binnii.com's internal endpoint. The
| ConnectionGuardServiceProvider enforces read-only at the code layer even
| if the DB GRANT is ever misconfigured.
|
*/

return [

    'default' => env('DB_CONNECTION', 'signup'),

    'connections' => [

        'signup' => [
            'driver' => 'sqlite',
            'database' => env('SIGNUP_DB_PATH', '/var/lib/binnii-signup/signup.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => true,
            'journal_mode' => 'WAL',
            'busy_timeout' => 5000,
        ],

        'mysql_ro' => [
            // Tests swap the driver to sqlite (phpunit env) and create a
            // stand-in users table; production is always mysql.
            'driver' => env('MYSQL_RO_DRIVER', 'mysql'),
            'host' => env('MYSQL_RO_HOST', '127.0.0.1'),
            'port' => env('MYSQL_RO_PORT', '3306'),
            'database' => env('MYSQL_RO_DATABASE', 'daycare'),
            'username' => env('MYSQL_RO_USERNAME'),
            'password' => env('MYSQL_RO_PASSWORD'),
            'unix_socket' => env('MYSQL_RO_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ],

    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', 'binnii_signup_'),
        ],
        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
    ],

];
