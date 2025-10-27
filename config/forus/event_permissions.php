<?php

use App\Models\Permission;

return [
    'bank_connection' => [
        'permissions' => [
            Permission::MANAGE_BANK_CONNECTIONS,
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
            Permission::MANAGE_VOUCHERS,
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
            'physical_card_requested',
            'expiring_soon_budget',
            'expiring_soon_product',
            'limit_multiplier_changed',

            // 'shared',
            // 'shared_by_email',
        ],
    ],

    'employees' => [
        'permissions' => [
            Permission::MANAGE_EMPLOYEES,
        ],
        'events' => [
            'created',
            'updated',
            'deleted',
        ],
    ],

    'fund' => [
        'permissions' => [
            Permission::VIEW_FUNDS,
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
        ],
    ],
];
