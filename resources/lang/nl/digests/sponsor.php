<?php

return [
    'subject' => 'Update: Nieuwe aanmeldingen voor uw fondsen',
    'title' => 'Nieuwe aanmeldingen voor uw fondsen',

    'greetings' => 'Beste :organization_name,',
    'dashboard_button' => 'GA NAAR DE BEHEEROMGEVING',

    // new provider pending applications
    "providers_pending" => [
        'title' => 'Pending provider applications',
        'header' => 'Nieuwe aanmeldingen voor :fund_name',
        'details' => implode('|', [
            ":providers_count aanbieder heeft zich aangemeld en wacht op goedkeuring.\n- :providers_list",
            ":providers_count aanbieders hebben zich aangemeld en wachten op goedkeuring.\n- :providers_list"
        ]),
    ],

    // new provider approved applications
    "providers_approved" => [
        'title' => 'Approved provider applications',
        'header' => 'Nieuwe aanmeldingen voor :fund_name',
        'details' => implode('|', [
            ":providers_count aanbieder heeft zich aangemeld en wacht op goedkeuring.\n- :providers_list",
            ":providers_count aanbieders hebben zich aangemeld en wachten op goedkeuring.\n- :providers_list"
        ]),
    ],

    // new provider unsubscription requests
    "providers_unsubscriptions" => [
        'title' => 'Provider unsubscription requests',
        'header' => 'New provider unsubscription request for :fund_name',
        'details' => implode('|', [
            ":providers_count provider requested to be removed from the fund. \n- :providers_list",
            ":providers_count providers requested to be removed from the fund.\n- :providers_list"
        ]),
    ],

    // product approved by allow_products from fund_providers
    'products_auto' => [
        'title' => "Products approved automatically",
        'header' => 'Nieuwe aanbiedingen voor :fund_name webshop.',
        'details' => implode('|', [
            ":products_count aanbieding is toegevoegd aan :fund_name.",
            ":products_count aanbiedingen zijn toegevoegd aan :fund_name.",
        ]),
        'provider' => implode('|', [
            ':provider_name (:products_count aanbieding)',
            ':provider_name (:products_count aanbiedingen)'
        ]),
        'item' => ':product_name :product_price_locale',
    ],

    // manually approved products (has active fund_provider_products)
    'products_manual' => [
        'title' => "Products approved manually",
        'header' => 'Nieuwe aanbiedingen voor :fund_name webshop.',
        'details' => implode('|', [
            ":products_count aanbieding is toegevoegd aan :fund_name.",
            ":products_count aanbiedingen zijn toegevoegd aan :fund_name.",
        ]),
        'provider' => implode('|', [
            ':provider_name (:products_count aanbieding)',
            ':provider_name (:products_count aanbiedingen)'
        ]),
        'item' => ':product_name :product_price_locale',
    ],

    // products create but not approved
    'products_pending' => [
        'title' => "Products pending review",
        'header' => 'Nieuw aanbod beschikbaar voor :fund_name webshop.',
        'details' => implode('|', [
            "Er staat :products_count aanbod in de wacht om te beoordelen.",
            "Er staan :products_count aanbiedingen in de wacht om te beoordelen.",
        ]),
        'provider' => implode('|', [
            ':provider_name (:products_count aanbieding)',
            ':provider_name (:products_count aanbiedingen)'
        ]),
        'item' => ':product_name :product_price_locale',
    ],

    // new messages from providers
    "feedback" => [
        'title' => "Provider replies",
        'header' => implode('|', [
            "U heeft :count_messages nieuw bericht ontvangen voor :fund_name",
            "U heeft :count_messages nieuwe berichten ontvangen voor :fund_name",
        ]),
        'item_header' => 'Nieuwe berichten van :provider_name',
        'item' => implode('|', [
            '- :provider_name heeft :count_messages nieuw bericht gestuurd op :product_name.',
            '- :provider_name heeft :count_messages nieuwe berichten gestuurd op :product_name.',
        ]),
    ]
];
