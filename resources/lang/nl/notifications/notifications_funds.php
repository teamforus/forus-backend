<?php

return [
    'provider_applied' => [
        'title' => ':provider_name heeft zich aangemeld voor :fund_name.',
        'description' => ':provider_name heeft zich aangemeld voor :fund_name',
    ],
    'provider_message' => [
        'title' => ':fund_name: een nieuw bericht op aanmelding van :provider_name voor :product_name.',
        'description' => 'Er is een bericht op uw reactie op :product_name van :provider_name voor :fund_name.',
    ],
    'balance_low' => [
        'title' => 'Het budget ":fund_name" heeft de ingestelde aanvul-herinneringsgrens overschreden.',
        'description' =>
            'Het budget van het ":fund_name" fonds lager is dan €:fund_notification_amount. ' .
            'Het budget op het ":fund_name" is momenteel €:fund_budget_left.',
    ],
    'balance_supplied' => [
        'title' => 'The budget for ":fund_name" was supplied.',
        'description' => 'The budget for ":fund_name" was supplied with €:fund_top_up_amount_locale.',
    ],
    'ended' => [
        'title' => ':fund_name is geëindigt.',
        'description' =>
            ':fund_name liep van :fund_start_date_locale: tot :fund_end_date_locale: en is vanaf vandaag niet meer geldig. ' .
            'Dit betekent dat er geen betalingen meer gedaan kunnen worden met QR-codes van :fund_name.',
    ],
    'started' => [
        'title' => ':fund_name is van start gegaan!',
        'description' => ':fund_name is gestart! Vanaf vandaag kunnen aanbieders klanten verwachten met een tegoed van :fund_name.',
    ],
    'expiring' => [
        'title' => ':fund_name soon will expire!',
        'description' => ':fund_name will be closed by :fund_end_date_locale.',
    ],
    'created' => [
        'title' => ':fund_name fund was created!',
        'description' => ':fund_name was created.',
    ],
    'product_added' => [
        'title' => ':provider_name heeft een nieuwe aanbieding toegevoegd aan :fund_name.',
        'description' => ':provider_name heeft een nieuwe aanbieding toegevoegd aan :fund_name.',
    ]
];
