<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => env('SES_REGION'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'precheck_micro' => [
        'base_url' => env('PRECHECK_MICRO_BASE_URL', 'http://localhost:8010'),
        'timeout' => env('PRECHECK_MICRO_TIMEOUT', 15),
        'retries' => env('PRECHECK_MICRO_RETRIES', 2),
        'token' => env('PRECHECK_MICRO_BEARER'),
        'stream_token_ttl' => env('PRECHECK_MICRO_STREAM_TOKEN_TTL', 900), // seconds
    ]

];
