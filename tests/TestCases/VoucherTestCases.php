<?php

namespace Tests\TestCases;

class VoucherTestCases
{
    /** @var array|array[] */
    public static array $featureTestCase1 = [
        'fund_id' => 1,
        'organization' => [
            'bsn_enabled' => true,
        ],
        'fund_config' => [],
        "asserts" => [[
            'type' => 'budget',
            // assign type (email, bsn, client_uid)
            'assign_by' => 'email',
            'assert_created' => false,
            'activate' => false,
            'assert_active' => false,
            // use existing identity data (for bsn, email) or not
            'existing_identity' => true,
            // exclude fields from voucher array (after can be validated for missing fields)
            'except_fields' => [],
            // if true - amount will be over limit for current fund (if budget voucher)
            'exceed_voucher_amount_limit' => false,
            // provide some validation errors, will be asserted if "assert_created" is "fail"
            'assert_errors' => [
                'activate',
                'activation_code',
                'limit_multiplier',
                'expire_at',
                'note',
                'email',
            ],
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
            'assert_created' => true,
            'activate' => true,
            'activation_code' => 1,
            'assert_active' => true,
            'sponsor_assign_existing_identity' => true,
            'sponsor_assign_by' => 'bsn',
        ], [
            'type' => 'budget',
            'assign_by' => 'client_uid',
            'assert_created' => true,
            'activate' => true,
            'activation_code' => 1,
            'assert_active' => true,
            'sponsor_assign_existing_identity' => true,
            'sponsor_assign_by' => 'email',
        ], [
            'type' => 'budget',
            'assign_by' => 'client_uid',
            'assert_created' => true,
            'activate' => true,
            'activation_code' => 1,
            'assert_active' => true,
            'sponsor_assign_existing_identity' => false,
            'sponsor_assign_by' => 'bsn',
        ], [
            'type' => 'budget',
            'assign_by' => 'client_uid',
            'assert_created' => true,
            'activate' => true,
            'activation_code' => 1,
            'assert_active' => true,
            'sponsor_assign_existing_identity' => false,
            'sponsor_assign_by' => 'email',
        ]]
    ];


    /** @var array|array[]  */
    public static array $featureTestCase2 = [
        'fund_id' => 1,
        'organization' => [
            'bsn_enabled' => true,
        ],
        'fund_config' => [],

        "asserts" => [[
            'type' => 'product',
            'assign_by' => 'email',
            'assert_created' => true,
        ], [
            'type' => 'product',
            'assign_by' => 'bsn',
            'assert_created' => true,
            'activate' => false,
            'assert_active' => false,
        ], [
            'type' => 'product',
            'assign_by' => 'email',
            'assert_created' => true,
            'activate' => true,
            'assert_active' => true,
        ]]
    ];

    /** @var array|array[]  */
    public static array $featureTestCase3 = [
        'fund_id' => 2,
        'organization' => [
            'bsn_enabled' => true,
        ],
        'fund_config' => [],

        "asserts" => [[
            'type' => 'budget',
            'assign_by' => 'email',
            'assert_created' => true,
            'exceed_voucher_amount_limit' => false,
        ], [
            'type' => 'budget',
            'assign_by' => 'bsn',
            'assert_created' => true,
            'exceed_voucher_amount_limit' => false,
            'activate' => true,
            'assert_active' => true,
        ]]
    ];

    /** @var array|array[]  */
    public static array $featureTestCase4 = [
        'fund_id' => 2,
        'organization' => [
            'bsn_enabled' => false,
        ],
        'fund_config' => [],

        "asserts" => [[
            'type' => 'budget',
            'assign_by' => 'email',
            'assert_created' => true,
            'exceed_voucher_amount_limit' => false,
        ], [
            'type' => 'budget',
            'assign_by' => 'email',
            'assert_created' => false,
            'exceed_voucher_amount_limit' => true,
            'assert_errors' => [
                'amount',
            ],
        ], [
            'type' => 'budget',
            'assign_by' => 'bsn',
            'assert_created' => false,
            'assert_errors' => [
                'bsn',
                'assign_by_type'
            ],
        ]]
    ];

    /** @var array|array[]  */
    public static array $featureTestCase5 = [
        'fund_id' => 2,
        'organization' => [
            'bsn_enabled' => true,
        ],
        'fund_config' => [],

        "asserts" => [[
            'type' => 'product',
            'assign_by' => 'email',
            'product' => 'approved',
            'assert_created' => true,
        ], [
            'type' => 'product',
            'assign_by' => 'bsn',
            'product' => 'approved',
            'assert_created' => true,
            'activate' => false,
            'assert_active' => false,
        ], [
            'type' => 'product',
            'assign_by' => 'email',
            'product' => 'approved',
            'assert_created' => true,
            'activate' => true,
            'assert_active' => true,
        ], [
            'type' => 'product',
            'assign_by' => 'client_uid',
            'product' => 'approved',
            'assert_created' => true,
        ], [
            'type' => 'product',
            'assign_by' => 'client_uid',
            'product' => 'empty_stock',
            'assert_created' => false,
            'assert_errors' => [
                'product_id',
            ],
        ], [
            'type' => 'product',
            'assign_by' => 'client_uid',
            'product' => 'unapproved',
            'assert_created' => false,
            'assert_errors' => [
                'product_id',
            ],
        ]]
    ];

    /** @var array|array[]  */
    public static array $featureTestCase6 = [
        'fund_id' => 6,
        'organization' => [
            'bsn_enabled' => true,
        ],
        'fund_config' => [],

        "asserts" => [[
            'type' => 'budget',
            'assign_by' => 'email',
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
            'activation_code' => 1,
            'assert_created' => true,
        ], [
            'type' => 'budget',
            'assign_by' => 'client_uid',
            'assert_created' => true,
            'activate' => true,
            'activation_code' => 1,
            'assert_active' => true,
        ]]
    ];

    /** @var array|array[]  */
    public static array $featureTestCase7 = [
        'fund_id' => 4,
        'organization' => [
            'bsn_enabled' => true,
        ],
        'fund_config' => [],

        "asserts" => [[
            'type' => 'budget',
            'assign_by' => 'email',
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
            'assert_created' => false,
            'except_fields' => [
                'amount',
            ],
            'assert_errors' => [
                'amount',
            ],
        ]]
    ];
}
