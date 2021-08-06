<?php

return [
    'added_employee' => [
        'title' => 'U bent toegevoegd aan :organization_name als :employee_roles.',
        'description' => 'U bent toegekend aan :organization_name als :employee_roles',
    ],
    'removed_employee' => [
        'title' => 'U bent verwijderd als een medewerker van :organization_name',
        'description' => 'U bent verwijderd als een medewerker van :organization_name',
    ],
    'changed_employee_roles' => [
        'title' => 'Uw rechten als medewerker zijn aangepast voor :organization_name',
        'description' => 'Aan uw profiel zijn de volgende rollen toegekend: :employee_roles voor organisatie :organization_name.',
    ],

    // approved for budget
    'requester_provider_approved_budget' => [
        'title' => ':provider_name doet mee aan :fund_name',
        'description' => ':provider_name is geaccepteerd als aanbieder voor :fund_name door :sponsor_name',
    ],
    // approved for all products
    'requester_provider_approved_products' => [
        'title' => ':provider_name doet mee aan :fund_name',
        'description' => ':provider_name is geaccepteerd als aanbieder voor :fund_name door :sponsor_name',
    ],

    // product was added automatically
    'requester_product_added' => [
        'title' => ':product_name van :provider_name is beschikbaar voor :fund_name',
        'description' =>
            ':product_name van :provider_name is toegevoegd aan de webshop',
    ],

    // product was manually approved by sponsor
    'requester_product_approved' => [
        'title' => ':product_name van :provider_name is beschikbaar voor :fund_name',
        'description' =>
            ':product_name van :provider_name is toegevoegd aan de webshop',
    ],

    // assigned budget through dashboard
    'identity_voucher_assigned_budget' => [
        'title' => ':fund_name is aan u toegekend.',
        'title_informal' => 'Alstublieft, hier is je :fund_name !',
        'description' =>
            'Hierbij ontvangt u uw :fund_name. Het tegoed heeft een waarde van ' .
            ':voucher_amount_locale en is geldig tot en met :voucher_expire_date_locale.',
        'description_informal' =>
            'Hierbij ontvang je je :fund_name. Het tegoed heeft een waarde van ' .
            ':voucher_amount_locale en is geldig tot en met :voucher_expire_date_locale.',
    ],

    // assigned subsidy through dashboard
    'identity_voucher_assigned_subsidy' => [
        'title' => ':fund_name is aan u toegekend.',
        'title_informal' => 'Alstublieft, hier is je :fund_name! Je aanvraag is goedgekeurd.',
        'description' =>
            'Het tegoed is geldig tot en met :voucher_expire_date_locale.',
    ],

    // added to identity through webshop by activation code or fund request
    'voucher_added_budget' => [
        'title' => 'Gefeliciteerd! :fund_name is geactiveerd!',
        'description' =>
            'Uw :fund_name is geactiveerd. ' .
            'Het tegoed heeft een waarde van :voucher_amount_locale en is geldig tot en met :voucher_expire_date_locale.',
    ],

    'voucher_added_subsidy' => [
        'title' => 'Er is een :fund_name aan u toegekend.',
        'title_informal' => 'Alstublieft, hier is je :fund_name! Je aanvraag is goedgekeurd.',
        'description' =>
            'De QR-code is geldig tot en met  :voucher_expire_date_locale.',
    ],

    // bought from webshop
    'product_voucher_added' => [
        'title' => 'Aanbod :product_name bij :provider_name gereserveerd!',
        'description' =>
            'Aanbod :product_name bij :provider_name gereserveerd! ' .
            'De reservering heeft een waarde van :voucher_amount_locale en is geldig tot en met :voucher_expire_date_locale',
    ],

    // fund request submitted
    'fund_request_created' => [
        'title' => 'Aanvraag voor :fund_name is ontvangen.',
        'description' =>
            'De aanvraag voor :fund_name is ontvangen.' .
            'U ontvangt binnen twee weken een reactie op uw aanvraag.',
    ],

    // fund request submitted
    'fund_request_feedback_requested' => [
        'title' => 'Aanvraag :fund_name aanvullen.',
        'description' =>
            'Uw aanvraag voor :fund_name is onvolledig. ' .
            ':sponsor_name heeft meer informatie nodig om uw aanvraag af te handelen. Het bericht is: :fund_request_clarification_question',
        'description_informal' =>
            'Je aanvraag voor :fund_name is onvolledig. ' .
            ':sponsor_name heeft meer informatie nodig om je aanvraag af te handelen. Het bericht is: :fund_request_clarification_question',
        ],

    // fund request resolved
    'fund_request_resolved' => [
        'title' => 'Aanvraag is behandeld',
        'description' => ':sponsor_name heeft uw aanvraag voor :fund_name behandeld.',
    ],

    // budget voucher transaction
    'voucher_transaction' => [
        'title' => 'Er is een bedrag van uw :fund_name afgeschreven.',
        'title_informal' => 'Er is een bedrag van je :fund_name afgeschreven.',
        'description' =>
            'Er is met uw tegoed een aankoop gedaan. Hierdoor is er een bedrag afgeschreven. ' .
            'Het huidige bedrag van uw :fund_name is :voucher_amount_locale.',
    ],

    // subsidy voucher transaction, for subsidy fund
    'voucher_subsidy_transaction' => [
        'title' => 'Aanbieding van :fund_name gebruikt!',
        'description' =>
            "Er is gebruikgemaakt van een :fund_name aanbieding.\n" .
            "Het resterende tegoed voor :product_name is :subsidy_new_limit",
    ],

    // product voucher transaction
    'product_voucher_transaction' => [
        'title' => 'Een reservering is zojuist gebruikt om :product_name af te nemen.',
        'description' => 'Een reservering is zojuist gebruikt om :product_name af te nemen.',
    ],

    // product voucher reserved on webshop (voucher created)
    'product_voucher_reserved' => [
        'title' => 'U heeft :product_name gereserveerd.',
        'title_informal' => 'Je hebt een :product_name gereserveerd.',
        'description' =>
            'U heeft :product_name gereserveerd bij :provider_name.',
        'description' =>
            'Je hebt :product_name gereserveerd bij :provider_name.',
    ],

    // product voucher shared to provider
    'product_voucher_shared' => [
        'title' => 'Aanbod QR-code gedeeld met :provider_name.',
        'description' =>
            'U heeft de aanbod gedeeld met :provider_name met het volgende bericht: ' .
            ':voucher_share_message',
    ],

    // todo: duplicate of notification_vouchers.php; which one is used?

    // product voucher is about to expire
    'voucher_expire_soon_product' => [
        'title' => ':fund_name verloopt binnenkort.',
        'description' => ':fund_name verloopt binnenkort.'
    ],

    // budget voucher is about to expire
    'voucher_expire_soon_budget' => [
        'title' => ':fund_name verloopt binnenkort.',
        'description' => ':fund_name verloopt binnenkort.'
    ],

    // product voucher expired
    'product_voucher_expired' => [
        'title' => ':fund_name is verlopen.',
        'description' => 'Het tegoed op :fund_name is verlopen.'
    ],

    // budget voucher expired
    'budget_voucher_expired' => [
        'title' => ':fund_name is verlopen.',
        'description' => 'Het tegoed op :fund_nam is verlopen.'
    ],

    'product_reservation_accepted' => [
        'title' => 'Reserverving is geaccepteerd.',
        'description' => 'De reservering van :product_name is geaccepteerd door :provider_name',
    ],

    'product_reservation_rejected' => [
        'title' => 'Reservering is geweigerd.',
        'description' => 'De reserving van :product_name is geweigerd door :provider_name. Neem contact met de aanbieder voor meer informatie.',
    ],

    'product_reservation_created' => [
        'title' => 'Reserverving is aangemaakt.',
        'description' => 'De reservering van :product_name is aangemaakt.',
    ],

    'product_reservation_canceled' => [
        'title' => 'Reservering is geannuleerd.',
        'description' => 'De reserving van :product_name is geannuleerd. Neem eventueel contact met de aanbieder voor meer informatie.',
    ]
];
