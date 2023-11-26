<?php

return [
    'test_mode' => env('MOLLIE_TEST_MODE', true),
    'client_id' => env('MOLLIE_CLIENT_ID', ''),
    'client_secret' => env('MOLLIE_CLIENT_SECRET', ''),
    'redirect_url' => env('MOLLIE_REDIRECT_URI', '/mollie/callback'),
    'webhook_url' => env('MOLLIE_WEBHOOK_URI', '/mollie/webhook'),

    'base_access_token' => env('MOLLIE_ACCESS_TOKEN', ''),
    'token_expire_offset' => env('MOLLIE_TOKEN_EXPIRE_OFFSET', 60 * 5),
];
