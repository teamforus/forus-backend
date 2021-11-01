<?php

return [
    'reserved' => [
        'title' => 'Aanbod :product_name is gereserveerd.',
        'description' => 'Aanbod :product_name is gereserveerd.',
    ],
    'approved' => [
        'title' => 'Aanmelding met :product_name is goedgekeurd voor :fund_name.',
        'description' => implode(' ', [
            'Aanmelding voor :fund_name is goedgekeurd.',
            'Uw aanbod :product_name staat nu op de webshop van :sponsor_name.'
        ]),
    ],
    // todo: translate
    'revoked' => [
        'title' => ':product_name is verwijderd :fund_name.',
        'description' => ':product_name is verwijderd uit :fund_name door :sponsor_name',
    ],
    'expired' => [
        'title' => 'Aanbod :product_name is verlopen.',
        'description' => 'Aanbod :product_name is verlopen.',
    ],
    'sold_out' => [
        'title' => 'Aanbod :product_name is uitverkocht.',
        'description' => 'Aanbod :product_name is uitverkocht.',
    ]
];
