<?php

return [
    // Enable logging
    'enabled' => env('QUERY_COUNTER_ENABLED', false),

    // Default minimum number of queries to trigger logging
    'min_queries' => env('QUERY_COUNTER_MIN_QUERIES', 200),

    // Default minimum number of queries to trigger logging
    'min_queries_time' => env('QUERY_COUNTER_MIN_QUERIES_TIME', 1000),

    // Log channel to be used
    'log_channel' => env('QUERY_COUNTER_LOG_CHANNEL', 'daily'), // e.g., 'single', 'daily', 'stack', etc.

    // Routes to exclude from logging
    'excluded_routes' => [
        // 'excluded-endpoint-name',
    ],

    // Route-specific overrides for minimum queries
    'min_queries_overwrite' => [
        // 'override-endpoint-name' => 200,
    ],

    // Route-specific overrides for minimum queries
    'min_queries_time_overwrite' => [
        // 'override-endpoint-name' => 1000,
    ],
];
