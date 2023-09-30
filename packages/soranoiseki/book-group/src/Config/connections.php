<?php

return [
    'openbiblio' => [
        'driver' => 'mysql',
        'url' => env('DATABASE_URL'),
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('OPENBIBLIO_DATABASE', 'openbiblio'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'unix_socket' => env('DB_SOCKET', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => null,
        'options' => extension_loaded('pdo_mysql') ? array_filter([
            PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        ]) : [],
    ],
    // https://github.com/mongodb/laravel-mongodb#configuration
    // 'mongodb' => [
    //     'driver' => 'mongodb',
    //     'host' => env('MONGODB_HOST', '127.0.0.1'),
    //     'port' => env('MONGODB_PORT', 27017),
    //     'database' => env('MONGODB_DATABASE', 'homestead'),
    //     'username' => env('MONGODB_USERNAME', 'homestead'),
    //     'password' => env('MONGODB_PASSWORD', 'secret'),
    //     // 'options' => [
    //     //     'appname' => 'homestead',
    //     // ],
    // ],  
    'mongodb' => [
        'driver' => 'mongodb',
        'dsn' => env('MONGODB_DSN'),
        'database' => env('MONGODB_DATABASE', 'cluster0'),
        'username' => env('MONGODB_USERNAME', 'homestead'),
        'password' => env('MONGODB_PASSWORD', 'secret'),
    ],
];
