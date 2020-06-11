<?php

return [
    'reserved' => [
        'title' => 'Aanbieding :product_name is gereserveerd.',
        'description' => 'Aanbieding :product_name is gereserveerd.',
    ],
    'approved' => [
        'title' => 'Aanmelding met :product_name is goedgekeurd voor :fund_name.',
        'description' => implode([
            'Aanmelding voor :fund_name is goedgekeurd.',
            'Uw specifieke aanbieding :product_name staat nu op de webshop van :sponsor_name.'
        ], ' '),
    ],
    // todo: translate
    'revoked' => [
        'title' => ':product_name has ben removed :fund_name.',
        'description' => ':product_name has ben removed from :fund_name by :sponsor_name',
    ],
    'expired' => [
        'title' => 'Aanbieding :product_name is verlopen.',
        'description' => 'Aanbieding :product_name is verlopen.',
    ],
    'sold_out' => [
        'title' => 'Aanbieding :product_name is uitverkocht.',
        'description' => 'Aanbieding :product_name is uitverkocht.',
    ]
];
