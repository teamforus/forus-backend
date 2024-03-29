<?php

return [
    'subject' => 'Overzicht van uw reserveringen',
    'title' => 'Overzicht van uw reserveringen',

    'reservations' => [
        'title' => implode('|', [
            "Beste :provider_name,\n\nU heeft in totaal :count_reservations reservering ontvangen (:count_pending_reservations in afwachting) voor het volgende aanbod.",
            "Beste :provider_name,\n\nU heeft in totaal :count_reservations reserveringen ontvangen (:count_pending_reservations in afwachting) voor het volgende aanbod.",
        ]),
        'product_item' => [
            'title' => ':product_name voor :product_price_locale',
            'description' => implode('|', [
                '- :count :state reservering',
                '- :count :state reserveringen',
            ]),
            'description_total' => implode('|', [
                '- :count_total reservering in totaal.',
                '- :count_total reserveringen in totaal.',
            ]),
        ],
    ],

    'description' => implode("\n", [
        "Heeft u ingesteld dat u de reserveringen handmatig wilt accepteren? Ga dan naar uw beheeromgeving om de reserveringen te bevestigen.",
        "De transactie wordt na 14 dagen verwerkt.",
    ]),

    'dashboard_button' => 'GA NAAR DE BEHEEROMGEVING',
];
