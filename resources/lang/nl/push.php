<?php

return [
    "voucher" => [
        "activated" => [
            "title" => ":fund_name",
            "body" => "U hebt een activatiecode gebruikt om ':fund_name' voucher te activeren.",
        ],
        "bought" => [
            "title" => ":implementation_name",
            "body" => "U hebt een aanbieding gekocht in ':implementation_name' webshop.",
        ],
    ],
    "transactions" => [
        "offline_regular_voucher" => [
            "title" => ":fund_name",
            "body" => "U heeft betaald! €:amount is afgeschreven van uw tegoed.",
        ],
        "offline_product_voucher" => [
            "title" => ":product_name",
            "body" => "Een aanbieding voucher is zojuist gebruikt om :product_name af te nemen.",
        ]
    ],
    "bunq_transactions" => [
        "complete" => [
            "title" => "Uitbetaling",
            "body" => "Uw transactie van €:amount is uitbetaald.",
        ]
    ],
    "access_levels" => [
        "added" => [
            "title" => ":org_name",
            "body" => "U bent toegevoegd aan :org_name as :role_name_list.",
        ],
        "updated" => [
            "title" => ":org_name",
            "body" => "Uw rechten zijn aangepast. Vraag uw beheerder.",
        ],
        "removed" => [
            "title" => ":org_name",
            "body" => "U bent verwijderd als een medewerker van :org_name.",
        ]
    ],
    "providers" => [
        "accepted" => [
            "title" => ":fund_name",
            "body" => "U bent goedgekeurd voor :fund_name.",
        ]
    ]
];
