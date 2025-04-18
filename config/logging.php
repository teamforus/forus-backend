<?php

use Monolog\Handler\StreamHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
        ],

        'kvk' => [
            'driver' => 'single',
            'path' => storage_path('logs/kvk-service.log'),
            'level' => 'debug',
        ],

        'query-counter' => [
            'driver' => 'single',
            'path' => storage_path('logs/query-counter.log'),
            'level' => 'debug',
        ],

        'bunq' => [
            'driver' => 'single',
            'path' => storage_path('logs/bunq-service.log'),
            'level' => 'debug',
        ],

        'bng' => [
            'driver' => 'single',
            'path' => storage_path('logs/bng-service.log'),
            'level' => 'debug',
        ],

        'criteria' => [
            'driver' => 'single',
            'path' => storage_path('logs/criteria.log'),
            'level' => 'debug',
        ],

        'funds' => [
            'driver' => 'single',
            'path' => storage_path('logs/funds.log'),
            'level' => 'debug',
        ],

        'digid' => [
            'driver' => 'single',
            'path' => storage_path('logs/digid-service.log'),
            'level' => 'debug',
        ],

        'mollie' => [
            'driver' => 'single',
            'path' => storage_path('logs/mollie-service.log'),
            'level' => 'debug',
        ],

        'backoffice' => [
            'driver' => 'single',
            'path' => storage_path('logs/backoffice-service.log'),
            'level' => 'debug',
        ],

        'iconnect' => [
            'driver' => 'single',
            'path' => storage_path('logs/iconnect.log'),
            'level' => 'debug',
        ],

        'deepl' => [
            'driver' => 'single',
            'path' => storage_path('logs/deepl.log'),
            'level' => 'debug',
        ],

        'translate-service' => [
            'driver' => 'single',
            'path' => storage_path('logs/translate-service.log'),
            'level' => 'debug',
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
            'days' => 7,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => 'critical',
        ],

        'stderr' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => 'debug',
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => 'debug',
        ],
    ],
];
