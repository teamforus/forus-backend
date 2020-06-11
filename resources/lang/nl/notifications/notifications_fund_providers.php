<?php

return [
    'sponsor_message' => [
        'title' => 'New chat message.',
        'description' =>
            'You have a new chat message from sponsor ":sponsor_name" related to ":product_name".'
    ],
    'fund_expiring' => [
        'title' => ':fund_name is verloopt bijna.',
        'description' =>
            ':fund_name loopt van :start_date: tot :end_date_minus1: en verloopt dus bijna.'
    ],
    'fund_ended' => [
        'title' => ':fund_name is verloopt bijna.',
        'description' =>
            ':fund_name liep van :start_date: tot :end_date_minus1: en is vanaf vandaag niet meer geldig. ' .
            ' Dit betekent dat er geen betalingen meer gedaan kunnen worden met QR-codes van :fund_name. ' .
            'Kijk in uw dashboard of u zich voor een nieuw fonds kunt aanmelden.'
    ],
    'fund_started' => [
        'title' => ':fund_name is van start gegaan!',
        'description' =>
            ':fund_name is gestart! Vanaf vandaag kunt u klanten verwachten met een tegoed van :fund_name.'
    ],
    'approved_budget' => [
        'title' => 'Aanmelding voor :fund_name is goedgekeurd.',
        'description' =>
            'Dit betekent dat u vanaf nu tegoeden kunt scannen en aanbiedingen kan leveren aan klanten die recht hebben op :fund_name. ' .
            'Al uw aanbiedingen staan nu op de webshop.',
    ],
    'approved_products' => [
        'title' => 'Aanmelding voor :fund_name is goedgekeurd.',
        'description' =>
            'Dit betekent dat u vanaf nu tegoeden kunt scannen en aanbiedingen kan leveren aan klanten die recht hebben op :fund_name. ' .
            'Al uw aanbiedingen staan nu op de webshop.',
    ],
    'revoked_budget' => [
        'title' => 'Aanmelding voor :fund_name is gewijzigd. U bent niet meer geaccepteerd voor het scannen van tegoeden.',
        'description' => 'Aanmelding voor :fund_name is gewijzigd. U bent niet meer geaccepteerd voor het scannen van tegoeden.',
    ],
    'revoked_products' => [
        'title' => 'Aanmelding voor :fund_name is gewijzigd. U bent niet meer geaccepteerd voor al uw aanbiedingen.',
        'description' => 'Aanmelding voor :fund_name is gewijzigd. U bent niet meer geaccepteerd voor al uw aanbiedingen.',
    ],
];