<?php

return [
    'subject' => 'Update: Blijf op de hoogte van uw aanbieders',
    'title' => 'Laatste ontwikkelingen omtrent uw aanbieders en aanbod',

    'greetings' => 'Beste :organization_name,',
    'dashboard_button' => 'GA NAAR DE BEHEEROMGEVING',

    // new provider pending applications
    "providers_pending" => [
        'title' => 'Aanmeldingen van aanbieders die uw aandacht vereisen.',
        'header' => ':fund_name',
        'details' => implode('|', [
            ":providers_count aanbieder heeft zich aangemeld en wacht op goedkeuring.\n- :providers_list",
            ":providers_count aanbieders hebben zich aangemeld en wachten op goedkeuring.\n- :providers_list"
        ]),
    ],

    // new provider approved applications
    "providers_approved" => [
        'title' => 'Aanbieders die zijn goedgekeurd',
        'header' => ':fund_name',
        'details' => implode('|', [
            ":providers_count aanbieder is goedgekeurd.\n- :providers_list",
            ":providers_count aanbieders zijn goedgekeurd.\n- :providers_list"
        ]),
    ],

    // new provider unsubscription requests
    "providers_unsubscriptions" => [
        'title' => 'Afmeldingen van aanbieders die uw aandacht vereisen.',
        'header' => ':fund_name',
        'details' => implode('|', [
            ":providers_count provider heeft verzocht om hun deelname te beëindigen.\n- :providers_list",
            ":providers_count providers hebben verzocht om hun deelname te beëindigen.\n- :providers_list"
        ]),
    ],

    // product approved by allow_products from fund_providers
    'products_auto' => [
        'title' => "Goedgkeurd aanbod",
        'header' => 'Er zijn nieuwe aanbiedingen toegvoegd aan de webshop.',
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
        'title' => "Goedgkeurd aanbod",
        'header' => 'Er zijn nieuwe aanbiedingen toegvoegd aan de webshop.',
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
        'title' => "Geplaatst aanbiedingen die uw aandacht vereisen.",
        'header' => ':fund_name',
        'details' => implode('|', [
            "Er staat :products_count aanbieding geplaatst die wacht op goedkeuring.",
            "Er staan :products_count aanbiedingen geplaatst die wachten op goedkeuring.",
        ]),
        'provider' => implode('|', [
            ':provider_name (:products_count aanbieding)',
            ':provider_name (:products_count aanbiedingen)'
        ]),
        'item' => ':product_name :product_price_locale',
    ],

    // new messages from providers
    "feedback" => [
        'title' => "Uw heeft nieuwe berichten van aanbieders.",
        'header' => implode('|', [
            "U heeft :count_messages nieuw bericht ontvangen voor :fund_name",
            "U heeft :count_messages nieuwe berichten ontvangen voor :fund_name",
        ]),
        'item_header' => ':provider_name',
        'item' => implode('|', [
            '- :provider_name heeft :count_messages nieuw bericht gestuurd op :product_name.',
            '- :provider_name heeft :count_messages nieuwe berichten gestuurd op :product_name.',
        ]),
    ]
];
