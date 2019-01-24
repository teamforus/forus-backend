<?php

return [
    "voucher" => [
        "activated" => [
            "title" => "",
            "body" => "U hebt een activatiecode gebruikt om ':fund_name' voucher te activeren.",
        ],
        "bought" => [
            "title" => "",
            "body" => "U hebt een aanbieding gekocht in ':implementation_name' webshop.",
        ],
    ],
    "transactions" => [
        "offline_regular_voucher" => [
            "title" => "",
            "body" => "Er is zojuist een betaling plaats gevonden van &euro;:amount van uw ':fund_name' voucher",
        ],
        "offline_product_voucher" => [
            "title" => "",
            "body" => "Een aanbieding voucher is zojuist gebruikt om :product_name af te nemen.",
        ]
    ],
    "bunq_transactions" => [
        "complete" => [
            "title" => "",
            "body" => "Uw transactie van &euro;:amount is uitbetaald.",
        ]
    ],
    "access_levels" => [
        "added" => [
            "title" => "",
            "body" => "U bent toegevoegd aan :org_name as :role_name_list.",
        ],
        "updated" => [
            "title" => "",
            "body" => "",
        ],
        "removed" => [
            "title" => "",
            "body" => "U bent verwijderd als een medewerker van :org_name.",
        ]
    ],
    "providers" => [
        "accepted" => [
            "title" => "",
            "body" => "U bent goedgekeurd voor :fund_name.",
        ]
    ]
];