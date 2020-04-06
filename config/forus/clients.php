<?php

return [
    'mobile' => env('DISABLE_DEPRECATED_API', false) ? [
        'me_app-android', 'me_app-ios',
    ] : [
        'app-me_app', 'me_app-android', 'me_app-ios',
    ],
    'dashboards' => [
        'sponsor', 'provider', 'validator',
    ],
    'webshop' => [
        'webshop',
    ],
    'websites' => [
        'website',
    ],
    'auth' => [
        'pin_code-auth',
    ],
    'default' => env('DISABLE_DEPRECATED_API', false) ? null : 'general',
];