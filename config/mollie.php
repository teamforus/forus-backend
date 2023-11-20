<?php

return [
    'test_mode' => env('MOLLIE_TEST_MODE', true),
    'client_id' => env('MOLLIE_CLIENT_ID', ''),

    'client_secret' => env('MOLLIE_CLIENT_SECRET', ''),
    'redirect_url' => env('MOLLIE_REDIRECT_URI', '/mollie/callback'),
    'webhook_url' => env('MOLLIE_WEBHOOK_URI', '/mollie/webhook'),
    'base_access_token' => env('MOLLIE_ACCESS_TOKEN', ''),
    'expire_decrease' => env('MOLLIE_EXPIRE_DECREASE', 60 * 5),
    'webhook_ip_whitelist' => [
        '35.233.7.254',
        '35.187.75.91',
        '34.76.59.175',
        '34.76.201.228',
        '34.76.188.130',
        '34.76.90.110',
        '34.77.104.206',
        '87.233.217.242',
        '87.233.217.243',
        '87.233.217.244',
        '87.233.217.245',
        '87.233.217.246',
        '87.233.217.247',
        '87.233.217.248',
        '87.233.217.249',
        '87.233.217.250',
        '87.233.217.251',
        '87.233.217.252',
    ]
];
