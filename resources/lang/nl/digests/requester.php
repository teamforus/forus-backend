<?php

return [
    'title' => 'Update: Nieuwe aanbod op de webshop',
    'providers' => [
        'title' => implode('|', [
            // singular
            ":sponsor_name zijn goedgekeurd :count_providers nieuwe aanbieder voor :fund_name",
            // plural
            ":sponsor_name zijn goedgekeurd :count_providers nieuwe aanbieders voor :fund_name"
        ]),
        'description' =>
            "Uw kunt uw tegoed bij de volgende nieuwe aanbieders: :providers_list\n\n" .
            "Bekijk de webshop voor de volledige context en status.",
    ],

    'products' => [
        'title' => implode('|', [
            // singular
            ":sponsor_name zijn geaccepteerd :count_products nieuwe product voor :fund_name",
            // plural
            ":sponsor_name zijn geaccepteerd :count_products nieuwe producten voor :fund_name"
        ]),
        'price' => "- :product_name voor €:product_price_locale",
    ],

    'button_webshop' => 'GA NAAR HET WEBSHOP',
];