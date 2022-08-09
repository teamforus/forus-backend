<?php

return [
    'add_money' => false,
    'validationRequests' => true,
    'event_permissions' => array_combine(
        array_keys(config('forus.event_permissions')),
        array_pluck(config('forus.event_permissions'), 'permissions'),
    ),
    'organizations' => [
        'list' => true,
        'show' => true,
        'funds' => [
            'list' => true,
            'vouchers' => [
                'regular' => true,
                'products' => false,
            ],
            'mustAcceptProducts' => false,
            'allowPrevalidations' => true,
            'allowValidationRequests' => false,

            /**
             * Sponsor may set/edit criteria from dashboard
             */
            'criteria' => env('FUND_CRITERIA_EDITABLE_BY_USER', false),

            /**
             * Enable fund requests in dashboard
             * Hides elements from validator panel and disable responsible
             * api endpoints when disabled
             */
            'fund_requests' => env('ENABLE_FUND_REQUESTS_PANEL', true),
            
            /**
             * Sponsor may set/edit fund formula products from dashboard
             */
            'formula_products' => env('FUND_FORMULA_PRODUCTS_EDITABLE_BY_USER', false),
        ],
        "products" => [
            // list all funds
            "list" => true,
            "hard_limit" => env('PRODUCT_MAX_COUNT', 25),
            "soft_limit" => env('PRODUCT_MAX_COUNT_SOFT_LIMIT', 15)
        ],
    ],
];
