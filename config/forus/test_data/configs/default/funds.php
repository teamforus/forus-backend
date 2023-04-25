<?php

return [
    'Zuidhorn' => [
        'implementation' => 'Zuidhorn',
        'organization' => 'Zuidhorn',
        'data' => [
            'type' => 'budget',
            'criteria_editable_after_start' => true,
        ],
        'data_config' => [
            'key' => 'zuidhorn',
            'allow_reimbursements' => true,
            'allow_voucher_top_ups' => true,
            'allow_voucher_records' => true,
        ],
    ],
    'Nijmegen' => [
        'implementation' => 'Nijmegen',
        'organization' => 'Nijmegen',
        'data' => [
            'type' => 'budget',
            'auto_requests_validation' => true,
            'criteria_editable_after_start' => true,
        ],
        'data_config' => [
            'key' => 'meedoen',
            'allow_reimbursements' => true,
            'allow_physical_cards' => true,
            'allow_voucher_top_ups' => true,
            'allow_voucher_records' => true,
            'allow_direct_payments' => true,
            'allow_generator_direct_payments' => true,
        ],
    ],
    'Nijmegen II' => [
        'implementation' => 'Nijmegen',
        'organization' => 'Nijmegen',
        'data' => [
            'type' => 'budget',
        ],
    ],
    'Nijmegen III' => [
        'implementation' => 'Nijmegen',
        'organization' => 'Nijmegen',
        'data' => [
            'type' => 'subsidies',
        ],
    ],
    'Westerkwartier' => [
        'implementation' => 'Westerkwartier',
        'organization' => 'Westerkwartier',
        'data' => [
            'type' => 'budget',
        ],
    ],
    'Stadjerspas: Jeugd' => [
        'implementation' => 'Stadjerspas',
        'organization' => 'Gemeente Groningen',
        'data' => [
            'description_text' => "",
            'description_short' => "Stadjerspas voor volwassenen.",
            'request_btn_text' => "Aanvragen",
            'type' => "subsidies",
            'state' => "active",
            'balance' => "0.00",
            'balance_provider' => "top_ups",
            'archived' => false,
            'public' => false,
            'criteria_editable_after_start' => false,
            'notification_amount' => null,
            'manage_provider_products' => true,
            'start_date' => "2020-09-18",
            'end_date' => "2023-12-23",
            'default_validator_employee_id' => null,
            'auto_requests_validation' => false,
        ],
        'data_config' => [
            'key' => "stadjerspas",
            'record_validity_days' => null,
            'hash_bsn' => true,
            'hash_bsn_salt' => "JRKAArVhLLG0TWYaREQmwbLF7zMrma9g1HqdwwHq7WCM67BdzvQaDrkKp5AZ1EVs",
            'hash_partner_deny' => true,
            'bunq_allowed_ip' => "",
            'bunq_sandbox' => true,
            'csv_primary_key' => "bsn_hash",
            'allow_physical_cards' => true,
            'allow_fund_requests' => false,
            'allow_prevalidations' => false,
            'allow_direct_requests' => false,
            'allow_blocking_vouchers' => false,
            'allow_reimbursements' => false,
            'allow_direct_payments' => false,
            'allow_generator_direct_payments' => false,
            'allow_voucher_top_ups' => false,
            'allow_voucher_records' => false,
            'employee_can_see_product_vouchers' => false,
            'vouchers_type' => "internal",
            'email_required' => true,
            'contact_info_enabled' => false,
            'contact_info_required' => false,
            'contact_info_message_custom' => false,
            'limit_generator_amount' => "5000.00",
            'limit_voucher_top_up_amount' => "5000.00",
            'limit_voucher_total_amount' => "5000.00",
            'generator_ignore_fund_budget' => false,
            'bsn_confirmation_time' => "900",
            'bsn_confirmation_api_time' => "900",
            'backoffice_enabled' => false,
            'backoffice_check_partner' => false,
            'backoffice_fallback' => false
        ],
        "data_criteria" => [
            [
                'record_type_key' => "stadjerspas_eligible",
                'operator' => "=",
                'value' => "Akkoord",
                'title' => "U gaat akkoord met de voorwaarden!",
                'show_attachment' => true,
                'description' => implode("\r\n", [
                    "- Ik ben ouder dan 18 jaar;",
                    "- Ik woon in Groningen;",
                    "- Ik ontvang geen studiefinanciering;",
                    "- Geen werkzaamheden, inkomsten of omstandigheden te hebben verzwegen die van belang kunnen zijn voor de beoordeling van deze aanvraag.",
                    "- Ik gebruik de Stadjerspas alleen voor mij persoonlijk en verstrek het niet aan derden;",
                    "- De vragen op dit formulier volledig en naar waarheid te hebben ingevuld.",
                ]),
            ],
            [
                'record_type_key' => "children_nth",
                'operator' => ">",
                'value' => "0",
                'title' => "Uw aantal kinderen",
                'show_attachment' => true,
                'description' => "",
            ],
            [
                'record_type_key' => "income_level",
                'operator' => "=",
                'value' => "120%",
                'title' => "Deel uw inkomensgegevens",
                'show_attachment' => true,
                'description' => implode("\r\n", [
                    "Wilt u weten wat de inkomensnorm is, [klik dan hier](./explanation).\r\n ",
                    "Stuur bewijsstukken mee van alle inkomsten, zoals:\r\n ",
                    "- Uw loonstrook",
                    "- Alimentatie",
                    "- ZZP-inkomsten",
                    "- Als er beslag is gelegd op uw inkomsten.",
                ])
            ]
        ],
        'data_formula' => [
            [
                'type' => "fixed",
                'amount' => "0.00",
                'record_type_key' => null,
            ],
        ],
        'data_limit_multiplier' => null,
    ],
    'Stadjerspas' => [
        'implementation' => 'Stadjerspas',
        'organization' => 'Gemeente Groningen',
        'data' => [
            'name' => "Stadjerspas",
            'description_text' => "",
            'description_short' => "Stadjerspas voor volwassenen.",
            'request_btn_text' => "Aanvragen",
            'type' => "subsidies",
            'state' => "active",
            'balance' => "0.00",
            'balance_provider' => "top_ups",
            'archived' => false,
            'public' => false,
            'criteria_editable_after_start' => false,
            'notification_amount' => null,
            'manage_provider_products' => true,
            'start_date' => "2020-09-18",
            'end_date' => "2023-12-23",
            'default_validator_employee_id' => null,
            'auto_requests_validation' => false,
        ],
        'data_config' => [
            'key' => "stadjerspas",
            'record_validity_days' => null,
            'hash_bsn' => true,
            'hash_bsn_salt' => "pUVje8fCMl8OzOqMYAKt5gcIiZ5JvbDD0Mf7jTSByPoUpxTLehbOoIfLvDv5ioh4",
            'hash_partner_deny' => false,
            'bunq_key' => "30a71cde0fc533612bd896ebc248133b64bd8dbb6fdcc273725c0cc8f954f81b",
            'bunq_allowed_ip' => "",
            'bunq_sandbox' => true,
            'csv_primary_key' => "bsn_hash",
            'allow_physical_cards' => true,
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
            'allow_direct_requests' => true,
            'allow_blocking_vouchers' => false,
            'allow_reimbursements' => false,
            'allow_direct_payments' => false,
            'allow_generator_direct_payments' => false,
            'allow_voucher_top_ups' => false,
            'allow_voucher_records' => false,
            'employee_can_see_product_vouchers' => false,
            'vouchers_type' => "internal",
            'email_required' => true,
            'contact_info_enabled' => false,
            'contact_info_required' => false,
            'contact_info_message_custom' => false,
            'limit_generator_amount' => "5000.00",
            'limit_voucher_top_up_amount' => "5000.00",
            'limit_voucher_total_amount' => "5000.00",
            'generator_ignore_fund_budget' => false,
            'bsn_confirmation_time' => "900",
            'bsn_confirmation_api_time' => "900",
            'backoffice_enabled' => false,
            'backoffice_check_partner' => false,
            'backoffice_fallback' => false,
        ],
        "data_criteria" => [
            [
                'record_type_key' => "income_level",
                'operator' => "=",
                'value' => "120%",
                'title' => "Deel uw inkomensgegevens",
                'show_attachment' => true,
                'description' => implode("\r\n", [
                    "Wilt u weten wat de inkomensnorm is, [klik dan hier](./explanation).  \r\n",
                    "Stuur bewijsstukken mee van alle inkomsten, zoals:  \r\n",
                    "- Uw loonstrook",
                    "- Alimentatie",
                    "- ZZP-inkomsten",
                    "- Als er beslag is gelegd op uw inkomsten.",
                ])
            ],
            [
                'record_type_key' => "children_nth",
                'operator' => ">",
                'value' => "-1",
                'title' => "Uw aantal kinderen",
                'show_attachment' => true,
                'description' => "",
            ],
            [
                'record_type_key' => "stadjerspas_eligible",
                'operator' => "=",
                'value' => "Akkoord",
                'title' => "U gaat akkoord met de voorwaarden",
                'show_attachment' => false,
                'description' => implode("\r\n", [
                    "- Ik ben ouder dan 18 jaar;",
                    "- Ik woon in Groningen;",
                    "- Ik ontvang geen studiefinanciering;",
                    "- Geen werkzaamheden, inkomsten of omstandigheden te hebben verzwegen die van belang kunnen zijn voor de beoordeling van deze aanvraag.",
                    "- Ik gebruik de Stadjerspas alleen voor mij persoonlijk en verstrek het niet aan derden;",
                    "- De vragen op dit formulier volledig en naar waarheid te hebben ingevuld.",
                ]),
            ]
        ],
        'data_formula' => [
            [
                'type' => "fixed",
                'amount' => "0.00",
                'record_type_key' => null,
            ],
        ],
        'data_limit_multiplier' => null,
    ],
    'Berkelland' => [
        'implementation' => 'Berkelland',
        'organization' => 'Berkelland',
        'data' => [
            'type' => 'budget',
        ],
    ],
    'Kerstpakket' => [
        'implementation' => 'Kerstpakket',
        'organization' => 'Kerstpakket',
        'data' => [
            'type' => 'budget',
        ],
    ],
    'Noordoostpolder' => [
        'implementation' => 'Noordoostpolder',
        'organization' => 'Noordoostpolder',
        'data' => [
            'type' => 'budget',
        ],
    ],
    'Oostgelre' => [
        'implementation' => 'Oostgelre',
        'organization' => 'Oostgelre',
        'data' => [
            'type' => 'budget',
        ],
    ],
    'Winterswijk' => [
        'implementation' => 'Winterswijk',
        'organization' => 'Winterswijk',
        'data' => [
            'type' => 'budget',
        ],
    ],
    'Potjeswijzer' => [
        'implementation' => 'Potjeswijzer',
        'organization' => 'Potjeswijzer',
        'data' => [
            'type' => 'budget',
        ],
    ],
    'Doetegoed' => [
        'implementation' => 'Doetegoed',
        'organization' => 'Doetegoed',
        'data' => [
            'type' => 'budget',
        ],
    ],
];