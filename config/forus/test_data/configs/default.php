<?php

return [
    // primary email for the identity to whom the organizations and funds are attached
    'primary_email' => 'example@example.com',

    // count of providers to be generated
    'providers_count' => 5,
    'validators_count' => 2,
    'provider_offices_count' => 2,
    'provider_products_count' => 4,
    'vouchers_per_fund_count' => 5,

    'default_organization_iban' => '00000000',
    'prevalidation_bsn' => false,

    'no_vouchers' => false,
    'no_product_vouchers' => false,

    // amount in generated vouchers
    'voucher_amount' => 600,

    'voucher_transaction_min' => 5,
    'voucher_transaction_max' => 50,

    'fund_requests_count' => 10,
    'fund_request_email_pattern' => 'requester-%s@example.com',

    // default bunq key
    'bunq_key' => '',

    // criteria for generated funds
    'funds_criteria' => [[
        'record_type_key' => 'children_nth',
        'operator' => '>',
        'value' => 2,
    ], [
        'record_type_key' => 'net_worth',
        'operator' => '<',
        'value' => 1000,
    ], [
        'record_type_key' => 'gender',
        'operator' => '=',
        'value' => 'Female',
    ]],

    // default digid config
    'digid_enabled' => false,
    'digid_app_id' => null,
    'digid_shared_secret' => null,
    'digid_a_select_server' => null,
    'digid_trusted_cert' => 'disable',

    // default implementation frontend urls
    'url_webshop' => "http://localhost:5500/#!/",
    'url_sponsor' => "http://localhost:3500/#!/",
    'url_provider' => "http://localhost:4000/#!/",
    'url_validator' => "http://localhost:4500/#!/",
    'url_app' => "http://localhost:9000/#!/",

    'backoffice_url' => null,
    'backoffice_server_key' => "",
    'backoffice_server_cert' => "",
    'backoffice_client_cert' => "",
    'backoffice_client_cert_key' => "",
    'backoffice_fallback' => true,

    'iconnect_url' => "",
    'iconnect_oin' => "",
    'iconnect_binding' => "",

    'productboard_api_key' => null,

    'iconnect_env' => "",
    'iconnect_cert' => "",
    'iconnect_cert_pass' => "",
    'iconnect_key' => "",
    'iconnect_key_pass' => "",
    'iconnect_cert_trust' => "",

    "organizations" => [
        'Zuidhorn' => [
            'allow_custom_fund_notifications' => true,
        ],
        'Nijmegen' => [
            'allow_budget_fund_limits' => true,
            'allow_manual_bulk_processing' => true,
            'allow_custom_fund_notifications' => true,
        ],
        'Stadjerspas' => [
            'allow_budget_fund_limits' => true,
            'manage_provider_products' => true,
        ],
    ],

    "implementations" => [
        'Zuidhorn' => [
            'informal_communication' => true,
            'digid_saml' => true,
        ],
        'Nijmegen' => [
            'digid_saml' => true,
            'digid_signup' => true,
            'informal_communication' => true,
            'allow_per_fund_notification_templates' => true,
        ],
        'Stadjerspas' => [
            'digid_signup' => true,
            'informal_communication' => true,
        ],
    ],

    "funds" => [
        'Zuidhorn' => [
            'implementation' => 'Zuidhorn',
            'organization' => 'Zuidhorn',
            'type' => 'budget',
            'allow_reimbursements' => true,
            'allow_voucher_top_ups' => true,
            'allow_voucher_records' => true,
            'criteria_editable_after_start' => true,
        ],
        'Nijmegen' => [
            'implementation' => 'Nijmegen',
            'organization' => 'Nijmegen',
            'key' => 'meedoen',
            'type' => 'budget',
            'allow_physical_cards' => true,
            'allow_reimbursements' => true,
            'auto_requests_validation' => true,
            'criteria_editable_after_start' => true,
            'allow_voucher_top_ups' => true,
            'allow_voucher_records' => true,
            'allow_direct_payments' => true,
            'allow_generator_direct_payments' => true,
        ],
        'Nijmegen II' => [
            'implementation' => 'Nijmegen',
            'organization' => 'Nijmegen',
            'type' => 'budget',
        ],
        'Westerkwartier' => [
            'implementation' => 'Westerkwartier',
            'organization' => 'Westerkwartier',
            'type' => 'budget',
        ],
        'Stadjerspas' => [
            'implementation' => 'Stadjerspas',
            'organization' => 'Stadjerspas',
            'type' => 'subsidies',
            'allow_physical_cards' => true,
            'auto_requests_validation' => true,
        ],
        'Stadjerspas II' => [
            'implementation' => 'Stadjerspas',
            'organization' => 'Stadjerspas',
            'type' => 'budget',
            'email_optional' => true,
            'allow_direct_payments' => true,
            'allow_generator_direct_payments' => true,
        ],
        'Berkelland' => [
            'implementation' => 'Berkelland',
            'organization' => 'Berkelland',
            'type' => 'budget',
        ],
        'Kerstpakket' => [
            'implementation' => 'Kerstpakket',
            'organization' => 'Kerstpakket',
            'type' => 'budget',
        ],
        'Noordoostpolder' => [
            'implementation' => 'Noordoostpolder',
            'organization' => 'Noordoostpolder',
            'type' => 'budget',
        ],
        'Oostgelre' => [
            'implementation' => 'Oostgelre',
            'organization' => 'Oostgelre',
            'type' => 'budget',
        ],
        'Winterswijk' => [
            'implementation' => 'Winterswijk',
            'organization' => 'Winterswijk',
            'type' => 'budget',
        ],
        'Potjeswijzer' => [
            'implementation' => 'Potjeswijzer',
            'organization' => 'Potjeswijzer',
            'type' => 'budget',
        ]
    ]
];