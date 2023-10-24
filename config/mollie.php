<?php

return [
    'test_mode' => env('MOLLIE_TEST_MODE', false),
    'client_id' => env('MOLLIE_CLIENT_ID', ''),
    'client_secret' => env('MOLLIE_CLIENT_SECRET', ''),
    'redirect_url' => env('MOLLIE_REDIRECT_URI', ''),
    'base_access_token' => env('MOLLIE_ACCESS_TOKEN', ''),
    'expire_decrease' => env('MOLLIE_EXPIRE_DECREASE', 60 * 5),
];
