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
        'title' => 'Rechten zijn aangepast voor :organization_name',
        'description' => 'Aan uw profiel zijn nieuwe rollen toegekend namelijk :employee_roles voor organisatie :organization_name.',
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
            ':product_name van :provider_name is toegevoegd aan de ' .
            'webshop en beschikbaar om te reserveren met tegoed van :fund_name.',
    ],

    // product was manually approved by sponsor
    'requester_product_approved' => [
        'title' => ':product_name van :provider_name is beschikbaar voor :fund_name',
        'description' =>
            ':product_name van :provider_name is toegevoegd aan de ' .
            'webshop en beschikbaar om te reserveren met tegoed van :fund_name.',
    ],

    // assigned through dashboard
    'identity_voucher_assigned' => [
        'title' => ':fund_name-voucher is aan u toegekend.',
        'description' =>
            'Hierbij ontvangt u uw :fund_name-voucher. De voucher heeft een waarde van ' .
            '€ :voucher_amount en is geldig tot en met :voucher_expire_date_locale.',
    ],

    // added to identity through webshop by activation code or fund request
    'voucher_added' => [
        'title' => 'Er is een :fund_name tegoed aan u toegekend.',
        'description' =>
            'Hierbij ontvangt u uw :fund_name-voucher. ' .
            'De voucher heeft een waarde van €:voucher_amount_locale en is geldig tot en met :voucher_expire_date_locale.',
    ],

    // bought from webshop
    'product_voucher_added' => [
        'title' => 'Aanbieding :product_name bij :provider_name gereserveerd!',
        'description' =>
            'Aanbieding :product_name bij :provider_name gereserveerd! ' .
            'De reservering heeft een waarde van €:voucher_amount_locale en is geldig tot en met :voucher_expire_date_locale',
    ],

    // fund request submitted
    'fund_request_created' => [
        'title' => 'Uw aanvraag voor :fund_name is ingediend.',
        'description' =>
            'Uw aanvraag voor :fund_name is ingediend. ' .
            'U zult binnen twee weken een reactie ontvangen op uw aanvraag.',
    ],

    // fund request submitted
    'fund_request_feedback_requested' => [
        'title' => 'Aanvraag :fund_name aanvullen.',
        'description' =>
            'Uw aanvraag voor :fund_name is onvolledig. ' .
            'De gemeente heeft meer informatie nodig om uw aanvraag af te handelen. Het bericht is: :fund_request_clarification_question',
    ],

    // fund request resolved
    'fund_request_resolved' => [
        'title' => 'Uw aanvraag is afgehandeld met de status ":fund_request_state".',
        'description' => 'Uw aanvraag is afgehandeld met de status ":fund_request_state"',
    ],

    // budget voucher transaction
    'voucher_transaction' => [
        'title' => 'Er is een bedrag van uw :fund_name-voucher afgeschreven.',
        'description' =>
            'Er is met uw voucher een aankoop gedaan. Hierdoor is er een bedrag afgeschreven. ' .
            'Het huidige bedrag van uw \':fund_name\'-voucher is €:voucher_amount_locale.',
    ],

    // product voucher transaction
    'product_voucher_transaction' => [
        'title' => 'Een aanbiedingsvoucher is zojuist gebruikt om :product_name af te nemen.',
        'description' => 'Een aanbiedingsvoucher is zojuist gebruikt om :product_name af te nemen.',
    ],

    // product voucher shared to provider
    'product_voucher_shared' => [
        'title' => 'Aanbieding QR-code gedeeld met :provider_name.',
        'description' =>
            'U heeft de aanbieding gedeeld met :provider_name met het volgende bericht: ' .
            ':voucher_share_message',
    ],

    // product voucher is about to expire
    // todo: add product details
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
    // todo: add product details
    'product_voucher_expired' => [
        'title' => 'Uw :fund_name-voucher is verlopen.',
        'description' => 'Uw :fund_name-voucher is verlopen.'
    ],

    // budget voucher expired
    'budget_voucher_expired' => [
        'title' => 'Uw :fund_name-voucher is verlopen.',
        'description' => 'Uw :fund_name-voucher is verlopen.'
    ]
];
