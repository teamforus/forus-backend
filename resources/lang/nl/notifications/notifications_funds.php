<?php

return [
    'provider_applied' => [
        'title' => ':provider_name heeft zich aangemeld voor :fund_name.',
        'description' => ':provider_name heeft zich aangemeld voor :fund_name',
    ],
    'provider_message' => [
        'title' => ':fund_name: een nieuw bericht op aanmelding van :provider_name met :product_name.',
        'description' => 'Er is een bericht op uw reactie op :product_name van :provider_name voor :fund_name.',
    ],
    'balance_low' => [
        'title' => 'Het budget ":fund_name" heeft de ingestelde aanvul-herinneringsgrens overschreden.',
        'description' =>
            'Het budget ":fund_name" is lager dan €:fund_notification_amount. ' .
            'Het budget ":fund_name" is momenteel €:fund_budget_left.',
    ],
    'balance_supplied' => [
        'title' => 'Het budget voor ":fund_name" is opgehoogd.',
        'description' => 'Het budget voor ":fund_name" is opgehoogd met €:fund_top_up_amount_locale.',
    ],
    'ended' => [
        'title' => ':fund_name is geëindigd.',
        'description' =>
            ':fund_name liep van :fund_start_date_locale: tot :fund_end_date_locale: en is vanaf vandaag niet meer geldig. ' .
            'Dit betekent dat er geen betalingen meer gedaan kunnen worden met QR-codes van :fund_name.',
    ],
    'started' => [
        'title' => ':fund_name is van start gegaan!',
        'description' => ':fund_name is gestart! Vanaf vandaag kunnen aanbieders klanten verwachten met een tegoed van :fund_name.',
    ],
    'expiring' => [
        'title' => ':fund_name verloopt bijna!',
        'description' => ':fund_name zal sluiten op :fund_end_date_locale.',
    ],
    'created' => [
        'title' => ':fund_name is aangemaakt!',
        'description' => ':fund_name is aangemaakt.',
    ],
    'product_added' => [
        'title' => ':provider_name heeft een nieuw aanbod toegevoegd aan :fund_name.',
        'description' => ':provider_name heeft een nieuw aanbod toegevoegd aan :fund_name.',
    ],
    'product_subsidy_removed' => [
        'title' => ':provider_name heeft de prijs veranderd voor ":product_name"',
        'description' =>
            ':provider_name heeft de prijs veranderd voor :product_name" en de actie is verwijderd uit de webshop.'.
            'Als u dit aanbod weer wilt toevoegen aan de webshop, start opnieuw een actie op uw dashboard.'
    ]
];
