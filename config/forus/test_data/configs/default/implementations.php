<?php

return [
    'Zuidhorn' => [
        'implementation' => [
            'digid_connection_type' => 'cgi',
            'digid_sign_up_allowed' => true,
            'informal_communication' => true,
        ],
    ],
    'Nijmegen' => [
        'implementation' => [
            'digid_enabled' => false,
            'digid_required' => false,
            'digid_connection_type' => 'saml',
            'digid_sign_up_allowed' => true,
            'informal_communication' => true,
            'allow_per_fund_notification_templates' => true,
        ],
    ],
    'Stadjerspas' => [
        'implementation' => [
            'key' => "groningen",
            'title' => "Doe jij al mee met de Stadjerspas?",
            'description' => "Welkom op de website van de Stadjerspas. U kan hier de regeling aanvragen en het aanbod bekijken.",
            'description_alignment' => "left",
            'overlay_enabled' => false,
            'overlay_type' => "color",
            'header_text_color' => "auto",
            'overlay_opacity' => 90,
            'lon' => "5.58338",
            'lat' => "52.137586",
            'informal_communication' => true,
            'email_from_name' => "Gemeente Groningen",
            'email_from_address' => null,
            'email_color' => "#F6F5F5",
            'email_signature' => null,
            'show_home_map' => true,
            'show_home_products' => true,
            'show_providers_map' => true,
            'show_provider_map' => true,
            'show_office_map' => true,
            'show_voucher_map' => true,
            'show_product_map' => true,
        ]
    ],
    'Goeree-Overflakkee' => [
        'implementation' => [
            'key' => "goereeoverflakkee",
            'title' => "Kindpakket Goeree-Overflakkee",
            'description' => "Gemeente Goeree-Overflakkee wil dat álle kinderen meedoen. En dat alle kinderen lekker kunnen sporten en zonder zorgen naar school gaan. Ook kinderen uit een gezin waar niet zoveel geld is. De gemeente helpt deze kinderen met het Kindpakket Goeree-Overflakkee.",
            'description_alignment' => "left",
            'page_title_suffix' => "Gemeente Goeree-Overflakkee",
            'informal_communication' => true,
            'email_from_name' => "Gemeente Goeree-Overflakkee",
            'email_color' => "#08749B",
            'show_home_map' => true,
            'show_home_products' => true,
            'show_providers_map' => true,
            'show_provider_map' => true,
            'show_office_map' => true,
            'show_voucher_map' => true,
            'show_product_map' => true,
            'allow_per_fund_notification_templates' => true,
            'digid_enabled' => false,
        ]
    ],
    'Berkelland' => [
        'implementation' => [
            'key' => "berkelland",
            'title' => "Kindregelingen gemeente Berkelland",
            'description' => "Welkom op de website van de Kindregelingen van de gemeente Berkelland.",
            'description_alignment' => "left",
            'page_title_suffix' => "Fijnder",
            'informal_communication' => false,
            'email_from_name' => "Gemeente Berkelland",
            'show_home_map' => true,
            'show_home_products' => true,
            'show_providers_map' => true,
            'show_provider_map' => true,
            'show_office_map' => true,
            'show_voucher_map' => true,
            'show_product_map' => true,
            'allow_per_fund_notification_templates' => false,
            'digid_enabled' => false,
        ]
    ],
    'Oostgelre' => [
        'implementation' => [
            'key' => "oostgelre",
            'title' => "Kindregelingen gemeente Oostgelre",
            'description' => "Welkom op de website van de Kindregelingen van de gemeente Oostgelre.",
            'description_alignment' => "left",
            'page_title_suffix' => "Fijnder",
            'informal_communication' => false,
            'email_from_name' => "Gemeente Oostgelre",
            'show_home_map' => true,
            'show_home_products' => true,
            'show_providers_map' => true,
            'show_provider_map' => true,
            'show_office_map' => true,
            'show_voucher_map' => true,
            'show_product_map' => true,
            'allow_per_fund_notification_templates' => false,
            'digid_enabled' => false,
        ]
    ],
    'Winterswijk' => [
        'implementation' => [
            'key' => "winterswijk",
            'title' => "Kindregelingen gemeente Winterswijk",
            'description' => "Welkom op de website van de Kindregelingen van de gemeente Winterswijk.",
            'description_alignment' => "left",
            'page_title_suffix' => "Fijnder",
            'informal_communication' => false,
            'email_from_name' => "Gemeente Winterswijk",
            'show_home_map' => true,
            'show_home_products' => true,
            'show_providers_map' => true,
            'show_provider_map' => true,
            'show_office_map' => true,
            'show_voucher_map' => true,
            'show_product_map' => true,
            'allow_per_fund_notification_templates' => true,
            'digid_enabled' => false,
        ]
    ],
    'Potjeswijzer' => [
        'implementation' => [
            'key' => "potjeswijzer",
            'title' => "Voor jou en je gezin",
            'description' => "Er bestaan veel verschillende potjes binnen het Westerkwartier, vaak meer dan je denkt.",
            'description_alignment' => "left",
            'page_title_suffix' => "Gemeente Westerkwartier",
            'informal_communication' => true,
            'email_from_name' => "Gemeente Westerkwartier",
            'show_home_map' => true,
            'show_home_products' => true,
            'show_providers_map' => true,
            'show_provider_map' => true,
            'show_office_map' => true,
            'show_voucher_map' => true,
            'show_product_map' => true,
            'allow_per_fund_notification_templates' => false,
            'digid_enabled' => true,
            'digid_connection_type' => 'saml',
            'digid_sign_up_allowed' => false,
        ]
    ],
    'Noordoostpolder' => [
        'implementation' => [
            'key' => "noordoostpolder",
            'title' => "Meedoenpakket",
            'description' => "Welkom op de website van het Meedoenpakket van de gemeente Noordoostpolder.",
            'description_alignment' => "left",
            'page_title_suffix' => "Gemeente Noordoostpolder",
            'informal_communication' => false,
            'email_from_name' => "Gemeente Noordoostpolder",
            'show_home_map' => true,
            'show_home_products' => true,
            'show_providers_map' => true,
            'show_provider_map' => true,
            'show_office_map' => true,
            'show_voucher_map' => true,
            'show_product_map' => true,
            'allow_per_fund_notification_templates' => false,
            'digid_enabled' => false,
        ]
    ],
    'Geertruidenberg' => [
        'implementation' => [
            'key' => "geertruidenberg",
            'title' => "Een bijdrage voor de sport of hobby van uw kind!",
            'description' => "U heeft geen of weinig inkomen en kunt de sport, hobby of schooluitjes van uw kind niet betalen. U kunt een bijdrage aanvragen bij de gemeente. Dit heet Kindregeling.",
            'description_alignment' => "left",
            'page_title_suffix' => "Gemeente Geertruidenberg",
            'informal_communication' => false,
            'email_from_name' => "Gemeente Geertruidenberg",
            'show_home_map' => true,
            'show_home_products' => true,
            'show_providers_map' => true,
            'show_provider_map' => true,
            'show_office_map' => true,
            'show_voucher_map' => true,
            'show_product_map' => true,
            'allow_per_fund_notification_templates' => false,
            'digid_enabled' => true,
            'digid_connection_type' => 'saml',
            'digid_sign_up_allowed' => false,
        ]
    ],
    'Heumen' => [
        'implementation' => [
            'key' => "heumen",
            'title' => "Heumenstegoed",
            'description' => "Heeft u een laag inkomen? En woont u in de gemeente Heumen? Dan zijn er verschillende financiële regelingen voor u. U kunt een bijdrage aanvragen.",
            'description_alignment' => "left",
            'page_title_suffix' => "Gemeente Heumen",
            'informal_communication' => false,
            'email_from_name' => "Gemeente Heumen",
            'show_home_map' => true,
            'show_home_products' => true,
            'show_providers_map' => true,
            'show_provider_map' => true,
            'show_office_map' => true,
            'show_voucher_map' => true,
            'show_product_map' => true,
            'allow_per_fund_notification_templates' => false,
            'digid_enabled' => false,
        ]
    ],
    'Waalwijk' => [
        'implementation' => [
            'key' => "waalwijk",
            'title' => "Welkom op de website van de Paswijzer",
            'description' => "Welkom op de website van de Paswijzer.",
            'description_alignment' => "left",
            'page_title_suffix' => "Gemeente Waalwijk",
            'informal_communication' => true,
            'email_from_name' => "Paswijzer - Gemeente Waalwijk",
            'show_home_map' => true,
            'show_home_products' => true,
            'show_providers_map' => true,
            'show_provider_map' => true,
            'show_office_map' => true,
            'show_voucher_map' => true,
            'show_product_map' => true,
            'allow_per_fund_notification_templates' => false,
            'digid_enabled' => true,
            'digid_connection_type' => 'saml',
            'digid_sign_up_allowed' => false,
        ]
    ],
    'Eemsdelta' => [
        'implementation' => [
            'key' => "eemsdelta",
            'title' => "Welkom bij de Kansshop",
            'description' => "De Kansshop is een platform waarop organisaties en regelingen te vinden zijn voor huishoudens met een laag inkomen. Hier vind je ook de webwinkel van onze gemeente.",
            'description_alignment' => "left",
            'page_title_suffix' => "Gemeente Eemsdelta",
            'informal_communication' => true,
            'email_from_name' => "Kansshop - Gemeente Eemsdelta",
            'email_color' => "#0D4379",
            'show_home_map' => true,
            'show_home_products' => true,
            'show_providers_map' => true,
            'show_provider_map' => true,
            'show_office_map' => true,
            'show_voucher_map' => true,
            'show_product_map' => true,
            'allow_per_fund_notification_templates' => true,
            'digid_enabled' => true,
            'digid_connection_type' => 'saml',
            'digid_sign_up_allowed' => false,
        ]
    ],
    'Schagen' => [
        'implementation' => [
            'key' => "schagen",
            'title' => "Meedoen Schagen",
            'description' => "Welkom op de webshop Meedoen Schagen.",
            'description_alignment' => "left",
            'page_title_suffix' => "Gemeente Schagen",
            'informal_communication' => false,
            'email_from_name' => "Gemeente Schagen",
            'email_color' => "#306FB3",
            'show_home_map' => true,
            'show_home_products' => true,
            'show_providers_map' => true,
            'show_provider_map' => true,
            'show_office_map' => true,
            'show_voucher_map' => true,
            'show_product_map' => true,
            'allow_per_fund_notification_templates' => false,
            'digid_enabled' => true,
            'digid_connection_type' => 'saml',
            'digid_sign_up_allowed' => false,
        ]
    ],
    'Hart van West Brabant' => [
        'implementation' => [
            'key' => "hartvanwestbrabant",
            'title' => "Welkom bij de Meedoen Webshop van Werkplein",
            'description' => "Voor gezinnen met een laag inkomen is er de Meedoen-regeling. U krijgt dan een bijdrage om bijvoorbeeld een abonnement te nemen op een sportclub, een cursus te volgen of iets kiezen uit deze Meedoen Webshop van Werkplein. Voor u of voor uw kinderen.",
            'description_alignment' => "left",
            'page_title_suffix' => "Werkplein Hart van West-Brabant",
            'informal_communication' => false,
            'email_from_name' => "Werkplein Hart van West-Brabant",
            'email_color' => "#24A1A1",
            'show_home_map' => true,
            'show_home_products' => true,
            'show_providers_map' => true,
            'show_provider_map' => true,
            'show_office_map' => true,
            'show_voucher_map' => true,
            'show_product_map' => true,
            'allow_per_fund_notification_templates' => true,
            'digid_enabled' => false,
        ]
    ]
];
