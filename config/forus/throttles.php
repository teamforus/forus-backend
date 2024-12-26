<?php

return [
    'auth' => [
        'attempts' => env('AUTH_THROTTLE_ATTEMPTS', 10),
        'decay' => env('AUTH_THROTTLE_DECAY', 10),
    ],
    'update_profile' => [
        'attempts' => env('UPDATE_PROFILE_THROTTLE_ATTEMPTS', 5),
        'decay' => env('UPDATE_PROFILE_THROTTLE_DECAY', 10),
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

    'accept_reservation' => [
        'attempts' => env('ACCEPT_RESERVATION_THROTTLE_ATTEMPTS', 1),
        'decay' => env('ACCEPT_RESERVATION_THROTTLE_DECAY', 60),
    ],

    'mollie' => [
        'fetch_connections' => [
            'attempts' => env('MOLLIE_FETCH_CONNECTION_ATTEMPTS', 10),
            'decay' => env('MOLLIE_FETCH_CONNECTION_DECAY', 10),
        ],
        'connect' => [
            'attempts' => env('MOLLIE_CONNECT_ATTEMPTS', 10),
            'decay' => env('MOLLIE_CONNECT_DECAY', 10),
        ],
        'create' => [
            'attempts' => env('MOLLIE_CREATE_ATTEMPTS', 10),
            'decay' => env('MOLLIE_CREATE_DECAY', 10),
        ],
        'create_profile' => [
            'attempts' => env('MOLLIE_CREATE_PROFILE_ATTEMPTS', 20),
            'decay' => env('MOLLIE_CREATE_PROFILE_DECAY', 20),
        ],
        'fetch_payments' => [
            'attempts' => env('MOLLIE_FETCH_PAYMENT_ATTEMPTS', 50),
            'decay' => env('MOLLIE_FETCH_PAYMENT_DECAY', 15),
        ],
    ],
];