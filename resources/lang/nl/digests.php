<?php

return [
    'provider_funds' => [
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
    ],
    'provider_products' => [
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
    ],
    'provider_reservations' => [
        'subject' => 'Overzicht van uw reserveringen',
        'title' => 'Overzicht van uw reserveringen',

        'reservations' => [
            'title' => implode('|', [
                "Beste :provider_name,\n\nU heeft in totaal :count_reservations reservering ontvangen (:count_pending_reservations in afwachting) voor het volgende aanbod.",
                "Beste :provider_name,\n\nU heeft in totaal :count_reservations reserveringen ontvangen (:count_pending_reservations in afwachting) voor het volgende aanbod.",
            ]),
            'product_item' => [
                'title' => ':product_name voor :product_price_locale',
                'description' => implode('|', [
                    '- :count :state reservering',
                    '- :count :state reserveringen',
                ]),
                'description_total' => implode('|', [
                    '- :count_total reservering in totaal.',
                    '- :count_total reserveringen in totaal.',
                ]),
            ],
        ],

        'description' => implode("\n", [
            "Heeft u ingesteld dat u de reserveringen handmatig wilt accepteren? Ga dan naar uw beheeromgeving om de reserveringen te bevestigen.",
            "De transactie wordt na 14 dagen verwerkt.",
        ]),

        'dashboard_button' => 'GA NAAR DE BEHEEROMGEVING',
    ],
    'requester' => [
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
                ":sponsor_name heeft :count_products nieuwe producten of diensten aan de webshop toegevoegd van :fund_name.",
                // plural
                ":sponsor_name heeft :count_products nieuwe producten of diensten aan de webshop toegevoegd van :fund_name."
            ]),
            'price' => "- :product_name voor :product_price_locale",
        ],

        'button_webshop' => 'GA NAAR DE WEBSHOP',
    ],
    'sponsor' => [
        'subject' => 'Update: Blijf op de hoogte van uw aanbieders',
        'title' => 'Laatste ontwikkelingen omtrent uw aanbieders en aanbod',

        'greetings' => 'Beste :organization_name,',
        'dashboard_button' => 'GA NAAR DE BEHEEROMGEVING',

        // new provider pending applications
        "providers_pending" => [
            'title' => 'Aanmeldingen van aanbieders die uw aandacht vereisen.',
            'header' => ':fund_name',
            'details' => implode('|', [
                ":providers_count aanbieder heeft zich aangemeld en wacht op goedkeuring.\n- :providers_list",
                ":providers_count aanbieders hebben zich aangemeld en wachten op goedkeuring.\n- :providers_list"
            ]),
        ],

        // new provider approved applications
        "providers_approved" => [
            'title' => 'Aanbieders die zijn goedgekeurd',
            'header' => ':fund_name',
            'details' => implode('|', [
                ":providers_count aanbieder is goedgekeurd.\n- :providers_list",
                ":providers_count aanbieders zijn goedgekeurd.\n- :providers_list"
            ]),
        ],

        // new provider unsubscription requests
        "providers_unsubscriptions" => [
            'title' => 'Afmeldingen van aanbieders die uw aandacht vereisen.',
            'header' => ':fund_name',
            'details' => implode('|', [
                ":providers_count provider heeft verzocht om hun deelname te beëindigen.\n- :providers_list",
                ":providers_count providers hebben verzocht om hun deelname te beëindigen.\n- :providers_list"
            ]),
        ],

        // product approved by allow_products from fund_providers
        'products_auto' => [
            'title' => "Goedgekeurd aanbod",
            'header' => 'Er zijn nieuwe aanbiedingen toegevoegd aan de webshop.',
            'details' => implode('|', [
                ":products_count aanbieding is toegevoegd aan :fund_name.",
                ":products_count aanbiedingen zijn toegevoegd aan :fund_name.",
            ]),
            'provider' => implode('|', [
                ':provider_name (:products_count aanbieding)',
                ':provider_name (:products_count aanbiedingen)'
            ]),
            'item' => ':product_name :product_price_locale',
        ],

        // manually approved products (has active fund_provider_products)
        'products_manual' => [
            'title' => "Goedgekeurd aanbod",
            'header' => 'Er zijn nieuwe aanbiedingen toegevoegd aan de webshop.',
            'details' => implode('|', [
                ":products_count aanbieding is toegevoegd aan :fund_name.",
                ":products_count aanbiedingen zijn toegevoegd aan :fund_name.",
            ]),
            'provider' => implode('|', [
                ':provider_name (:products_count aanbieding)',
                ':provider_name (:products_count aanbiedingen)'
            ]),
            'item' => ':product_name :product_price_locale',
        ],

        // products create but not approved
        'products_pending' => [
            'title' => "Geplaatste aanbiedingen die uw aandacht vereisen.",
            'header' => ':fund_name',
            'details' => implode('|', [
                "Er staat :products_count aanbieding geplaatst die wacht op goedkeuring.",
                "Er staan :products_count aanbiedingen geplaatst die wachten op goedkeuring.",
            ]),
            'provider' => implode('|', [
                ':provider_name (:products_count aanbieding)',
                ':provider_name (:products_count aanbiedingen)'
            ]),
            'item' => ':product_name :product_price_locale',
        ],

        // new messages from providers
        "feedback" => [
            'title' => "Uw heeft nieuwe berichten van aanbieders.",
            'header' => implode('|', [
                "U heeft :count_messages nieuw bericht ontvangen voor :fund_name",
                "U heeft :count_messages nieuwe berichten ontvangen voor :fund_name",
            ]),
            'item_header' => ':provider_name',
            'item' => implode('|', [
                '- :provider_name heeft :count_messages nieuw bericht gestuurd op :product_name.',
                '- :provider_name heeft :count_messages nieuwe berichten gestuurd op :product_name.',
            ]),
        ]
    ],
    'sponsor_product_updates' => [
        'subject' => 'Wijzigingen in aanbiedingen',
        'title' => 'Wijzigingen in aanbiedingen',

        'greetings' => 'Beste :organization_name,',

        'details' => implode(' ', [
            "Aanbieders hebben wijzigingen aangebracht in eerder geaccepteerde aanbiedingen.",
            "Er zijn :nr_changes nieuwe wijzigingen. Bekijk en beoordeel de wijzigingen in de beheeromgeving.",
        ]),

        'dashboard_button' => 'Bekijk de wijzigingen',
    ],
    'validator' => [
        'subject' => 'Update: Nieuwe aanvragen',
        'title' => implode('|', [
            "Update: :count_requests nieuwe aanvraag",
            "Update: :count_requests nieuwe aanvragen",
        ]),
        'greetings' => implode('|', [
            "Beste :organization_name,\n Er is :count_requests notificatie die betrekking heeft tot uw organisatie.",
            "Beste :organization_name,\n Er zijn :count_requests notificaties die betrekking hebben tot uw organisatie.",
        ]),
        'fund_header' => implode('|', [
            ":count_requests nieuwe aanvraag voor :fund_name",
            ":count_requests nieuwe aanvragen voor :fund_name",
        ]),
        'fund_details' => implode('|', [
            "U heeft :count_requests nieuwe aanvraag wachtende op uw beheeromgeving.\n" .
            "Ga naar de beheeromgeving om deze aanvraag goed te keuren.",
            "U heeft :count_requests nieuwe aanvragen wachtende op uw beheeromgeving.\n" .
            "Ga naar de beheeromgeving om deze aanvragen goed te keuren.",
        ]),
        'dashboard_button' => 'GA NAAR DE BEHEEROMGEVING',
    ],
];
