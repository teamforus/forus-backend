<?php

return [
    'Zuidhorn' => [
        'implementation' => [
            'digid_connection_type' => 'cgi',
            'digid_sign_up_allowed' => true,
            'informal_communication' => true,
        ],
    ],
    'Stadjerspas' => [
        'implementation' => [
            'key' => "groningen",
            'title' => "Doe jij al mee met de Stadjerspas?",
            'description' => "Welkom op de website van de Stadjerspas. U kan hier de regeling aanvragen en het aanbod bekijken.",
            'description_alignment' => "left",
            'overlay_enabled' => false,
            'overlay_type' => "color",
            'header_text_color' => "auto",
            'overlay_opacity' => 90,
            'lon' => "5.58338",
            'lat' => "52.137586",
            'informal_communication' => true,
            'email_from_name' => "Gemeente Groningen",
            'email_from_address' => null,
            'email_color' => "#F6F5F5",
            'email_signature' => null,
            'show_home_map' => true,
            'show_home_products' => true,
            'show_providers_map' => true,
            'show_provider_map' => true,
            'show_office_map' => true,
            'show_voucher_map' => true,
            'show_product_map' => true,
        ]
    ],
    'Doetegoed' => [
        'implementation' => [
            'currency_sign' => '⛁',
            'currency_round' => true,
        ]
    ]
];