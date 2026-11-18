<?php

/*
 * Put examples under 'shared', 'requester' or 'provider'. Each scope supports this layout:
 *
 * 'endpoints' => [
 *     'v1.vouchers.transactions.store' => [
 *         'request' => [
 *             'application/json' => [
 *                 'Budget payment' => [
 *                     'amount' => 2.5,
 *                     'organization_id' => 1,
 *                 ],
 *             ],
 *         ],
 *         'responses' => [
 *             422 => [
 *                 'application/json' => [
 *                     'Invalid input' => [
 *                         'message' => 'The given data was invalid.',
 *                     ],
 *                 ],
 *             ],
 *         ],
 *     ],
 * ],
 * 'schemas' => [
 *     'StoreVoucherTransactionRequest' => [
 *         'examples' => [
 *             'Product voucher' => (object) [],
 *         ],
 *         // These examples appear only on anyOf branches in the OpenAPI JSON export.
 *         'variants' => [
 *             'product_voucher' => [
 *                 'With note' => [
 *                     'note' => 'Example redemption',
 *                 ],
 *             ],
 *         ],
 *     ],
 * ],
 *
 * All sections are optional. Use the versioned route name without the mobile consumer prefix.
 * Choose a response status, media type, schema or named variant that exists in the generated API.
 *
 * Each example name maps directly to its payload. Use (object) [] for {} and [] for a JSON array.
 * A consumer entry replaces the shared example set for that target. An empty set clears its examples.
 */

$paymentExamples = [
    'Budget amount' => ['amount' => 2.5, 'organization_id' => 1, 'note' => 'Example payment'],
    'Budget product' => ['product_id' => 1, 'organization_id' => 1, 'note' => 'Example payment'],
    'Product voucher' => (object) [],
];

return [
    'shared' => [],
    'requester' => [],
    'provider' => [
        'endpoints' => [
            'v1.vouchers.transactions.store' => [
                'request' => [
                    'application/json' => $paymentExamples,
                ],
                'responses' => [
                    422 => [
                        'application/json' => [
                            'Invalid input' => [
                                'message' => 'The given data was invalid.',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'schemas' => [
            'StoreVoucherTransactionRequest' => [
                'examples' => $paymentExamples,
                // 'variants' => [
                //     'budget_amount' => ['Budget amount' => $paymentExamples['Budget amount']],
                //     'budget_product' => ['Budget product' => $paymentExamples['Budget product']],
                //     'product_voucher' => [
                //         'Product voucher' => $paymentExamples['Product voucher'],
                //         'With note' => ['note' => 'Example redemption'],
                //     ],
                // ],
            ],
        ],
    ],
];
