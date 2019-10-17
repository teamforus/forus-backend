<?php

return [
    // Web shops
    'webshop'               => env('WEB_SHOP_GENERAL_URL', false),

    // Panels
    'panel-sponsor'         => env('PANEL_SPONSOR_URL', false),
    'panel-provider'        => env('PANEL_PROVIDER_URL', false),
    'panel-validator'       => env('PANEL_VALIDATOR_URL', false),

    'landing-app'           => env('LANDING_APP_URL', false),

    'app-me_app'            => env('ME_APP_URL', 'meapp://'),

    "map" => [
        "lon"               => env('WEB_SHOP_GENERAL_MAP_LON'),
        "lat"               => env('WEB_SHOP_GENERAL_MAP_LAT'),
    ]
];