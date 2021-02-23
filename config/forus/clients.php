<?php

return [
    'mobile' => array_merge([
        'me_app-android', 'me_app-ios',
    ], env('DISABLE_FALLBACK_TRANSACTIONS', false) ? [] : [
        'app-me_app'
    ]),
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
    'default' => null,
];