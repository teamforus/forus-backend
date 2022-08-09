<?php

return [
    'bank_connection' => [
        'permissions' => [
            'manage_bank_connections'
        ],
        'events' => array_merge([
            'disabled',
            'monetary_account_changed',
            'replaced',
            'disabled_invalid',
            'activated',

            // 'created',
            // 'rejected',
        ]),
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
            'expiring_soon_budget',
            'expiring_soon_product',

            // 'shared',
            // 'shared_by_email',
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
            'created',
            'vouchers_exported',
            'balance_low',
            'balance_supplied',
            'fund_started',
            'fund_ended',
            'fund_expiring',
            'archived',
            'unarchived',
            'balance_updated_by_bank_connection',

            // 'provider_applied',
            // 'provider_replied',
            // 'provider_approved_products',
            // 'provider_approved_budget',
            // 'provider_revoked_products',
            // 'provider_revoked_budget',
            // 'fund_product_added',
            // 'fund_product_approved',
            // 'fund_product_revoked',
            // 'fund_product_subsidy_removed',
        ],
    ],
];