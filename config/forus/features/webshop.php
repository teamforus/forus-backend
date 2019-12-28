<?php

return [
    "records" => [
        // view records list
        "list"      => false,

        // create new record
        "create"    => false,

        // validate record
        "validate"  => false
    ],
    "funds" => [
        // list all funds
        "list"      => env('FEATURES_WEBSHOP_FUNDS_LIST', false),

        // view fund details
        "show"      => false,

        /**
         * Allow users to submit make fund requests
         * Hides elements from webshop and disable responsible api endpoints
         * when disabled
         */
        'fund_requests' => env('ENABLE_FUND_REQUESTS_WEBSHOP', false),
    ],
    "products" => [
        // list all funds
        "list"      => true,

        // view fund details
        "show"      => true
    ],
];
