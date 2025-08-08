<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ClickHouse Default Connection
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the ClickHouse connections below you wish
    | to use as your default connection for all ClickHouse work.
    |
    */

    'default' => env('CLICKHOUSE_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | ClickHouse Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the ClickHouse connections setup for your application.
    | Examples of configuring each database platform that is supported by
    | ClickHouse is shown below to make development simple.
    |
    */

    'connections' => [
        'default' => [
            'host' => env('CLICKHOUSE_HOST', 'localhost'),
            'port' => env('CLICKHOUSE_PORT', '8123'),
            'username' => env('CLICKHOUSE_USERNAME', 'default'),
            'password' => env('CLICKHOUSE_PASSWORD', 'clickhouse'),
            'database' => env('CLICKHOUSE_DATABASE', 'default'),
            'options' => [
                'timeout' => env('CLICKHOUSE_TIMEOUT', 30),
                'ssl' => env('CLICKHOUSE_SSL', false),
                'readonly' => env('CLICKHOUSE_READONLY', true),
            ],
        ],

        'cluster' => [
            /*
             |--------------------------------------------------------------------------
             | ClickHouse Cluster Configuration
             |--------------------------------------------------------------------------
             |
             | Here you may configure the cluster nodes for ClickHouse.
             |
             | available mode:
             | - round_robin: round robin mode
             | - random: random mode
             | - failover: failover mode
             |
             */
            'mode' => env('CLICKHOUSE_CLUSTER_MODE', 'round_robin'),
            'nodes' => [
                'host' => explode(',', env('CLICKHOUSE_CLUSTER_NODES', 'node1,node2')),
                'port' => explode(',', env('CLICKHOUSE_CLUSTER_PORTS', '8123,8123')),
                'weight' => explode(',', env('CLICKHOUSE_CLUSTER_WEIGHTS', '1,1')),
                'username' => env('CLICKHOUSE_USERNAME', 'default'),    
                'password' => env('CLICKHOUSE_PASSWORD', 'clickhouse'),
                'database' => env('CLICKHOUSE_DATABASE', 'default'),
                'options' => [
                    'timeout' => env('CLICKHOUSE_TIMEOUT', 30),
                    'ssl' => env('CLICKHOUSE_SSL', false),
                    'readonly' => env('CLICKHOUSE_READONLY', true),
                ],
            ],
            'options' => [
                'retry_attempts' => env('CLICKHOUSE_CLUSTER_RETRY_ATTEMPTS', 3),
                'retry_delay' => env('CLICKHOUSE_CLUSTER_RETRY_DELAY', 1000), // milliseconds
                'health_check_interval' => env('CLICKHOUSE_CLUSTER_HEALTH_CHECK_INTERVAL', 30), // seconds
                'failover_timeout' => env('CLICKHOUSE_CLUSTER_FAILOVER_TIMEOUT', 5000), // milliseconds
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | ClickHouse Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the logging options for ClickHouse queries.
    |
    */

    'logging' => [
        'enabled' => env('CLICKHOUSE_LOGGING_ENABLED', true),
        'channel' => env('CLICKHOUSE_LOGGING_CHANNEL', 'clickhouse'),
    ],

    /*
    |--------------------------------------------------------------------------
    | ClickHouse Migration Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the migration options for ClickHouse.
    | Note: ClickHouse migrations use Laravel's default migrations table.
    |
    */

    'migrations' => [
        'path' => env('CLICKHOUSE_MIGRATIONS_PATH', database_path('migrations/clickhouse')),
    ],
];
