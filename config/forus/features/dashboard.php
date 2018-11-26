<?php

return [
    'add_money' => false,
    'validationRequests' => false,
    'organizations' => [
        'list' => true,
        'show' => true,
        'funds' =>
            [
                'list' => true,
                'vouchers' =>
                    [
                        'regular' => true,
                        'products' => false,
                    ],
                'mustAcceptProducts' => false,
                'allowPrevalidations' => true,
                'allowValidationRequests' => false,
            ],
        "products" => [
            // list all funds
            "list"      => true
        ],
    ],
];
