<?php

return [
    'subject' => 'Update: Nieuw aanbod op de webshop',
    'title' => 'Update: Nieuw aanbod op de webshop',

    'providers' => [
        'title' => implode('|', [
            // singular
            ":sponsor_name heeft :count_providers nieuwe aanbieder toegevoegd aan :fund_name",
            // plural
            ":sponsor_name heeft :count_providers nieuwe aanbieders toegevoegd aan :fund_name"
        ]),
        'description' =>
            "Uw tegoed kunt u nu uitgeven bij: :providers_list\n\n" .
            "Kijk op de webshop voor meer informatie over de aanbieders.",
    ],

    'products' => [
        'title' => implode('|', [
            // singular
            ":sponsor_name heeft :count_products nieuwe aanbieding aan de webshop toegevoegd van :fund_name.",
            // plural
            ":sponsor_name heeft :count_products nieuwe aanbiedingen aan de webshop toegevoegd van :fund_name."
        ]),
        'price' => "- :product_name voor €:product_price_locale",
    ],

    'button_webshop' => 'GA NAAR DE WEBSHOP',
];
