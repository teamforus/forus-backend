<?php

return [
    'auth' => [
        'attempts' => env('AUTH_THROTTLE_ATTEMPTS', 10),
        'decay' => env('AUTH_THROTTLE_DECAY', 10),
    ],
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
    'feedback_form' => [
        'attempts' => env('FEEDBACK_FORM_API_ATTEMPTS', 15),
        'decay' => env('FEEDBACK_FORM_API_DECAY', 15),
    ],
];