<?php

/*
 * Put documentation text under 'shared', 'requester' or 'provider'.
 * Each scope supports the following keys. The examples use provider payment and email authentication.
 *
 * 'info' => [
 *     'title' => 'Provider API',
 *     'description' => 'API for the provider app.',
 * ],
 * 'fragments' => [
 *     'token_state' => 'the login token is expired or no longer pending',
 * ],
 * 'parameters' => [
 *     'header' => [
 *         'Client-Type' => 'Client type used by the app.',
 *     ],
 * ],
 * 'responses' => [
 *     429 => [
 *         'description' => 'Too many requests.',
 *     ],
 * ],
 * 'response_headers' => [
 *     'Retry-After' => 'Seconds to wait before trying again.',
 * ],
 * 'endpoints' => [
 *     'v1.vouchers.transactions.store' => [
 *         'summary' => 'Create a voucher payment.',
 *         'description' => 'Redeem a voucher for an amount or a product.',
 *         'parameters' => [
 *             'path' => [
 *                 'voucher_address_or_physical_code' => 'Voucher address or physical card code.',
 *             ],
 *         ],
 *         'request' => [
 *             'description' => 'Choose the payment variant for the voucher.',
 *             'fields' => [
 *                 'amount' => 'Amount to charge to the budget voucher.',
 *             ],
 *         ],
 *         'responses' => [
 *             422 => [
 *                 'description' => 'Invalid payment input.',
 *                 'fields' => [
 *                     'message' => 'Reason the payment input was rejected.',
 *                 ],
 *             ],
 *         ],
 *     ],
 *     'v1.identity.store' => [
 *         'responses' => [
 *             429 => [
 *                 'headers' => [
 *                     'Retry-After' => 'Seconds to wait before requesting another email.',
 *                 ],
 *             ],
 *         ],
 *     ],
 * ],
 * 'schemas' => [
 *     'StoreVoucherTransactionRequest' => [
 *         'title' => 'Voucher payment',
 *         'description' => 'Input for a voucher payment.',
 *         'fields' => [
 *             'product_id' => 'Product selected for a budget payment.',
 *         ],
 *         'variants' => [
 *             'budget_amount' => [
 *                 'title' => 'Budget amount payment',
 *                 'description' => 'Charge an amount to a budget voucher.',
 *                 'fields' => [
 *                     'organization_id' => 'Organization accepting the payment.',
 *                 ],
 *             ],
 *         ],
 *     ],
 * ],
 *
 * All sections are optional. Consumer text overrides shared text.
 * Omit a key to keep the existing description. Use an empty string to clear it.
 * Parameter locations are 'query', 'header', 'path' and 'cookie'.
 *
 * Field paths use dots for nested properties, [] for array items and * for dictionary values.
 * If a field occurs in multiple branches, put its description under the named variant.
 * Put example payloads in examples.php.
 */

return [
    'shared' => [
        'fragments' => [
            'token_state' => 'the login token is expired or no longer pending',
        ],
        'parameters' => [
            'header' => [
                'Client-Type' => 'Identifies the app and platform.',
                'Client-Version' => 'Client version.',
                'Client-Key' => 'Implementation key for this request.',
                'Access-Token' => implode(' ', [
                    'Send the `access_token` returned when you created the PIN or QR login request.',
                    'A missing or unknown token returns `invalid`.',
                ]),
            ],
        ],
        'responses' => [
            401 => [
                'description' => 'Session is invalid, expired, or inactive, or requires two-factor confirmation.',
            ],
            403 => [
                'description' => 'Access denied. Possible causes: {causes}.',
            ],
            429 => [
                'description' => 'Too many requests.',
            ],
        ],
        'response_headers' => [
            'X-RateLimit-Limit' => 'Maximum requests allowed in the rate-limit window.',
            'X-RateLimit-Remaining' => 'Requests remaining in the rate-limit window.',
            'X-RateLimit-Reset' => 'Unix timestamp when another request is allowed.',
            'Retry-After' => 'Seconds until another request is allowed.',
        ],
        'endpoints' => [
            'v1.identity.store' => [
                'responses' => [
                    204 => [
                        'description' => 'Check your email for a sign-in or signup link.',
                    ],
                ],
            ],
            'v1.identity.proxy.code' => [
                'summary' => 'Create a PIN login request.',
            ],
            'v1.identity.proxy.check-token' => [
                'summary' => 'Check login status.',
            ],
            'v1.identity.proxy.email.redirect' => [
                'description' => 'Open this URL in a browser to continue authentication.',
                'responses' => [
                    422 => [
                        'description' => 'The link has a missing or invalid `client_type` or `implementation_key`.',
                    ],
                ],
            ],
            'v1.identity.proxy.confirmation.redirect' => [
                'description' => 'Open this URL in a browser to continue authentication.',
                'responses' => [
                    422 => [
                        'description' => 'The link has a missing or invalid `client_type` or `implementation_key`.',
                    ],
                ],
            ],
            'v1.identity.proxy.email.exchange' => [
                'responses' => [
                    404 => [
                        'description' => 'Login token not found.',
                    ],
                ],
            ],
            'v1.identity.proxy.confirmation.exchange' => [
                'responses' => [
                    404 => [
                        'description' => 'Login token not found.',
                    ],
                ],
            ],
            'v1.identity.show' => [
                'responses' => [
                    200 => [
                        'description' => 'Your identity. Email is null when unavailable.',
                    ],
                ],
            ],
            'v1.product-categories.index' => [
                'parameters' => [
                    'query' => [
                        'parent_id' => implode(' ', [
                            'Omit or leave empty for root categories,',
                            'or provide an existing category ID for children.',
                        ]),
                        'used' => implode(' ', [
                            'Use 1 for categories with available products, including in descendants,',
                            'or 0 to include unused categories.',
                        ]),
                    ],
                ],
            ],
        ],
    ],
    'requester' => [
        'info' => [
            'title' => 'Forus Wallet Mobile App API',
            'description' => 'Forus Wallet Mobile App API.',
        ],
        'endpoints' => [
            'v1.vouchers.index' => [
                'parameters' => [
                    'query' => [
                        'product_id' => implode(' ', [
                            'Include reservation details for this product in each voucher’s `query_product` field.',
                            'This parameter does not filter the list.',
                        ]),
                        'implementation_id' => 'Must reference an existing implementation when non-null.',
                        'implementation_key' => 'Must reference an existing implementation key when non-null.',
                        'archived' => implode(' ', [
                            'Use `1` for expired or inactive vouchers and `0` for active, unexpired vouchers.',
                            'Omit to include both.',
                        ]),
                        'allow_reimbursements' => implode(' ', [
                            'Use `1` to return only vouchers that allow reimbursements.',
                            'Use `0` or omit to include all eligible vouchers.',
                        ]),
                    ],
                ],
            ],
            'v1.vouchers.show' => [
                'parameters' => [
                    'query' => [
                        'product_id' => 'Include reservation details for this product in `query_product`.',
                    ],
                ],
            ],
            'v1.products.index' => [
                'parameters' => [
                    'query' => [
                        'product_category_ids[]' => 'Match selected categories and their descendants.',
                        'fund_ids[]' => 'Filter products by selected funds.',
                        'organization_id' => 'Filter by provider organization ID.',
                        'from' => 'Minimum calculated product price.',
                        'to' => 'Maximum calculated product price.',
                        'postcode' => 'Used with distance to filter by provider office location.',
                        'distance' => 'Distance in kilometres, used with postcode.',
                        'bookmarked' => 'Use 1 for your bookmarked products. Guests receive an empty list.',
                        'order_by' => 'Sort field. Defaults to created_at.',
                        'order_dir' => 'Sort direction. Defaults to desc.',
                        'page' => 'Page number. Defaults to 1.',
                        'qr' => 'Use 1 to enable this filter.',
                        'reservation' => 'Use 1 to enable this filter.',
                        'extra_payment' => 'Use 1 to enable this filter.',
                        'regular' => 'Use 1 to enable this filter.',
                        'discount_fixed' => 'Use 1 to enable this filter.',
                        'discount_percentage' => 'Use 1 to enable this filter.',
                        'free' => 'Use 1 to enable this filter.',
                        'informational' => 'Use 1 to enable this filter.',
                    ],
                ],
            ],
            'v1.funds.index' => [
                'parameters' => [
                    'query' => [
                        'has_providers' => 'Use 1 for funds with approved providers or 0 for funds without them.',
                    ],
                ],
            ],
        ],
    ],
    'provider' => [
        'info' => [
            'title' => 'Forus Provider Mobile App API',
            'description' => 'Forus Provider Mobile App API.',
        ],
        'endpoints' => [
            'v1.identity.proxy.token' => [
                'summary' => 'Create a QR login request.',
            ],
            'v1.identity.proxy.authorize.token' => [
                'summary' => 'Approve a QR login request.',
                'responses' => [
                    404 => [
                        'description' => 'Login token not found.',
                    ],
                ],
            ],
            'v1.vouchers.products.index' => [
                'parameters' => [
                    'query' => [
                        'organization_id' => implode(' ', [
                            'Optional organization ID. Requires `{scan_permission}` permission',
                            'for that organization.',
                        ]),
                        'reservable' => 'Use 1 for true and 0 for false. Omit when unused.',
                    ],
                ],
            ],
            'v1.vouchers.product-vouchers.index' => [
                'parameters' => [
                    'query' => [
                        'organization_id' => implode(' ', [
                            'Optional organization ID. Requires `{scan_permission}` permission',
                            'for that organization.',
                        ]),
                    ],
                ],
            ],
            'v1.transactions.index' => [
                'parameters' => [
                    'query' => [
                        'organization_id' => implode(' ', [
                            'Optional organization ID. Requires `{scan_permission}` permission',
                            'for that organization.',
                        ]),
                    ],
                ],
            ],
            'v1.vouchers.show' => [
                'description' => implode(' ', [
                    'Budget amount fields are null unless you can spend the voucher directly.',
                    'With Me-app Client-Version ≥ 1, product or reservation permissions can grant access',
                    'with null amount fields.',
                    'Product vouchers include product details.',
                ]),
            ],
            'v1.vouchers.transactions.store' => [
                'request' => [
                    'description' => implode(' ', [
                        'For a budget voucher, send `organization_id` and either `amount` or `product_id`.',
                        'For a product voucher, send `{}` or include a `note`.',
                    ]),
                ],
                'responses' => [
                    422 => [
                        'description' => implode(' ', [
                            'Invalid input. Sending both `amount` and `product_id`, even as null,',
                            'returns `message` only. Other validation failures also include `errors`.',
                        ]),
                    ],
                    429 => [
                        'description' => 'Too many simultaneous payment requests for this voucher. Try again.',
                    ],
                ],
            ],
        ],
        'schemas' => [
            'StoreVoucherTransactionRequest' => [
                'description' => implode(' ', [
                    'Use the variant matching the voucher in the URL.',
                    'Budget payments require `organization_id` and either `amount` or `product_id`.',
                    'For product vouchers, send only an optional `note`.',
                    'Never send both `amount` and `product_id`, even as null.',
                    'Extra fields are not generally rejected, but `product_id` also affects authorization.',
                ]),
                'variants' => [
                    'budget_amount' => [
                        'title' => 'Budget amount payment',
                        'description' => implode(' ', [
                            'For budget vouchers. Requires permission to scan the voucher',
                            'for the selected organization.',
                        ]),
                        'fields' => [
                            'organization_id' => 'An existing ID permitted for this payment.',
                            'amount_extra_cash' => implode(' ', [
                                'Optional extra cash: null, a number, or a numeric string',
                                'from 0.02 to {maximum_extra_cash}.',
                            ]),
                            'amount' => 'Number or numeric string from 0.02 up to the available voucher balance.',
                        ],
                    ],
                    'budget_product' => [
                        'title' => 'Budget product payment',
                        'description' => implode(' ', [
                            'For budget vouchers. The product must be available for the voucher',
                            'and selected organization. The server determines the payment amount.',
                        ]),
                        'fields' => [
                            'organization_id' => 'An existing ID permitted for this payment.',
                            'amount_extra_cash' => implode(' ', [
                                'Optional extra cash: null, a number, or a numeric string',
                                'from 0.02 to {maximum_extra_cash}.',
                            ]),
                            'product_id' => 'An existing ID permitted for this payment.',
                        ],
                    ],
                    'product_voucher' => [
                        'title' => 'Product voucher payment',
                        'description' => implode(' ', [
                            'For product vouchers, including reservations. Only `note` is validated.',
                            'Product and amount come from the voucher.',
                        ]),
                    ],
                ],
            ],
        ],
    ],
];
