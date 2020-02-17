<?php

return [
    'add_money' => false,
    'validationRequests' => true,
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
            'fund_requests' => env('ENABLE_FUND_REQUESTS_PANEL', false),
            
            /**
             * Sponsor may set/edit fund formula products from dashboard
             */
            'formula_products' => env('FUND_FORMULA_PRODUCTS_EDITABLE_BY_USER', false),
        ],
        "products" => [
            // list all funds
            "list" => true,
            "maxProductCount" => env('PRODUCT_MAX_COUNT', 25)
        ],
    ],
];
