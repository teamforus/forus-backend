<?php

return [
    'created_budget' => [
        'title' => 'Er is een :fund_name tegoed aan u toegekend.',
        'description' => implode([
            'Hierbij ontvangt u uw :fund_name-voucher.',
            'De voucher heeft een waarde van € :voucher_amount_locale en is geldig tot en met :voucher_expire_date_locale.'
        ], ' '),
    ],
    'created_product' => [
        'title' => 'Aanbieding :product_name bij :provider_name gereserveerd!',
        'description' => implode([
            'Aanbieding :product_name bij :provider_name gereserveerd!',
            'De reservering heeft een waarde van €:voucher_amount_locale en is geldig tot en met :voucher_expire_date_locale.'
        ], ' '),
    ],
    'assigned' => [
        'title' => ':fund_name-voucher is aan u toegekend.',
        'description' => implode([
            'Hierbij ontvangt u uw :fund_name.',
            'De voucher heeft een waarde van :voucher_amount_locale en is geldig tot en met :voucher_expire_date_locale.'
        ], ' '),
    ],
    'shared' => [
        'title' => 'Aanbieding QR-code gedeeld met :provider_name.',
        'description' => implode([
            'U heeft de aanbieding gedeeld met :provider_name met het volgende bericht: ":voucher_share_message"',
        ], ' '),
    ],
    'expire_soon' => [
        'title' => ':fund_name -voucher is aan u toegekend.',
        'description' => implode([
            'Hierbij ontvangt u uw :fund_name-voucher. ',
            'De voucher heeft een waarde van €:voucher_amount en is geldig tot en met :expire_at_minus_day.'
        ], ' '),
    ],
    'expired' => [
        'title' => 'Uw :fund_name-voucher is verlopen.',
        'description' => implode([
            'Vanaf vandaag is uw :fund_name-voucher niet meer geldig. ',
            'Dit betekent dat er geen betalingen meer gedaan kunnen worden met QR-codes van :fund_name.',
        ], ' '),
    ]
];
