<?php

return [
    'Zuidhorn' => [
        'offices_count' => 0,
        'organization' => [
            'allow_bi_connection' => true,
            'allow_2fa_restrictions' => true,
            'allow_custom_fund_notifications' => true,
        ]
    ],
    'Nijmegen' => [
        'offices_count' => 0,
        'organization' => [
            'backoffice_available' => true,
            'allow_bi_connection' => true,
            'allow_2fa_restrictions' => true,
            'allow_budget_fund_limits' => true,
            'allow_manual_bulk_processing' => true,
            'allow_provider_extra_payments' => true,
            'allow_fund_request_record_edit' => true,
            'allow_custom_fund_notifications' => true,
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
            'validator_auto_accept_funds' => false,
            'reservations_budget_enabled' => true,
            'reservations_subsidy_enabled' => true,
            'reservations_auto_accept' => false,
            'reservation_phone' => "no",
            'reservation_address' => "no",
            'reservation_birth_date' => "no",
            'manage_provider_products' => true,
            'backoffice_available' => false,
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
];