<?php

// todo: remove comments when ready
return [
    'title' => 'Update: New applications for your funds',
    'greetings' => 'Beste :organization_name,',
    'dashboard_button' => 'GA NAAR HET DASHBOARD',

    // new provider requests
    'providers_header' => 'Nieuwe aanbieders hebben zich aangemeld :fund_name',
    'providers' => implode('|', [
        ":providers_count aanbieder hebben zich aangemeld voor :fund_name\n- :providers_list",
        ":providers_count aanbiederen hebben zich aangemeld voor :fund_name\n- :providers_list"
    ]),

    // new products requests
    'products' => [
        'header' => 'Nieuwe producten voor :fund_name webshop.',
        'details' => implode('|', [
            ":products_count product zijn toegevoegd aan :fund_name.",
            ":products_count producten zijn toegevoegd aan :fund_name.",
        ]),
        'provider' => implode('|', [
            ':provider_name (:products_count product)',
            ':provider_name (:products_count producten)'
        ]),
        'item' => ':product_name €:product_price_locale',
    ],

    // new provider feedback
    'feedback_header' => implode('|', [
        ":count_messages nieuwe reacties op de feedback die gegeven is op :fund_name",
        ":count_messages nieuwe reacties op de feedback die gegeven is op :fund_name",
    ]),
    'feedback_item_header' => 'Nieuwe berichten van :provider_name',
    'feedback_item' => implode('|', [
        '- :provider_name heeft :count_messages nieuwe bericht gestuurd over :product_name.',
        '- :provider_name heeft :count_messages nieuwe berichten gestuurd over :product_name.',
    ]),
];