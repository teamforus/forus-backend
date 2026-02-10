<?php

return [
    // Exposed to the dashboard to hide event log filter options the backend blocks anyway.
    'event_permissions' => array_combine(
        array_keys(config('forus.event_permissions')),
        array_pluck(config('forus.event_permissions'), 'permissions'),
    ),
    'organizations' => [
        'funds' => [
            // Enables criteria validation and syncing plus criteria update endpoints.
            'criteria' => env('FUND_CRITERIA_EDITABLE_BY_USER', false),
            // Registers fund request routes and shows dashboard fund request sections.
            'fund_requests' => env('ENABLE_FUND_REQUESTS_PANEL', true),
            // Enables formula product validation and syncing plus the editor.
            'formula_products' => env('FUND_FORMULA_PRODUCTS_EDITABLE_BY_USER', false),
        ],
        'products' => [
            // Hard cap for org products and blocks creation at the limit.
            'hard_limit' => env('PRODUCT_MAX_COUNT', 25),
            // Soft cap for dashboard warnings as counts approach the hard limit.
            'soft_limit' => env('PRODUCT_MAX_COUNT_SOFT_LIMIT', 15),
        ],
    ],
];
