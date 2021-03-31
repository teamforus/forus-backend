<?php

return [
    'created_budget' => [
        'title' => 'Er is een :fund_name tegoed aan u toegekend.',
        'title_informal' => 'Er is een :fund_name aan je toegekend.',
        'description' => implode([
            'Hierbij ontvangt u uw :fund_name-tegoed.',
            'Het tegoed heeft een waarde van € :voucher_amount_locale en is geldig tot en met :voucher_expire_date_locale.'
        ], ' '),
    ],
    'created_product' => [
        'title' => 'Aanbod :product_name bij :provider_name gereserveerd!',
        'title_informal' => 'Aanbod :product_name bij :provider_name gereserveerd!',
        'description' => implode([
            'Aanbod :product_name bij :provider_name gereserveerd!',
            'De reservering heeft een waarde van €:voucher_amount_locale en is geldig tot en met :voucher_expire_date_locale.'
        ], ' '),
    ],
    'assigned' => [
        'title' => ':fund_name-tegoed is aan u toegekend.',
        'title_informal' => 'Alsjeblieft je :fund_name QR-code.',
        'description' => implode([
            'Hierbij ontvangt u uw :fund_name.',
            'Het tegoed heeft een waarde van :voucher_amount_locale en is geldig tot en met :voucher_expire_date_locale.'
        ], ' '),
    ],
    'shared' => [
        'title' => 'Aanbod QR-code gedeeld met :provider_name.',
        'description' => implode([
            'U heeft de aanbod gedeeld met :provider_name met het volgende bericht: ":voucher_share_message"',
        ], ' '),
    ],
    'expire_soon' => [
        'title' => ':fund_name verloopt binnenkort',
        'description' => implode([
            'Uw :fund_name is geldig tot en met :fund_last_active_date.',
            'Vanaf :expire_at_minus_day is het budget niet meer geldig.'
        ], ' '),
        'description_informal' => implode([
            'Je :fund_name is geldig tot en met :fund_last_active_date.',
            'Vanaf :expire_at_minus_day is het tegoed niet meer geldig.'
        ], ' '),
    ],
    'expired' => [
        'title' => 'Uw :fund_name-tegoed is verlopen.',
        'title_informal'  => 'Je :fund_name-tegoed is verlopen',
        'description' => implode([
            'Vanaf vandaag is uw :fund_name-tegoed niet meer geldig. ',
            'Dit betekent dat er geen betalingen meer gedaan kunnen worden met QR-codes van :fund_name.',
        ], ' '),
        'description_informal' => implode([
            'Vanaf vandaag is je tegoed voor :fund_name niet meer geldig. ',
            'Dit betekent dat er geen betalingen meer gedaan kunnen worden met QR-codes van :fund_name.',
        ], ' '),
    ]
];
