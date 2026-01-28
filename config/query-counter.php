<?php

return [
    // Enable logging
    'enabled' => env('QUERY_COUNTER_ENABLED', false),

    // Log queries only for the specified locale (e.g., 'nl'). Set to null to log queries for all locales.
    'locale' => env('QUERY_COUNTER_LOCALE', 'nl'),

    // Default minimum number of queries to trigger logging
    'min_queries' => env('QUERY_COUNTER_MIN_QUERIES', 200),

    // Default minimum total query time (in ms) to trigger logging
    'min_queries_time' => env('QUERY_COUNTER_MIN_QUERIES_TIME', 1000),

    // Log providers (configure at least one provider to log)
    'providers' => [
        [
            'driver' => App\Services\QueryCounterService\Providers\LogQueryCounterProvider::class,
            'channel' => env('QUERY_COUNTER_LOG_CHANNEL', 'daily'),
            'enabled' => env('QUERY_COUNTER_LOG_ENABLED', true),
        ],
        [
            'driver' => App\Services\QueryCounterService\Providers\DatabaseQueryCounterProvider::class,
            'connection' => env('QUERY_COUNTER_DB_CONNECTION', 'mysql'),
            'enabled' => env('QUERY_COUNTER_DB_ENABLED', false),
            'table' => 'query_counter_logs',
            'group' => env('QUERY_COUNTER_DB_GROUP', 'default'),
        ],
    ],

    // Routes to exclude from logging
    'excluded_routes' => [
        // 'excluded-endpoint-name',
    ],

    // Route-specific overrides for minimum queries
    'min_queries_overwrite' => [
        // 'override-endpoint-name' => 200,
    ],

    // Route-specific overrides for minimum total query time (in ms)
    'min_queries_time_overwrite' => [
        // 'override-endpoint-name' => 1000,
    ],
];
