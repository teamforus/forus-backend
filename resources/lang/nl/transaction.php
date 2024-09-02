<?php

return [
    'target' => [
        'provider' => 'Aanbieder',
        'top_up' => 'Opwaarderen',
        'iban' => 'IBAN',
    ],
    'type' => [
        'incoming' => 'inkomend',
        'outgoing' => 'uitgaand',
    ],
    'payment_type' => [
        'reimbursement' => [
            'title' => 'Declaratie',
            'subtitle' => '',
        ],
        'voucher_scan' => [
            'title' => 'Tegoed',
            'subtitle' => 'Gescande QR-code',
        ],
        'product_voucher' => [
            'title' => 'Productegoed',
            'subtitle' => ':product',
        ],
        'product_reservation' => [
            'title' => 'Tegoed',
            'subtitle' => 'Reservering',
        ],
        'direct_provider' => [
            'title' => 'Uitbetaling',
            'subtitle' => '',
        ],
        'direct_iban' => [
            'title' => 'Uitbetaling',
            'subtitle' => '',
        ],
        'direct_top_up' => [
            'title' => 'Uitbetaling',
            'subtitle' => '',
        ],
    ],
];
