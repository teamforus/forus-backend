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
        'description' => 'Aan uw profiel zijn de volgende rollen toegekend: employee_roles voor organisatie :organization_name.',
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

    // assigned through dashboard
    'identity_voucher_assigned' => [
        'title' => ':fund_name-tegoed is aan u toegekend.',
        'description' =>
            'Hierbij ontvangt u uw :fund_name-tegoed. Het tegoed heeft een waarde van ' .
            ':voucher_amount_locale en is geldig tot en met :voucher_expire_date_locale.',
    ],

    // @TODO: IMPLEMENT SUBSIDY NOTIFICATION assigned through dashboard
    'identity_voucher_subsidy_assigned' => [
        'title' => ':fund_name is aan u toegekend.',
        // informal: 'Je hebt een :fund_name gekregen!.'
        'description' =>
            'Hierbij ontvangt u uw :fund_name.' . 
            'Het tegoed is geldig tot en met :voucher_expire_date_locale.',
        //
    ],
    

    // added to identity through webshop by activation code or fund request
    'voucher_added' => [
        'title' => 'Gefeliciteerd! :fund_name is geactiveerd!',
        'description' =>
            'Uw :fund_name is geactiveerd. ' .
            'Het tegoed heeft een waarde van :voucher_amount_locale en is geldig tot en met :voucher_expire_date_locale.',
    ],

    // @TODO: IMPLEMENT SUBSIDY NOTIFICATION voucher added
    'voucher_added_subsidy' => [
        'title' => 'Er is een :fund_name aan u toegekend.',
        // informal: 'title' => 'Je hebt een :fund_name gekregen!.',
        'description' =>
            'Hierbij ontvangt u uw :fund_name. ' .
            'Het tegoed is geldig tot en met :voucher_expire_date_locale.',
        // informal: 'Gefeliciteerd! Je hebt een :fund_name gekregen.'
        // 'Het tegoed is geldig tot en met :voucher_expire_date_locale.',
    ],

    // bought from webshop
    'product_voucher_added' => [
        'title' => 'Aanbod :product_name bij :provider_name gereserveerd!',
        'description' =>
            'Aanbod :product_name bij :provider_name gereserveerd! ' .
            'De reservering heeft een waarde van €:voucher_amount_locale en is geldig tot en met :voucher_expire_date_locale',
    ],

    // fund request submitted @todo: max change last sentence to informal/formal
    'fund_request_created' => [
        'title' => 'Aanvraag voor :fund_name is ontvangen.',
        'description' =>
            'De aanvraag voor :fund_name is ontvangen. ' .
            'U ontvangt binnen twee weken een reactie op uw aanvraag.',
            // informal: 'Je ontvangt binnen twee weken een reactie op jouw aanvraag.',
    ],

    // fund request submitted
    'fund_request_feedback_requested' => [
        'title' => 'Aanvraag :fund_name aanvullen.',
        'description' =>
            'Uw aanvraag voor :fund_name is onvolledig. ' .
            ':sponsor_name heeft meer informatie nodig om uw aanvraag af te handelen. Het bericht is: :fund_request_clarification_question',
    ],

    // fund request resolved TODO: @max uitzoeken
    'fund_request_resolved' => [
        'title' => 'Aanvraag is behandeld',
        'description' => 'Aanvraag is behandeld.',
    ],

    // budget voucher transaction
    'voucher_transaction' => [
        'title' => 'Er is een bedrag van uw :fund_name-tegoed afgeschreven.',
        'description' =>
            'Er is met uw tegoed een aankoop gedaan. Hierdoor is er een bedrag afgeschreven. ' .
            'Het huidige bedrag van uw \':fund_name\'-voucher is €:voucher_amount_locale.',
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
        'description' =>
            'U heeft :product_name gereserveerd bij :provider_name.',
    ],

    // product voucher shared to provider
    'product_voucher_shared' => [
        'title' => 'Aanbod QR-code gedeeld met :provider_name.',
        'description' =>
            'U heeft de aanbod gedeeld met :provider_name met het volgende bericht: ' .
            ':voucher_share_message',
    ],

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
        'title' => ':fund_name tegoed is verlopen.',
        'description' => 'Het tegoed op :fund_name-tegoed is verlopen.'
    ],

    // budget voucher expired
    'budget_voucher_expired' => [
        'title' => ':fund_name tegoed is verlopen.',
        'description' => 'Het tegoed op :fund_name-tegoed is verlopen.'
    ]
];
