<?php

return [
    'Zuidhorn' => [
        'offices_count' => 0,
        'organization' => [
            'allow_pre_checks' => true,
            'allow_bi_connection' => true,
            'allow_2fa_restrictions' => true,
            'allow_custom_fund_notifications' => true,
        ]
    ],
    'Nijmegen' => [
        'offices_count' => 0,
        'organization' => [
            'backoffice_available' => true,
            'allow_payouts' => true,
            'allow_profiles' => true,
            'allow_pre_checks' => true,
            'allow_bi_connection' => true,
            'allow_product_updates' => true,
            'allow_2fa_restrictions' => true,
            'allow_budget_fund_limits' => true,
            'allow_manual_bulk_processing' => true,
            'allow_provider_extra_payments' => true,
            'allow_fund_request_record_edit' => true,
            'allow_custom_fund_notifications' => true,
            'fund_request_resolve_policy' => "apply_auto_requested",
        ]
    ],
    'Gemeente Groningen' => [
        'offices_count' => 0,
        'organization' => [
            'website_public' => true,
            'business_type_id' => 585,
            'is_sponsor' => true,
            'is_provider' => true,
            'is_validator' => true,
            'reservations_budget_enabled' => true,
            'reservations_subsidy_enabled' => true,
            'reservations_auto_accept' => false,
            'reservation_phone' => "no",
            'reservation_address' => "no",
            'reservation_birth_date' => "no",
            'manage_provider_products' => true,
            'backoffice_available' => false,
            'allow_pre_checks' => true,
            'allow_bi_connection' => true,
            'allow_2fa_restrictions' => true,
            'allow_batch_reservations' => true,
            'allow_budget_fund_limits' => false,
            'allow_fund_request_record_edit' => true,
            'allow_custom_fund_notifications' => true,
            'pre_approve_external_funds' => false,
            'provider_throttling_value' => 100,
            'fund_request_resolve_policy' => "apply_auto_available",
            'bsn_enabled' => true,
            'bank_cron_time' => "09:00:00",
            'show_provider_transactions' => false,
        ]
    ],
    'Gemeente Westerkwartier' => [
        'offices_count' => 0,
        'organization' => [
            'website_public' => true,
            'business_type_id' => 585,
            'is_sponsor' => true,
            'is_provider' => true,
            'is_validator' => true,
            'validator_auto_accept_funds' => false,
            'reservations_budget_enabled' => true,
            'reservations_subsidy_enabled' => false,
            'reservations_auto_accept' => false,
            'reservation_phone' => "no",
            'reservation_address' => "no",
            'reservation_birth_date' => "no",
            'manage_provider_products' => false,
            'backoffice_available' => false,
            'allow_custom_fund_notifications' => true,
            'allow_provider_extra_payments' => false,
            'allow_pre_checks' => true,
            'allow_bi_connection' => false,
            'allow_2fa_restrictions' => false,
            'allow_batch_reservations' => false,
            'allow_budget_fund_limits' => false,
            'allow_fund_request_record_edit' => false,
            'pre_approve_external_funds' => false,
            'provider_throttling_value' => 100,
            'fund_request_resolve_policy' => "apply_manually",
            'bsn_enabled' => true,
            'bank_cron_time' => "09:00:00",
            'show_provider_transactions' => false,
        ]
    ],
    'Gemeente Noordoostpolder' => [
        'offices_count' => 0,
        'organization' => [
            'website_public' => false,
            'business_type_id' => 585,
            'is_sponsor' => true,
            'is_provider' => true,
            'is_validator' => true,
            'validator_auto_accept_funds' => false,
            'reservations_budget_enabled' => true,
            'reservations_subsidy_enabled' => false,
            'reservations_auto_accept' => false,
            'reservation_phone' => "no",
            'reservation_address' => "no",
            'reservation_birth_date' => "no",
            'manage_provider_products' => false,
            'backoffice_available' => false,
            'allow_custom_fund_notifications' => false,
            'allow_provider_extra_payments' => false,
            'allow_pre_checks' => false,
            'allow_bi_connection' => false,
            'allow_2fa_restrictions' => false,
            'allow_batch_reservations' => false,
            'allow_budget_fund_limits' => false,
            'allow_fund_request_record_edit' => false,
            'pre_approve_external_funds' => false,
            'provider_throttling_value' => 100,
            'fund_request_resolve_policy' => "apply_manually",
            'bsn_enabled' => false,
            'bank_cron_time' => "09:00:00",
            'show_provider_transactions' => false,
        ]
    ],
    'Fijnder (Berkelland, Oostgelre and Winterswijk combined)' => [
        'offices_count' => 0,
        'organization' => [
            'website_public' => false,
            'business_type_id' => 1532,
            'is_sponsor' => true,
            'is_provider' => true,
            'is_validator' => true,
            'validator_auto_accept_funds' => false,
            'reservations_budget_enabled' => true,
            'reservations_subsidy_enabled' => false,
            'reservations_auto_accept' => false,
            'reservation_phone' => "no",
            'reservation_address' => "no",
            'reservation_birth_date' => "no",
            'manage_provider_products' => false,
            'backoffice_available' => false,
            'allow_custom_fund_notifications' => true,
            'allow_provider_extra_payments' => false,
            'allow_pre_checks' => false,
            'allow_bi_connection' => false,
            'allow_2fa_restrictions' => false,
            'allow_batch_reservations' => false,
            'allow_budget_fund_limits' => false,
            'allow_fund_request_record_edit' => false,
            'pre_approve_external_funds' => false,
            'provider_throttling_value' => 100,
            'fund_request_resolve_policy' => "apply_manually",
            'bsn_enabled' => false,
            'bank_cron_time' => "09:00:00",
            'show_provider_transactions' => false,
        ]
    ],
    'Gemeente Heumen' => [
        'offices_count' => 0,
        'organization' => [
            'website_public' => true,
            'business_type_id' => 585,
            'is_sponsor' => true,
            'is_provider' => true,
            'is_validator' => true,
            'validator_auto_accept_funds' => false,
            'reservations_budget_enabled' => true,
            'reservations_subsidy_enabled' => false,
            'reservations_auto_accept' => false,
            'reservation_phone' => "no",
            'reservation_address' => "no",
            'reservation_birth_date' => "no",
            'manage_provider_products' => true,
            'backoffice_available' => false,
            'allow_custom_fund_notifications' => false,
            'allow_provider_extra_payments' => false,
            'allow_pre_checks' => false,
            'allow_bi_connection' => false,
            'allow_2fa_restrictions' => false,
            'allow_batch_reservations' => false,
            'allow_budget_fund_limits' => false,
            'allow_fund_request_record_edit' => false,
            'pre_approve_external_funds' => true,
            'provider_throttling_value' => 100,
            'fund_request_resolve_policy' => "apply_manually",
            'bsn_enabled' => false,
            'bank_cron_time' => "09:00:00",
            'show_provider_transactions' => false,
        ]
    ],
    'Gemeente Waalwijk' => [
        'offices_count' => 0,
        'organization' => [
            'website_public' => true,
            'business_type_id' => 585,
            'is_sponsor' => true,
            'is_provider' => true,
            'is_validator' => true,
            'validator_auto_accept_funds' => false,
            'reservations_budget_enabled' => true,
            'reservations_subsidy_enabled' => false,
            'reservations_auto_accept' => false,
            'reservation_phone' => "no",
            'reservation_address' => "no",
            'reservation_birth_date' => "no",
            'manage_provider_products' => true,
            'backoffice_available' => false,
            'allow_custom_fund_notifications' => false,
            'allow_provider_extra_payments' => false,
            'allow_pre_checks' => false,
            'allow_bi_connection' => true,
            'allow_2fa_restrictions' => false,
            'allow_batch_reservations' => false,
            'allow_budget_fund_limits' => false,
            'allow_fund_request_record_edit' => true,
            'pre_approve_external_funds' => false,
            'provider_throttling_value' => 100,
            'fund_request_resolve_policy' => "apply_auto_available",
            'bsn_enabled' => true,
            'bank_cron_time' => "09:00:00",
            'show_provider_transactions' => true,
        ]
    ],
    'Gemeente Geertruidenberg' => [
        'offices_count' => 0,
        'organization' => [
            'website_public' => true,
            'business_type_id' => 585,
            'is_sponsor' => true,
            'is_provider' => true,
            'is_validator' => true,
            'validator_auto_accept_funds' => false,
            'reservations_budget_enabled' => true,
            'reservations_subsidy_enabled' => false,
            'reservations_auto_accept' => false,
            'reservation_phone' => "no",
            'reservation_address' => "no",
            'reservation_birth_date' => "no",
            'manage_provider_products' => true,
            'backoffice_available' => false,
            'allow_custom_fund_notifications' => false,
            'allow_provider_extra_payments' => false,
            'allow_pre_checks' => false,
            'allow_bi_connection' => false,
            'allow_2fa_restrictions' => true,
            'allow_batch_reservations' => false,
            'allow_budget_fund_limits' => false,
            'allow_fund_request_record_edit' => false,
            'pre_approve_external_funds' => false,
            'provider_throttling_value' => 100,
            'fund_request_resolve_policy' => "apply_auto_available",
            'bsn_enabled' => true,
            'bank_cron_time' => "09:00:00",
            'show_provider_transactions' => false,
        ]
    ],
    'Gemeente Eemsdelta' => [
        'offices_count' => 0,
        'organization' => [
            'website_public' => true,
            'business_type_id' => 585,
            'is_sponsor' => true,
            'is_provider' => true,
            'is_validator' => true,
            'validator_auto_accept_funds' => false,
            'reservations_budget_enabled' => true,
            'reservations_subsidy_enabled' => false,
            'reservations_auto_accept' => false,
            'reservation_phone' => "no",
            'reservation_address' => "no",
            'reservation_birth_date' => "no",
            'manage_provider_products' => true,
            'backoffice_available' => false,
            'allow_custom_fund_notifications' => true,
            'allow_provider_extra_payments' => false,
            'allow_pre_checks' => false,
            'allow_bi_connection' => false,
            'allow_2fa_restrictions' => false,
            'allow_batch_reservations' => false,
            'allow_budget_fund_limits' => false,
            'allow_fund_request_record_edit' => true,
            'pre_approve_external_funds' => false,
            'provider_throttling_value' => 100,
            'fund_request_resolve_policy' => "apply_auto_available",
            'bsn_enabled' => true,
            'bank_cron_time' => "09:00:00",
            'show_provider_transactions' => true,
        ]
    ],
    'Gemeente Schagen' => [
        'offices_count' => 0,
        'organization' => [
            'website_public' => true,
            'business_type_id' => 585,
            'is_sponsor' => true,
            'is_provider' => true,
            'is_validator' => true,
            'validator_auto_accept_funds' => false,
            'reservations_budget_enabled' => true,
            'reservations_subsidy_enabled' => false,
            'reservations_auto_accept' => false,
            'reservation_phone' => "no",
            'reservation_address' => "no",
            'reservation_birth_date' => "no",
            'manage_provider_products' => false,
            'backoffice_available' => false,
            'allow_custom_fund_notifications' => false,
            'allow_provider_extra_payments' => false,
            'allow_pre_checks' => false,
            'allow_bi_connection' => false,
            'allow_2fa_restrictions' => false,
            'allow_batch_reservations' => false,
            'allow_budget_fund_limits' => false,
            'allow_fund_request_record_edit' => false,
            'pre_approve_external_funds' => false,
            'provider_throttling_value' => 100,
            'fund_request_resolve_policy' => "apply_manually",
            'bsn_enabled' => true,
            'bank_cron_time' => "09:00:00",
            'show_provider_transactions' => false,
        ]
    ],
    'Gemeente Goeree-Overflakkee' => [
        'offices_count' => 0,
        'organization' => [
            'website_public' => true,
            'business_type_id' => 585,
            'is_sponsor' => true,
            'is_provider' => true,
            'is_validator' => true,
            'validator_auto_accept_funds' => false,
            'reservations_budget_enabled' => true,
            'reservations_subsidy_enabled' => false,
            'reservations_auto_accept' => false,
            'reservation_phone' => "no",
            'reservation_address' => "no",
            'reservation_birth_date' => "no",
            'manage_provider_products' => false,
            'backoffice_available' => false,
            'allow_custom_fund_notifications' => true,
            'allow_provider_extra_payments' => false,
            'allow_pre_checks' => false,
            'allow_bi_connection' => false,
            'allow_2fa_restrictions' => false,
            'allow_batch_reservations' => false,
            'allow_budget_fund_limits' => false,
            'allow_fund_request_record_edit' => false,
            'pre_approve_external_funds' => false,
            'provider_throttling_value' => 100,
            'fund_request_resolve_policy' => "apply_manually",
            'bsn_enabled' => false,
            'bank_cron_time' => "09:00:00",
            'show_provider_transactions' => false,
        ]
    ],
    'Etten-Leur - Werkplein Hart van West-Brabant' => [
        'offices_count' => 0,
        'organization' => [
            'website_public' => false,
            'business_type_id' => 585,
            'is_sponsor' => true,
            'is_provider' => true,
            'is_validator' => true,
            'validator_auto_accept_funds' => false,
            'reservations_budget_enabled' => true,
            'reservations_subsidy_enabled' => false,
            'reservations_auto_accept' => false,
            'reservation_phone' => "no",
            'reservation_address' => "no",
            'reservation_birth_date' => "no",
            'manage_provider_products' => true,
            'backoffice_available' => false,
            'allow_custom_fund_notifications' => true,
            'allow_provider_extra_payments' => true,
            'allow_pre_checks' => false,
            'allow_bi_connection' => true,
            'allow_2fa_restrictions' => true,
            'allow_batch_reservations' => false,
            'allow_budget_fund_limits' => false,
            'allow_fund_request_record_edit' => false,
            'pre_approve_external_funds' => false,
            'provider_throttling_value' => 100,
            'fund_request_resolve_policy' => "apply_manually",
            'bsn_enabled' => false,
            'bank_cron_time' => "09:00:00",
            'show_provider_transactions' => false,
        ]
    ],
    'Halderberge - Werkplein Hart van West-Brabant' => [
        'offices_count' => 0,
        'organization' => [
            'website_public' => false,
            'business_type_id' => 585,
            'is_sponsor' => true,
            'is_provider' => true,
            'is_validator' => true,
            'validator_auto_accept_funds' => false,
            'reservations_budget_enabled' => true,
            'reservations_subsidy_enabled' => false,
            'reservations_auto_accept' => false,
            'reservation_phone' => "no",
            'reservation_address' => "no",
            'reservation_birth_date' => "no",
            'manage_provider_products' => true,
            'backoffice_available' => false,
            'allow_custom_fund_notifications' => true,
            'allow_provider_extra_payments' => true,
            'allow_pre_checks' => false,
            'allow_bi_connection' => true,
            'allow_2fa_restrictions' => true,
            'allow_batch_reservations' => false,
            'allow_budget_fund_limits' => false,
            'allow_fund_request_record_edit' => false,
            'pre_approve_external_funds' => false,
            'provider_throttling_value' => 100,
            'fund_request_resolve_policy' => "apply_manually",
            'bsn_enabled' => false,
            'bank_cron_time' => "09:00:00",
            'show_provider_transactions' => false,
        ]
    ],
    'Moerdijk - Werkplein Hart van West-Brabant' => [
        'offices_count' => 0,
        'organization' => [
            'website_public' => false,
            'business_type_id' => 585,
            'is_sponsor' => true,
            'is_provider' => true,
            'is_validator' => true,
            'validator_auto_accept_funds' => false,
            'reservations_budget_enabled' => true,
            'reservations_subsidy_enabled' => false,
            'reservations_auto_accept' => false,
            'reservation_phone' => "no",
            'reservation_address' => "no",
            'reservation_birth_date' => "no",
            'manage_provider_products' => true,
            'backoffice_available' => false,
            'allow_custom_fund_notifications' => true,
            'allow_provider_extra_payments' => true,
            'allow_pre_checks' => false,
            'allow_bi_connection' => true,
            'allow_2fa_restrictions' => true,
            'allow_batch_reservations' => false,
            'allow_budget_fund_limits' => false,
            'allow_fund_request_record_edit' => false,
            'pre_approve_external_funds' => false,
            'provider_throttling_value' => 100,
            'fund_request_resolve_policy' => "apply_manually",
            'bsn_enabled' => false,
            'bank_cron_time' => "09:00:00",
            'show_provider_transactions' => false,
        ]
    ],
    'Zundert - Werkplein Hart van West-Brabant' => [
        'offices_count' => 0,
        'organization' => [
            'website_public' => false,
            'business_type_id' => 585,
            'is_sponsor' => true,
            'is_provider' => true,
            'is_validator' => true,
            'validator_auto_accept_funds' => false,
            'reservations_budget_enabled' => true,
            'reservations_subsidy_enabled' => false,
            'reservations_auto_accept' => false,
            'reservation_phone' => "no",
            'reservation_address' => "no",
            'reservation_birth_date' => "no",
            'manage_provider_products' => true,
            'backoffice_available' => false,
            'allow_custom_fund_notifications' => true,
            'allow_provider_extra_payments' => true,
            'allow_pre_checks' => false,
            'allow_bi_connection' => true,
            'allow_2fa_restrictions' => true,
            'allow_batch_reservations' => false,
            'allow_budget_fund_limits' => false,
            'allow_fund_request_record_edit' => false,
            'pre_approve_external_funds' => false,
            'provider_throttling_value' => 100,
            'fund_request_resolve_policy' => "apply_manually",
            'bsn_enabled' => false,
            'bank_cron_time' => "09:00:00",
            'show_provider_transactions' => false,
        ]
    ],
];
