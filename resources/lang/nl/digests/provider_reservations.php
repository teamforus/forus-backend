<?php

// todo: set plural version and delete the comment
return [
    'subject' => 'Update: Uw aanbod is gereserveerd',
    'title' => 'Update: Uw aanbod is gereserveerd',

    'reservations' => [
        'title' => implode('|', [
            "Beste :provider_name,\n\nU heeft in totaal :count_reservations nieuwe reserveringen ontvangen voor het volgende aanbod.",
            "Beste :provider_name,\n\nU heeft in totaal :count_reservations nieuwe reserveringen ontvangen voor het volgende aanbod.",
        ]),
        'product_item' => [
            'title' => ':product_name voor :product_price_locale',
            'subtitle' => implode('|', [
                '- :count_reservations nieuwe reserveringen.',
                '- :count_reservations nieuwe reserveringen.',
            ]),
        ],
    ],

    'description' => implode("\n", [
        "Heeft u ingesteld dat u de reserveringen handmatig wilt accepteren? Ga dan naar uw beheeromgeving om de reserveringen te bevestigen.",
        "De transactie wordt na 14 dagen verwerkt.",
    ]),

    'dashboard_button' => 'GA NAAR DE BEHEEROMGEVING',
];
