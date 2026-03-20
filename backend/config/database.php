<?php

return [
    'default' => env('DB_CONNECTION', 'pgsql'),

    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('POSTGRES_HOST'),
            'port' => env('POSTGRES_PORT'),
            'database' => env('POSTGRES_DB'),
            'username' => env('POSTGRES_USER'),
            'password' => env('POSTGRES_PASSWORD'),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ]
    ],
    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ]
];
