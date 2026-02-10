<?php

return [
    'funds' => [
        // When false, the funds list page redirects to the home page in the webshop UI.
        'list' => env('FEATURES_WEBSHOP_FUNDS_LIST', false),

        // Registers or removes fund request API routes and hides the fund request CTA when disabled.
        'fund_requests' => env('ENABLE_FUND_REQUESTS_WEBSHOP', true),
    ],
];
