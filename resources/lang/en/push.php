<?php

return [
    "voucher" => [
        "activated" => [
            "title" => "",
            "body" => "You used an activation code to receive a :fund_name voucher.",
        ],
        "bought" => [
            "title" => "",
            "body" => "You bought a product in ':implementation_name' webshop.",
        ],
    ],
    "transactions" => [
        "offline_regular_voucher" => [
            "title" => "",
            "body" => "There has just been payment of &euro;:amount from your ':fund_name' voucher.",
        ],
        "offline_product_voucher" => [
            "title" => "",
            "body" => "A product voucher is used :product_name.",
        ]
    ],
    "bunq_transactions" => [
        "complete" => [
            "title" => "",
            "body" => "Your transaction of &euro;:amount has been payed out.",
        ]
    ],
    "access_levels" => [
        "added" => [
            "title" => "",
            "body" => "You have been added tot :org_name as :role_name_list.",
        ],
        "updated" => [
            "title" => "",
            "body" => "",
        ],
        "removed" => [
            "title" => "",
            "body" => "You have been removed as an employee from :org_name.",
        ]
    ],
    "providers" => [
        "accepted" => [
            "title" => "",
            "body" => "You have been accepted for :fund_name.",
        ]
    ]
];