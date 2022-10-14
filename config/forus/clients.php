<?php

use App\Models\Implementation;

return [
    'mobile' => array_merge([
        Implementation::ME_APP_ANDROID,
        Implementation::ME_APP_IOS,
    ], env('DISABLE_FALLBACK_TRANSACTIONS', false) ? [] : [
        Implementation::ME_APP_DEPRECATED,
    ]),
    'dashboards' => [
        Implementation::FRONTEND_SPONSOR_DASHBOARD,
        Implementation::FRONTEND_PROVIDER_DASHBOARD,
        Implementation::FRONTEND_VALIDATOR_DASHBOARD,
    ],
    'webshop' => [
        Implementation::FRONTEND_WEBSHOP,
    ],
    'websites' => [
        Implementation::FRONTEND_WEBSITE,
    ],
    'auth' => [
        Implementation::FRONTEND_PIN_CODE,
    ],
    'default' => null,
];