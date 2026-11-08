<?php

return [
    'enabled' => env('DCP_ENABLED', false),

    'allowed_urls' => array_values(array_filter(array_map(
        fn (string $url) => rtrim(trim($url), '/'),
        explode(',', (string) env('DCP_ALLOWED_URLS', 'https://api-acc.dcp.nl')),
    ))),

    'orderable_types' => [
        'physical_card_request',
    ],

    'formats' => [
        'upper', 'lower', 'date:d-m-Y', 'date:Y-m-d', 'date:d/m/Y', 'date:dmY',
    ],

    'timeout' => env('DCP_TIMEOUT', 30),

    'attempts' => env('DCP_ATTEMPTS', 5),
    'attempts_interval' => env('DCP_ATTEMPTS_INTERVAL', 60),
];
