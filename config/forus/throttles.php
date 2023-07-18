<?php

return [
    'fund_check' => [
        'attempts' => env('FUND_CHECK_ATTEMPTS', 20),
        'decay' => env('FUND_CHECK_DECAY', 60),
    ],
    'contact_form' => [
        'attempts' => env('CONTACT_FORM_THROTTLE_ATTEMPTS', 10),
        'decay' => env('CONTACT_FORM_THROTTLE_DECAY', 10),
    ],
    'activation_code' => [
        'attempts' => env('ACTIVATION_CODE_ATTEMPTS', 3),
        'decay' => env('ACTIVATION_CODE_DECAY', 180),
    ],
    'identity_destroy' => [
        'attempts' => env('DELETE_IDENTITY_THROTTLE_ATTEMPTS', 10),
        'decay' => env('DELETE_IDENTITY_THROTTLE_DECAY', 10),
    ],
    'product_reservation_store' => [
        'attempts' => env('STORE_RESERVATION_THROTTLE_ATTEMPTS', 30),
        'decay' => env('STORE_RESERVATION_THROTTLE_DECAY', 10),
    ],
];