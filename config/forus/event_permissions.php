<?php

return [
    'bank_connection' => [
        'permissions' => [
            'manage_bank_connections'
        ],
        'events' => [
            'disabled',
            'monetary_account_changed',
            'replaced',
            'activated',
//            'created',
//            'rejected',
//            'disabled_invalid',
        ],
    ],

    'voucher' => [
        'permissions' => [
            'manage_vouchers'
        ],
        'events' => [
            'created_budget',
            'created_product',
            'expired',
            'assigned',
            'activated',
            'deactivated',
            'transaction',
            'transaction_product',
            'transaction_subsidy',
            'physical_card_requested',
//            'shared_by_email',
//            'shared',
//            'expiring_soon_budget',
//            'expiring_soon_product',
        ],
    ],

    'employee' => [
        'permissions' => [
            'manage_employees'
        ],
        'events' => [
            'created',
            'updated',
            'deleted',
        ],
    ],

    'fund' => [
        'permissions' => [
            'view_funds'
        ],
        'events' => [
            'vouchers_export',
//            'created',
//            'provider_applied',
//            'provider_replied',
//            'provider_approved_products',
//            'provider_approved_budget',
//            'provider_revoked_products',
//            'provider_revoked_budget',
//            'balance_low',
//            'balance_supplied',
//            'fund_started',
//            'fund_ended',
//            'fund_product_added',
//            'fund_product_approved',
//            'fund_product_revoked',
//            'fund_product_subsidy_removed',
//            'fund_expiring',
//            'archived',
//            'unarchived',
//            'balance_updated_by_bank_connection',
        ],
    ],
];