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

    // Log channel to be used (e.g., 'single', 'daily', 'stack', etc.)
    'log_channel' => env('QUERY_COUNTER_LOG_CHANNEL', 'daily'),

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
