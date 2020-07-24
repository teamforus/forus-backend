<?php

// todo: remove comments when ready
return [
    'title' => 'Nieuwe aanmeldingen voor uw fondsen',
    'greetings' => 'Beste :organization_name,',
    'dashboard_button' => 'GA NAAR HET DASHBOARD',

    // new provider requests
    'providers_header' => 'Nieuwe aanmeldingen voor :fund_name',
    'providers' => implode('|', [
        ":providers_count aanbieder heeft zich aangemeld en wacht op goedkeuring.\n- :providers_list",
        ":providers_count aanbieders hebben zich aangemeld en wachten op goedkeuring.\n- :providers_list"
    ]),

    // new products requests
    'products' => [
        'header' => 'Nieuwe aanbiedingen voor :fund_name webshop.',
        'details' => implode('|', [
            ":products_count aanbieding is toegevoegd aan :fund_name.",
            ":products_count aanbiedingen zijn toegevoegd aan :fund_name.",
        ]),
        'provider' => implode('|', [
            ':provider_name (:products_count aanbieding)',
            ':provider_name (:products_count aanbiedingen)'
        ]),
        'item' => ':product_name €:product_price_locale',
    ],

    // new provider feedback
    'feedback_header' => implode('|', [
        "U heeft :count_messages nieuw bericht ontvangen voor :fund_name",
        "U heeft :count_messages nieuwe berichten ontvangen voor :fund_name",
    ]),
    'feedback_item_header' => 'Nieuwe berichten van :provider_name',
    'feedback_item' => implode('|', [
        '- :provider_name heeft :count_messages nieuw bericht gestuurd op :product_name.',
        '- :provider_name heeft :count_messages nieuwe berichten gestuurd op :product_name.',
    ]),
];
