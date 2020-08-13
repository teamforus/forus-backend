<?php

return [
    'subject' => 'Update: Nieuwe reserveringen',
    'title' => implode('|', [
        'Overzicht: :count_products nieuwe reservering',
        'Overzicht: :count_products nieuwe reserveringen',
    ]),
    'greetings' => implode('|', [
        "Beste :provider_name,\nVandaag is er :count_products aanbod gereserveerd.",
        "Beste :provider_name,\nVandaag zijn er :count_products producten of diensten gereserveerd.",
    ]),
    'fund_title' => 'Uw aanbod is gereserveerd met :fund_name',
    'fund_products' => implode('|', [
        "- :product_name :count_reservations reservering\n" .
        "De klant dient de reservering voor :fund_end_date_locale te gebruiken.",
        "- :product_name :count_reservations reserveringen\n" .
        "De klant dient de reservering voor :fund_end_date_locale te gebruiken.",
    ]),
    'dashboard_button' => 'GA NAAR DE BEHEEROMGEVING',
];
