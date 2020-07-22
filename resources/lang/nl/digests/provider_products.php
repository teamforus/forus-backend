<?php

return [
    'title' => implode('|', [
        'Overzicht: :count_products nieuwe aanbieding reservering',
        'Overzicht: :count_products nieuwe aanbieding reserveringen',
    ]),
    'greetings' => implode('|', [
        "Beste :provider_name,\nVandaag is er :count_products aanbieding gereserveerd.",
        "Beste :provider_name,\nVandaag zijn er :count_products aanbiedingen gereserveerd.",
    ]),
    'fund_title' => 'Uw aanbieding is gereserveerd met :fund_name',
    'fund_products' => implode('|', [
        "- :product_name - :count_reservations reservering\n" .
        "De klant dient de reservering voor :fund_end_date_locale te gebruiken.",
        "- :product_name - :count_reservations reserveringen\n" .
        "De klant dient de reservering voor :fund_end_date_locale te gebruiken.",
    ]),
    'dashboard_button' => 'GA NAAR HET DASHBOARD',
];
