<?php

return [
    'subject' => 'Update: Huidige status van uw aanmelding',
    'title' => 'Update: Huidige status van uw aanmelding',

    'budget_approved' => [
        'title' => implode('|', [
            "Uw aanmelding voor :count_funds fonds is goedgekeurd om tegoeden te scannen.",
            "Uw aanmelding voor :count_funds fonden is goedgekeurd om tegoeden te scannen.",
        ]),
        'funds_list' => implode('|', [
            "Dit betekent dat u vanaf nu tegoeden kunt scannen en kunt afschrijven.\n" .
            "U bent goedgekeurt voor:",
            "Dit betekent dat u vanaf nu tegoeden kunt scannen en kunt afschrijven.\n" .
            "U bent goedgekeurt voor:",
        ]),
        'details' =>
            "Er zijn specifieke rechten aan u toegekend per fonds.\n" .
            "Bekijk het dashboard voor de volledige context.",
    ],

    'products_approved' => [
        'title' => implode('|', [
            'Uw aanmelding voor :count_funds fonds is goedgekeurd met al uw aanbod.',
            'Uw aanmelding voor :count_funds fondsen is goedgekeurd met al uw aanbod.',
        ]),
        'funds_list' => implode('|', [
            "Dit betekent dat uw aanbod in de webshop staan voor de volgende fonds:",
            "Dit betekent dat uw aanbod in de webshop staan voor de volgende fondsen:",
        ]),
    ],

    'budget_revoked' => [
        'title' => implode('|', [
            "Uw aanmelding voor :count_funds fonds is geweigerd om tegoeden te scannen.",
            "Uw aanmelding voor :count_funds fondsen is geweigerd om tegoeden te scannen.",
        ]),
        'funds_list' => implode('|', [
            "Dit betekent dat uw aanmelding voor de volgende fonds is gewijzigd:",
            "Dit betekent dat uw aanmelding voor de volgende fondsen is gewijzigd:",
        ]),
        'details' =>
            "Er zijn specifieke rechten aan u toegekend.\n" .
            "Bekijk de beheeromgeving voor de huidige status.",
    ],

    'products_revoked' => [
        'title' => implode('|', [
            "Uw aanmelding is geweigerd om uw aanbod in de webshop te plaatsen.",
            "Uw aanmeldingen voor :count_funds fondsen zijn geweigerd om uw aanbod in de webshop te plaatsen.",
        ]),
        'funds_list' => implode('|', [
            "Dit betekent dat u voor het volgende fonds geen aanbod meer in de webshop kunt plaatsen:",
            "Dit betekent dat u voor de volgende fondsen geen aanbod meer in de webshop kunt plaatsen:",
        ]),
        'funds_list_individual' => implode('|', [
            "Voor dit fonds staat nog een specifiek aanbod in de webshop:",
            "Voor deze fondsen staan nog uw specifieke aanbod in de webshop:",
        ]),
        'details' =>
            "Bekijk de beheeromgeving voor de volledige context en huidige status.",
    ],

    'individual_products' => [
        'title' =>
            "Een aantal van uw producten of diensten zijn goedgekeurd voor fondsen.",
        'details' =>
            "Voor elk fonds zijn specifieke rechten aan u toegekend.\n" .
            "Bekijk het dashboard voor de volledige context en status.",
        'product' =>
            "- :product_name voor :product_price_locale",
    ],

    'feedback' => [
        'title' => implode('|', [
            'Feedback op :count_products product of dienst',
            'Feedback op :count_products producten of diensten',
        ]),
        'details' => implode('|', [
            'U heeft feedback ontvangen op :count_products product of dienst.',
            'U heeft feedback ontvangen op :count_products producten of diensten.',
        ]),
        'product_title' => "Nieuwe berichten op :product_name voor :product_price_locale",
        'product_details' => implode('|', [
            "- :sponsor_name - heeft :count_messages bericht gestuurd op uw aanmelding voor :fund_name.\n",
            "- :sponsor_name - heeft :count_messages berichten gestuurd op uw aanmelding voor :fund_name.\n",
        ]),
    ],

    'dashboard_button' => 'GA NAAR DE BEHEEROMGEVING',
];
