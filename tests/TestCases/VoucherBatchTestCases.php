<?php

namespace Tests\TestCases;

class VoucherBatchTestCases
{
    /** @var array|array[] */
    public static array $featureTestCase1 = [
        'fund_id' => 1,
        'organization' => [
            'bsn_enabled' => true,
        ],
        'fund_config' => [
            'allow_direct_payments' => false,
            'allow_generator_direct_payments' => false,
        ],
        "asserts" => [[
            'type' => 'budget',
            'activate' => false,
            // assign type (email, bsn, client_uid)
            'assign_by' => 'email',
            'assert_active' => false,
            'assert_created' => false,
            // if true then add payment fields to batch
            'direct_payment' => false,
            // use existing identity data (for bsn, email)
            'existing_identity' => true,
            // remove fields from the voucher (to generate errors for assertion)
            'except_fields' => [],
            // if true - amount will be over limit for current fund (if budget voucher)
            'exceed_voucher_amount_limit' => false,
            // asserted validation error (used when assert_created is false)
            'assert_errors' => [
                'vouchers.0.activate',
                'vouchers.0.activation_code',
                'vouchers.0.limit_multiplier',
                'vouchers.0.expire_at',
                'vouchers.0.note',
                'vouchers.0.email',
            ],
            // overwrite fields from vouchers
            'replacement' => [
                'activate' => 'string',
                'activation_code' => ['array'],
                'limit_multiplier' => -10,
                'expire_at' => '01-01-2030',
                'note' => ['array'],
                'email' => 'invalidemail',
            ],
        ], [
            'type' => 'budget',
            'activate' => false,
            'assign_by' => 'bsn',
            'assert_created' => true,
            'assert_active' => false,
            'existing_identity' => true,
        ], [
            'type' => 'budget',
            'activate' => true,
            'assign_by' => 'email',
            'assert_created' => true,
            'assert_active' => true,
        ], [
            'type' => 'budget',
            'activate' => true,
            'assign_by' => 'client_uid',
            'assert_created' => true,
            'activation_code' => 3,
            'same_assign_by' => 4,
            'assert_active' => true,
        ]]
    ];

    public static array $featureTestCase2 = [
        'fund_id' => 1,
        'organization' => [
            'bsn_enabled' => true,
        ],
        'fund_config' => [
            'allow_direct_payments' => false,
            'allow_generator_direct_payments' => false,
        ],
        "asserts" => [[
            'type' => 'product',
            'assign_by' => 'email',
            'assert_created' => true,
        ], [
            'type' => 'product',
            'activate' => false,
            'assign_by' => 'bsn',
            'assert_active' => false,
            'assert_created' => true,
        ], [
            'type' => 'product',
            'activate' => true,
            'assign_by' => 'email',
            'assert_created' => true,
            'assert_active' => true,
        ]]
    ];

    public static array $featureTestCase3 = [
        'fund_id' => 2,
        'organization' => [
            'bsn_enabled' => true,
        ],
        'fund_config' => [
            'allow_direct_payments' => true,
            'allow_generator_direct_payments' => true,
        ],
        "asserts" => [[
            'type' => 'budget',
            'assign_by' => 'email',
            'assert_created' => true,
            'direct_payment' => true,
        ], [
            'type' => 'budget',
            'assign_by' => 'bsn',
            'assert_created' => true,
            'activate' => true,
            'assert_active' => true,
            'direct_payment' => true,
        ]]
    ];

    public static array $featureTestCase4 = [
        'fund_id' => 2,
        'organization' => [
            'bsn_enabled' => false,
        ],
        'fund_config' => [
            'allow_direct_payments' => true,
            'allow_generator_direct_payments' => true,
        ],
        "asserts" => [[
            'type' => 'budget',
            'assign_by' => 'email',
            'direct_payment' => true,
            'assert_created' => true,
            
        ], [
            'type' => 'budget',
            'assign_by' => 'email',
            'assert_created' => false,
            'exceed_voucher_amount_limit' => true,
            'assert_errors' => [
                'vouchers.0.amount',
                'vouchers.1.amount',
            ],
        ], [
            'type' => 'budget',
            'assign_by' => 'bsn',
            'assert_created' => false,
            'assert_errors' => [
                'vouchers.0.bsn',
                'vouchers.1.bsn',
            ],
        ]]
    ];

    public static array $featureTestCase5 = [
        'fund_id' => 2,
        'type' => 'product',
        'organization' => [
            'bsn_enabled' => true,
        ],
        "asserts" => [[
            'type' => 'budget',
            'assign_by' => 'email',
            'product' => 'approved',
            'assert_created' => true,
        ], [
            'type' => 'budget',
            'assign_by' => 'bsn',
            'product' => 'approved',
            'assert_created' => true,
            'activate' => false,
            'assert_active' => false,
        ], [
            'type' => 'budget',
            'product' => 'approved',
            'activate' => true,
            'assign_by' => 'email',
            'assert_created' => true,
            'assert_active' => true,
        ], [
            'type' => 'budget',
            'product' => 'approved',
            'assign_by' => 'client_uid',
            'assert_created' => true,
        ], [
            'type' => 'product',
            'product' => 'empty_stock',
            'assign_by' => 'client_uid',
            'assert_created' => false,
            'assert_errors' => [
                'vouchers.0.product_id',
                'vouchers.1.product_id',
                'vouchers.2.product_id',
            ],
        ], [
            'type' => 'product',
            'product' => 'unapproved',
            'assign_by' => 'client_uid',
            'assert_created' => false,
            'assert_errors' => [
                'vouchers.0.product_id',
                'vouchers.1.product_id',
                'vouchers.2.product_id',
            ],
        ]]
    ];

    public static array $featureTestCase6 = [
        'fund_id' => 6,
        'organization' => [
            'bsn_enabled' => true,
        ],
        "asserts" => [[
            'type' => 'budget',
            'assign_by' => 'email',
            'same_assign_by' => 5,
            'assert_created' => true,
        ], [
            'type' => 'budget',
            'assign_by' => 'bsn',
            'assert_created' => true,
            'activate' => false,
            'assert_active' => false,
        ], [
            'type' => 'budget',
            'assign_by' => 'email',
            'assert_created' => true,
            'activate' => true,
            'assert_active' => true,
        ], [
            'type' => 'budget',
            'assign_by' => 'client_uid',
            'activation_code' => 5,
            'assert_created' => true,
        ], [
            'type' => 'budget',
            'activate' => true,
            'assign_by' => 'client_uid',
            'assert_active' => true,
            'assert_created' => true,
            'same_assign_by' => 4,
            'activation_code' => 3,
        ]]
    ];

    public static array $featureTestCase7 = [
        'fund_id' => 5,
        'organization' => [
            'bsn_enabled' => true,
        ],
        'fund_config' => [
            'allow_direct_payments' => true,
            'allow_generator_direct_payments' => true,
        ],
        "asserts" => [[
            'type' => 'budget',
            'assign_by' => 'email',
            'assert_created' => true,
        ], [
            'type' => 'budget',
            'activate' => false,
            'assign_by' => 'bsn',
            'assert_active' => false,
            'assert_created' => true,
        ], [
            'type' => 'budget',
            'assign_by' => 'email',
            'activate' => true,
            'assert_active' => true,
            'assert_created' => true,
        ], [
            'type' => 'budget',
            'assign_by' => 'client_uid',
            'direct_payment' => true,
            'assert_created' => false,
            'except_fields' => [
                'amount',
                'direct_payment_name',
            ],
            'assert_errors' => [
                'vouchers.0.amount',
                'vouchers.0.direct_payment_name',
                'vouchers.1.amount',
                'vouchers.1.direct_payment_name',
                'vouchers.2.amount',
                'vouchers.2.direct_payment_name',
            ],
        ]]
    ];

    /** @var array|array[] */
    public static array $browserTestCase1 = [
        'assign_by' => 'email',
        'vouchers_count' => 5,
        'same_code_for_fund' => false,
    ];

    public static array $browserTestCase2 = [
        'assign_by' => 'bsn',
        'vouchers_count' => 2,
        'same_code_for_fund' => false,
    ];

    public static array $browserTestCase3 = [
        'assign_by' => 'client_uid',
        'vouchers_count' => 2,
        'same_code_for_fund' => false,
    ];

    public static array $browserTestCase4 = [
        'assign_by' => 'client_uid',
        'vouchers_count' => 2,
        'same_code_for_fund' => true,
    ];
}
