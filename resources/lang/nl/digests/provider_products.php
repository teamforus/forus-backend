<?php

return [
    'title' => implode('|', [
        'Overzicht: :count_products nieuwe product gereserveerd :provider_name',
        'Overzicht: :count_products nieuwe producten gereserveerd :provider_name',
    ]),
    'greetings' => implode('|', [
        "Beste :provider_name,\nEr zijn :count_products product gereserveerd vandaag.",
        "Beste :provider_name,\nEr zijn :count_products producten gereserveerd vandaag.",
    ]),
    'fund_title' => 'Uw product is gereserveerd met :fund_name',
    'fund_products' => implode('|', [
        "- :product_name - :count_reservations reservering(en)\n" .
        "De laatste dag dat de klant uw product kan ophalen is :fund_end_date_locale",
        "- :product_name - :count_reservations reservering(en)\n" .
        "De laatste dag dat de klant uw product kan ophalen is :fund_end_date_locale",
    ]),
    'dashboard_button' => 'GA NAAR HET DASHBOARD',
];