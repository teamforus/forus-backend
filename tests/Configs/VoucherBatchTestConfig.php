<?php

namespace Tests\Configs;

class VoucherBatchTestConfig
{
    /** @var array|array[]  */
    public static array $featureTestCases = [[
        'fund_id' => 1,
        // global count to create, can be overridden in asserts
        'vouchers_count' => 10,
        // type can be budget or product
        'type' => 'budget',
        // set bsn enabled config for organization
        'bsn_enabled' => true,
        // payments config for fund
        'allow_direct_payments' => false,
        'allow_generator_direct_payments' => false,

        "asserts" => [[
            // assign type (email, bsn, client_uid)
            'assign_by' => 'email',
            // assert creation (success or fail)
            'assert_creation' => 'fail',
            // activate or not vouchers
            'activate' => false,
            // assert activation (success or fail)
            'assert_activation' => 'fail',
            // if true then add payment fields to batch
            'with_transaction' => false,
            // use existing identity data (for bsn, email) or not
            'existing_identity' => true,
            // exclude fields from voucher array (after can be validated for missing fields)
            'except_fields' => [],
            // if true - amount will be over limit for current fund (if budget voucher)
            'amount_over_limit' => false,
            // provide some validation errors, will be asserted if "assert_creation" is "fail"
            'validation_errors' => [
                'vouchers.0.activate',
                'vouchers.0.activation_code',
                'vouchers.0.limit_multiplier',
                'vouchers.0.expire_at',
                'vouchers.0.note',
                'vouchers.0.email',
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
            'assign_by' => 'bsn',
            'assert_creation' => 'success',
            'activate' => false,
            'assert_activation' => 'fail',
            'with_transaction' => false,
            'existing_identity' => true,
        ], [
            'assign_by' => 'email',
            'assert_creation' => 'success',
            'activate' => true,
            'assert_activation' => 'success',
            'with_transaction' => false,
        ], [
            'vouchers_count' => 10,
            'assign_by' => 'client_uid',
            'assert_creation' => 'success',
            'activate' => true,
            'activation_code' => 3,
            'same_assign_by' => 4,
            'assert_activation' => 'success',
            'with_transaction' => false,
        ]]
    ], [
        'fund_id' => 1,
        'vouchers_count' => 3,
        'type' => 'product',
        'bsn_enabled' => true,

        "asserts" => [[
            'assign_by' => 'email',
            'assert_creation' => 'success',
            'with_transaction' => false,
        ], [
            'assign_by' => 'bsn',
            'assert_creation' => 'success',
            'activate' => false,
            'assert_activation' => 'fail',
            'with_transaction' => false,
        ], [
            'assign_by' => 'email',
            'assert_creation' => 'success',
            'activate' => true,
            'assert_activation' => 'success',
            'with_transaction' => false,
        ]]
    ], [
        'fund_id' => 2,
        'vouchers_count' => 10,
        'type' => 'budget',
        'bsn_enabled' => true,
        'allow_direct_payments' => true,
        'allow_generator_direct_payments' => true,

        "asserts" => [[
            'assign_by' => 'email',
            'assert_creation' => 'success',
            'amount_over_limit' => false,
            'with_transaction' => true,
        ], [
            'assign_by' => 'bsn',
            'assert_creation' => 'success',
            'amount_over_limit' => false,
            'activate' => true,
            'assert_activation' => 'success',
            'with_transaction' => true,
        ]]
    ], [
        'fund_id' => 2,
        'vouchers_count' => 2,
        'type' => 'budget',
        'bsn_enabled' => false,
        'allow_direct_payments' => true,
        'allow_generator_direct_payments' => true,

        "asserts" => [[
            'assign_by' => 'email',
            'assert_creation' => 'success',
            'amount_over_limit' => false,
            'with_transaction' => true,
        ], [
            'assign_by' => 'email',
            'assert_creation' => 'fail',
            'amount_over_limit' => true,
            'validation_errors' => [
                'vouchers.0.amount',
                'vouchers.1.amount',
            ],
        ], [
            'assign_by' => 'bsn',
            'assert_creation' => 'fail',
            'validation_errors' => [
                'vouchers.0.bsn',
                'vouchers.1.bsn',
            ],
        ]]
    ], [
        'fund_id' => 2,
        'vouchers_count' => 3,
        'type' => 'product',
        'bsn_enabled' => true,

        "asserts" => [[
            'assign_by' => 'email',
            // product type - can be assigned (assign), empty_stock and not_assigned
            'product' => 'assigned',
            'assert_creation' => 'success',
            'with_transaction' => false,
        ], [
            'assign_by' => 'bsn',
            'product' => 'assigned',
            'assert_creation' => 'success',
            'activate' => false,
            'assert_activation' => 'fail',
            'with_transaction' => false,
        ], [
            'assign_by' => 'email',
            'product' => 'assigned',
            'assert_creation' => 'success',
            'activate' => true,
            'assert_activation' => 'success',
            'with_transaction' => false,
        ], [
            'assign_by' => 'client_uid',
            'product' => 'assigned',
            'assert_creation' => 'success',
            'with_transaction' => false,
        ], [
            'assign_by' => 'client_uid',
            'product' => 'empty_stock',
            'assert_creation' => 'fail',
            'validation_errors' => [
                'vouchers.0.product_id',
                'vouchers.1.product_id',
                'vouchers.2.product_id',
            ],
        ], [
            'assign_by' => 'client_uid',
            'product' => 'not_assigned',
            'assert_creation' => 'fail',
            'validation_errors' => [
                'vouchers.0.product_id',
                'vouchers.1.product_id',
                'vouchers.2.product_id',
            ],
        ]]
    ], [
        'fund_id' => 6,
        'vouchers_count' => 10,
        'type' => 'budget',
        'bsn_enabled' => true,

        "asserts" => [[
            'assign_by' => 'email',
            'same_assign_by' => 5,
            'assert_creation' => 'success',
            'with_transaction' => false,
        ], [
            'assign_by' => 'bsn',
            'assert_creation' => 'success',
            'activate' => false,
            'assert_activation' => 'fail',
            'with_transaction' => false,
        ], [
            'assign_by' => 'email',
            'assert_creation' => 'success',
            'activate' => true,
            'assert_activation' => 'success',
            'with_transaction' => false,
        ], [
            'assign_by' => 'client_uid',
            'activation_code' => 5,
            'assert_creation' => 'success',
            'with_transaction' => false,
        ], [
            'vouchers_count' => 10,
            'assign_by' => 'client_uid',
            'assert_creation' => 'success',
            'activate' => true,
            'activation_code' => 3,
            'same_assign_by' => 4,
            'assert_activation' => 'success',
            'with_transaction' => false,
        ]]
    ], [
        'fund_id' => 4,
        'vouchers_count' => 3,
        'type' => 'budget',
        'bsn_enabled' => true,
        'allow_direct_payments' => true,
        'allow_generator_direct_payments' => true,

        "asserts" => [[
            'assign_by' => 'email',
            'assert_creation' => 'success',
            'with_transaction' => false,
        ], [
            'assign_by' => 'bsn',
            'assert_creation' => 'success',
            'activate' => false,
            'assert_activation' => 'fail',
            'with_transaction' => false,
        ], [
            'assign_by' => 'email',
            'assert_creation' => 'success',
            'activate' => true,
            'assert_activation' => 'success',
            'with_transaction' => false,
        ], [
            'assign_by' => 'client_uid',
            'with_transaction' => true,
            'assert_creation' => 'fail',
            'except_fields' => [
                'amount',
                'direct_payment_name',
            ],
            'validation_errors' => [
                'vouchers.0.amount',
                'vouchers.0.direct_payment_name',
                'vouchers.1.amount',
                'vouchers.1.direct_payment_name',
                'vouchers.2.amount',
                'vouchers.2.direct_payment_name',
            ],
        ]]
    ]];

    /** @var array|array[]  */
    public static array $browserTestCases = [[
        'vouchers_count' => 10,
        'assign_by' => 'email',
        'same_code_for_fund' => false,
    ], [
        'vouchers_count' => 4,
        'assign_by' => 'bsn',
        'same_code_for_fund' => false,
    ], [
        'vouchers_count' => 5,
        'assign_by' => 'client_uid',
        'same_code_for_fund' => false,
    ], [
        'vouchers_count' => 5,
        'assign_by' => 'client_uid',
        'same_code_for_fund' => true,
    ]];
}
