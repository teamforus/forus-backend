<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. You may set this to
    | any of the connections defined in the "connections" array below.
    |
    | Supported: "pusher", "redis", "log", "null"
    |
    */

    'default' => env('BROADCAST_DRIVER', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast events to other systems or over websockets. Samples of
    | each available type of connection are provided inside this array.
    |
    */

    'connections' => [

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'encrypted' => true,
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

        'apn' => env('APN_ENABLED', false) ? [
            'environment' => env('APN_SANDBOX', true) ?
                \NotificationChannels\Apn\ApnChannel::SANDBOX :
                \NotificationChannels\Apn\ApnChannel::PRODUCTION,
            'certificate' => env('APN_CERTIFICATE_PATH_RAW', false) ?: storage_path(
                env('APN_CERTIFICATE_PATH', false) ?: "app/apn-cert.pem"
            ),
            // optional pass phrase
            'pass_phrase' => env('APN_PASS_PHRASE', null),
        ] : null,

        'fcm' => env('FCM_ENABLED', false) ? [
            'key' => env('FCM_KEY'),
        ] : null,
    ],

];
