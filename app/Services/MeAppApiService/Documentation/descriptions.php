<?php

use Illuminate\Support\Arr;

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
 * Prefix a field with anyOf[]. to describe it in every matching branch. Use named variants for different meanings.
 * Put example payloads in examples.php.
 */

$paginationResponse = static fn (array $fields = []): array => [
    200 => [
        'fields' => [
            'meta' => 'Pagination details. Request the next page when `current_page` is less than `last_page`.',
            'meta.current_page' => 'Current page number, starting at 1.',
            'meta.last_page' => 'Last page number, or 1 if there are no results.',
            'meta.from' => 'Position of the first item on this page, starting at 1, or `null` if the page is empty.',
            ...$fields,
        ],
    ],
];

$creationFields = [
    'created_at' => 'Creation date and time in `YYYY-MM-DD HH:mm:ss` format, or `null` if unavailable.',
    'created_at_locale' => 'Creation date and time formatted for display, or `null` if unavailable.',
];

$transactionFields = [
    'id' => 'Transaction ID.',
    'organization_id' => 'ID of the provider linked to the transaction, or `null` if none is linked.',
    'product_id' => 'ID of the product paid for, or `null` if none is linked.',
    'state' => 'Transaction status: `pending`, `success`, or `canceled`.',
    'amount' => 'Transaction amount as a decimal string.',
    'amount_locale' => 'Transaction amount formatted for display.',
    'amount_extra_cash' => 'Additional cash amount recorded for the transaction, as a decimal string.',
    'amount_extra_cash_locale' => 'Additional cash amount formatted for display.',
    ...$creationFields,
];

$mediaFields = [
    'sizes' => 'Image URLs grouped by size. Each URL is `null` if that size is unavailable.',
    'sizes.thumbnail' => 'URL for the thumbnail image.',
    'sizes.small' => 'URL for the small image.',
    'sizes.large' => 'URL for the large image.',
];

$organizationFields = [
    'id' => 'Organization ID.',
    'name' => 'Organization name.',
    'email' => 'Contact email address, or `null` if unavailable.',
    'phone' => 'Contact phone number, or `null` if unavailable.',
    'logo' => 'Organization logo, or `null` if unavailable.',
];

$allowedOrganizationFields = [
    'id' => 'Provider organization ID.',
    'name' => 'Provider name.',
    'logo' => 'Provider logo, or `null` if unavailable.',
    ...Arr::prependKeysWith($mediaFields, 'logo.'),
];

$providerVoucherFields = Arr::prependKeysWith([
    'fund_id' => 'ID of the fund that issued the voucher.',
    'type' => 'Voucher type: `regular` for a budget voucher or `product` for a product voucher.',
    'address' => 'Voucher token address used for provider requests.',
    'fund' => 'Fund that issued the voucher.',
    'fund.id' => 'Fund ID.',
    'fund.name' => 'Fund name.',
    'fund.state' => 'Current fund status.',
    'fund.type' => 'Fund type, such as `budget` or `subsidies`.',
    'fund.organization' => 'Sponsor organization responsible for the fund.',
    ...Arr::prependKeysWith($organizationFields, 'fund.organization.'),
    'fund.logo' => 'Fund logo, or `null` if unavailable.',
    ...Arr::prependKeysWith($mediaFields, 'fund.logo.'),
    'allowed_organizations' => 'Organizations for which you can accept this voucher directly.',
    ...Arr::prependKeysWith($allowedOrganizationFields, 'allowed_organizations[].'),
    'allowed_product_organizations' => 'Organizations for which you can sell a product or redeem a linked product voucher.',
    ...Arr::prependKeysWith($allowedOrganizationFields, 'allowed_product_organizations[].'),
    'amount' => 'Available voucher balance as a decimal string, or `null` without direct payment permission.',
    'amount_locale' => 'Available balance formatted for display, or `null` without direct payment permission.',
    'amount_visible' => 'Whether the fund allows the provider to display the balance, or `null` if unavailable.',
    'product' => 'Product this voucher can redeem.',
    ...$creationFields,
], 'anyOf[].');

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
                'responses' => $paginationResponse(),
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
        'schemas' => [
            'IdentityResource' => [
                'description' => 'The identity of the signed-in user.',
                'fields' => [
                    'address' => 'Unique identifier for this identity.',
                    'email' => 'Primary email address, or `null` if the identity has no primary email.',
                ],
            ],
            'MediaSmallResource' => [
                'description' => 'URLs for the available sizes of a photo or logo.',
                'fields' => $mediaFields,
            ],
            'OrganizationResource' => [
                'description' => 'The name, contact details, and logo of a sponsor or provider.',
                'fields' => $organizationFields,
            ],
            'ProductCategoryResource' => [
                'description' => 'A category in the product catalog. Categories can be grouped under a parent category.',
                'fields' => [
                    'id' => 'Product category ID.',
                    'key' => 'Language-independent identifier for the category.',
                    'name' => 'Localized category name for display, or `null` if unavailable.',
                    'parent_id' => 'ID of the immediate parent category, or `null` for a root category.',
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
                'responses' => $paginationResponse(),
                'parameters' => [
                    'query' => [
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
            'v1.products.index' => [
                'description' => implode(' ', [
                    'Use `meta.price_max` as the upper bound for the price filter.',
                    'It is the highest minimum product price across all pages before the `from` and `to` filters.',
                ]),
                'responses' => $paginationResponse([
                    'meta.price_max' => implode(' ', [
                        'Highest minimum price among matching products before the `from` and `to` filters.',
                        'Returned as a decimal string in euros, or `"0.00"` if no products match.',
                    ]),
                ]),
                'parameters' => [
                    'query' => [
                        'product_category_ids[]' => 'Match selected categories and their descendants.',
                        'fund_ids[]' => 'Filter products by selected funds.',
                        'organization_id' => 'Filter by provider organization ID.',
                        'from' => 'Minimum product price in euros, inclusive. Accepts up to two decimal places.',
                        'to' => 'Maximum product price in euros, inclusive. Accepts up to two decimal places.',
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
            'v1.providers.index' => [
                'responses' => $paginationResponse(),
            ],
            'v1.funds.index' => [
                'responses' => $paginationResponse(),
                'parameters' => [
                    'query' => [
                        'has_providers' => 'Use 1 for funds with approved providers or 0 for funds without them.',
                    ],
                ],
            ],
        ],
        'schemas' => [
            'BusinessTypeResource' => [
                'description' => 'The type of business the provider operates.',
                'fields' => [
                    'id' => 'Business type ID.',
                    'key' => 'Language-independent identifier for the business type.',
                    'name' => 'Localized business type name for display, or `null` if unavailable.',
                ],
            ],
            'FundResource' => [
                'description' => 'A fund available in the product catalog’s fund filter.',
                'fields' => [
                    'id' => 'Fund ID to pass in `fund_ids[]` when filtering products.',
                    'name' => 'Fund name to display in the filter.',
                ],
            ],
            'OfficeScheduleResource' => [
                'description' => 'Opening hours for an office on one day of the week.',
                'fields' => [
                    'id' => 'Schedule entry ID.',
                    'office_id' => 'ID of the office these hours apply to.',
                    'week_day' => 'Day of the week, numbered from `0` (Monday) to `6` (Sunday).',
                    'start_time' => 'Opening time in 24-hour `HH:mm` format, or an empty string if unset.',
                    'end_time' => 'Closing time in 24-hour `HH:mm` format, or an empty string if unset.',
                ],
            ],
            'ProductResource' => [
                'description' => implode(' ', [
                    'Use `user_price` to display the product’s catalog price',
                    'and `funds[].user_price` to display the price for a specific fund.',
                    'The provider can enter up to 200 characters of free text in each optional `info_*` field.',
                ]),
                'fields' => [
                    'id' => 'Product ID.',
                    'name' => 'Product name.',
                    'organization_id' => 'ID of the provider offering this product.',
                    'sold_out' => 'Whether the product is sold out.',
                    'bookmarked' => implode(' ', [
                        'Whether the signed-in requester has bookmarked this product.',
                        'Always `false` for guests.',
                    ]),
                    'alternative_text' => 'A product can have multiple photos, but has only one `alternative_text` value.',
                    'description_html' => 'Product description formatted as HTML.',
                    'info_duration' => 'How long the offer lasts.',
                    'info_when' => 'When the offer can be used.',
                    'info_where' => 'Where the offer can be used.',
                    'info_more_info' => 'Additional information about the offer.',
                    'info_attention' => 'Details the provider wants the requester to pay special attention to.',
                    'expire_at' => implode(' ', [
                        'Product expiry date in `YYYY-MM-DD` format.',
                        'An individual fund may have an earlier deadline.',
                    ]),
                    'expire_at_locale' => 'Product expiry date formatted for display.',
                    'user_price' => 'Catalog price.',
                    'photos' => 'Product photos. The product’s `alternative_text` applies to the collection.',
                    'product_category' => 'Category assigned to the product, or `null` if none is assigned.',
                    'funds' => 'Funds listed for this product, with the price, quantity limit, and deadline for each fund.',
                    'offices' => implode(' ', [
                        'Offices of the provider offering the product.',
                        'The list does not indicate availability at each office.',
                    ]),
                    'organization' => 'The provider offering the product.',
                    'feature_scanning_enabled' => implode(' ', [
                        'True if at least one listed fund supports payment for this product',
                        'by scanning a voucher QR code.',
                    ]),
                    'feature_reservations_enabled' =>
                        'True if at least one listed fund supports reservations for this product.',
                    'feature_reservation_extra_payments_enabled' => implode(' ', [
                        'True if at least one listed fund lets the requester pay the difference',
                        'when reserving this product with an insufficient voucher balance.',
                    ]),
                ],
            ],
            'ProductFundResource' => [
                'description' => implode(' ', [
                    'A fund the requester can use for this product, including the price with that fund,',
                    'the quantity limit, the deadline, and the available payment options.',
                ]),
                'fields' => [
                    'id' => 'Fund ID.',
                    'name' => 'Fund name.',
                    'logo' => 'Fund logo, or `null` if the fund has no logo.',
                    'user_price' => 'Price with this fund.',
                    'feature_scanning_enabled' => implode(' ', [
                        'Whether this fund supports payment for this product',
                        'by scanning a voucher QR code.',
                    ]),
                    'feature_reservations_enabled' => 'Whether reservations are enabled for this product.',
                    'feature_reservation_extra_payments_enabled' => implode(' ', [
                        'Whether the requester can pay the difference when reserving this product',
                        'with an insufficient voucher balance from this fund.',
                    ]),
                    'end_at' => implode(' ', [
                        'Fund end date in `YYYY-MM-DD` format.',
                        'The product deadline (`expire_at`) can be earlier.',
                    ]),
                    'end_at_locale' => 'Fund end date formatted for display.',
                    'expire_at' => implode(' ', [
                        'The deadline for this product under this fund.',
                        'It is the earliest of the fund end date, the product expiry date,',
                        'and any applicable voucher deadline.',
                    ]),
                    'expire_at_locale' => 'Localized expiry date to display.',
                    'limit' => implode(' ', [
                        'Quantity limit to display for this product and fund.',
                        'Hide when `null`. Show zero when `0`.',
                    ]),
                ],
            ],
            'ProductOfficeResource' => [
                'description' => 'An office of the provider offering the product.',
                'fields' => [
                    'id' => 'Office ID.',
                    'organization_id' => 'ID of the provider this office belongs to.',
                    'address' => 'Street address of the office.',
                    'phone' => 'Office phone number, or `null` if unavailable.',
                    'lat' => 'Latitude of the office, or `null` if unavailable.',
                    'lon' => 'Longitude of the office, or `null` if unavailable.',
                    'photo' => 'Office photo, or `null` if unavailable.',
                ],
            ],
            'ProductUserPriceResource' => [
                'description' => implode(' ', [
                    'At product level, `user_price` shows the catalog price.',
                    'Within `funds[]`, it shows the price with that fund.',
                    'Display `value` as provided. It already includes the appropriate formatting or localized text.',
                ]),
                'fields' => [
                    'type' => 'The product’s price type.',
                    'value' => implode(' ', [
                        'Localized price label to display as provided.',
                        'It can show an amount, a discount, or text such as “Free”.',
                    ]),
                ],
            ],
            'ProviderResource' => [
                'description' => 'The provider offering a product, including its business type and public contact details.',
                'fields' => [
                    'id' => 'Provider organization ID.',
                    'name' => 'Provider name.',
                    'email' => 'Provider’s public email address, or `null` if unavailable.',
                    'phone' => 'Provider’s public phone number, or `null` if unavailable.',
                    'logo' => 'Provider logo, or `null` if unavailable.',
                    'business_type' => 'Type of business the provider operates, or `null` if none is assigned.',
                ],
            ],
            'VoucherFundResource' => [
                'description' => 'The fund that issued the voucher, including its sponsor and webshop link.',
                'fields' => [
                    'id' => 'Fund ID.',
                    'name' => 'Fund name to display.',
                    'type' => 'Fund type, such as `budget` or `subsidies`.',
                    'url_webshop' => 'URL of the webshop associated with the fund, or `null` if unavailable.',
                    'logo' => 'Fund logo, or `null` if unavailable.',
                    'organization' => 'Sponsor organization responsible for the fund.',
                ],
            ],
            'VoucherOfficeResource' => [
                'description' => implode(' ', [
                    'A provider office listed for the voucher,',
                    'with its address, contact details, and opening hours.',
                ]),
                'fields' => [
                    'id' => 'Office ID.',
                    'organization_id' => 'ID of the provider this office belongs to.',
                    'address' => 'Street address of the office.',
                    'phone' => 'Office phone number, or `null` if unavailable.',
                    'lon' => 'Longitude of the office, or `null` if unavailable.',
                    'lat' => 'Latitude of the office, or `null` if unavailable.',
                    'postcode' => 'Full postal code, such as `6811 AB`, or `null` if unavailable.',
                    'postcode_number' => 'First part of the postal code, such as `6811`, or `null` if unavailable.',
                    'postcode_addition' => 'Second part of the postal code, such as `AB`, or `null` if unavailable.',
                    'photo' => 'Office photo, or `null` if unavailable.',
                    'organization' => 'The provider this office belongs to.',
                    'schedule' => 'Weekly opening hours. An empty list means no schedule entries are available.',
                ],
            ],
            'VoucherProductResource' => [
                'description' => 'The product linked to a product voucher, including its price, photos, and provider.',
                'fields' => [
                    'id' => 'Product ID.',
                    'name' => 'Product name to display.',
                    'description' => 'Product description.',
                    'price' => 'Base product price as a decimal string.',
                    'price_locale' => implode(' ', [
                        'Localized price label. Display as provided.',
                        'It can show an amount, a discount, or text such as “Gratis”.',
                    ]),
                    'organization_id' => 'ID of the provider offering the product.',
                    'photos' => 'Product photos. An empty list means no photos are available.',
                    'organization' => 'The provider offering the product.',
                ],
            ],
            'VoucherProductVoucherResource' => [
                'description' => implode(' ', [
                    'A product voucher linked to the parent voucher,',
                    'with its amount, product details, and creation date.',
                ]),
                'fields' => [
                    'id' => 'Product voucher ID.',
                    'amount' => 'Amount of the product voucher as a decimal string.',
                    'amount_locale' => 'Voucher amount formatted for display.',
                    'product' => 'The product this voucher is for.',
                    'product.id' => 'Product ID.',
                    'product.name' => 'Product name to display.',
                    'product.organization_id' => 'ID of the provider offering the product.',
                    'created_at' => implode(' ', [
                        'Date and time the product voucher was created,',
                        'in `YYYY-MM-DD HH:mm:ss` format, or `null` if unavailable.',
                    ]),
                    'created_at_locale' => 'Creation date and time formatted for display, or `null` if unavailable.',
                ],
            ],
            'VoucherResource' => [
                'description' => implode(' ', [
                    'A requester’s voucher. A budget voucher (`regular`) has a balance to spend.',
                    'A product voucher (`product`) is tied to a specific product.',
                ]),
                'fields' => [
                    'id' => 'Voucher ID.',
                    'expired' => 'True once either the voucher expiry date or the fund end date has passed.',
                    'deactivated' => 'True if the voucher has been deactivated and can no longer be used for payments.',
                    'external' => 'True if the fund uses an external voucher system.',
                    'address' => 'Voucher identifier encoded in the QR code that a provider scans.',
                    'type' => '`regular` for a budget voucher, or `product` for a voucher tied to a specific product.',
                    'used' => implode(' ', [
                        'For a `regular` voucher, true when its available balance is zero.',
                        'For a `product` voucher, true when it has at least one transaction.',
                    ]),
                    'amount' => implode(' ', [
                        'Available balance for a budget voucher, or the voucher amount for a product voucher,',
                        'as a decimal string. `null` when the sponsor hides the amount.',
                    ]),
                    'amount_locale' => 'The same amount formatted for display, or `null` when the sponsor hides it.',
                    'product' => 'The product this voucher is for. `null` for a budget voucher.',
                    'offices' => implode(' ', [
                        'For a budget voucher, offices of providers approved for the fund.',
                        'For a product voucher, offices of the provider offering the product.',
                    ]),
                    'transactions' => 'Transaction history for this voucher.',
                    'fund' => 'The fund that issued the voucher.',
                    'product_vouchers' => implode(' ', [
                        'Product vouchers linked to this voucher as children.',
                        'An empty list means there are none.',
                    ]),
                    'last_active_day' => 'Calendar date of the voucher’s expiry, in `YYYY-MM-DD` format.',
                    'last_active_day_locale' => 'Voucher expiry date formatted for display, without the time.',
                    'last_transaction_at' => implode(' ', [
                        'Date the most recent outgoing voucher transaction was created.',
                        '`null` if there are no outgoing transactions.',
                    ]),
                    'last_transaction_at_locale' => implode(' ', [
                        'Date of the most recent outgoing voucher transaction, formatted for display.',
                        '`null` if there are no outgoing transactions.',
                    ]),
                    'expire_at' => implode(' ', [
                        'Voucher expiry date in `YYYY-MM-DD 00:00:00` format.',
                        'Use `expired` to check whether the voucher has expired.',
                    ]),
                    'expire_at_locale' => 'Voucher expiry date and time formatted for display.',
                ],
            ],
            'VoucherTransactionResource' => [
                'description' => implode(' ', [
                    'A transaction in the voucher’s history, with its amount, status,',
                    'and any linked provider, product, or reimbursement.',
                ]),
                'fields' => [
                    'id' => 'Transaction ID.',
                    'address' => 'Unique address identifying the transaction.',
                    'organization_id' => 'ID of the provider linked to the transaction, or `null` if none is linked.',
                    'product_id' => 'ID of the product linked to the transaction, or `null` if none is linked.',
                    'state' => 'Transaction status: `pending`, `success`, or `canceled`.',
                    'state_locale' => 'Transaction status formatted for display.',
                    'target' => 'Transaction target: `provider`, `top_up`, `payout`, or `iban`.',
                    'amount' => 'Transaction amount as a decimal string.',
                    'amount_locale' => 'Transaction amount formatted for display.',
                    'amount_extra_cash' => 'Additional cash amount recorded for the transaction, as a decimal string.',
                    'amount_extra_cash_locale' => 'Additional cash amount formatted for display.',
                    'created_at' => implode(' ', [
                        'Date and time the transaction was created,',
                        'in `YYYY-MM-DD HH:mm:ss` format, or `null` if unavailable.',
                    ]),
                    'created_at_locale' => 'Creation date and time formatted for display, or `null` if unavailable.',
                    'transfer_at' => implode(' ', [
                        'Scheduled transfer date and time in `YYYY-MM-DD HH:mm:ss` format,',
                        'or `null` if unset.',
                    ]),
                    'transfer_at_locale' => 'Scheduled transfer date and time formatted for display, or `null` if unset.',
                    'organization' => 'Provider linked to the transaction, or `null` if none is linked.',
                    'organization.id' => 'Provider organization ID.',
                    'organization.name' => 'Provider name.',
                    'organization.logo' => 'Provider logo, or `null` if unavailable.',
                    'product' => 'Product linked to the transaction, or `null` if none is linked.',
                    'product.id' => 'Product ID.',
                    'product.name' => 'Product name to display.',
                    'product.organization_id' => 'ID of the provider offering the product.',
                    'product.product_category_id' => 'ID of the product’s category.',
                    'product.description' => 'Product description.',
                    'product.price' => 'Base product price as a decimal string, or `null` if unset.',
                    'product.organization' => 'Provider offering the product.',
                    'product.organization.id' => 'Product provider’s organization ID.',
                    'product.organization.name' => 'Product provider’s name.',
                    'product.photo' => 'First product photo, or `null` if unavailable.',
                    'fund' => 'Fund that issued the voucher.',
                    'fund.id' => 'Fund ID.',
                    'fund.name' => 'Fund name.',
                    'reimbursement' => 'Reimbursement linked to the transaction, or `null` if none is linked.',
                    'reimbursement.id' => 'Reimbursement ID.',
                    'reimbursement.code' => 'Reimbursement reference code.',
                    'reimbursement.title' => 'Reimbursement title.',
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
                'responses' => $paginationResponse(),
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
                'responses' => $paginationResponse(),
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
                'responses' => $paginationResponse(),
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
            'DemoTransactionResource' => [
                'description' => 'The result of a demo payment, including its status and creation time.',
                'fields' => [
                    'id' => 'Demo transaction ID.',
                    'state' => 'Demo payment status: `pending`, `accepted`, or `rejected`.',
                    ...$creationFields,
                ],
            ],
            'ProviderProductResource' => [
                'description' => implode(' ', [
                    'A product available for the voucher. The prices show the requester’s share and the sponsor’s share.',
                    'Products with a fund-specific offer also include the sponsor and product category.',
                ]),
                'fields' => Arr::prependKeysWith([
                    'id' => 'Product ID.',
                    'name' => 'Product name.',
                    'description' => 'Product description.',
                    'product_category_id' => 'ID of the product category.',
                    'organization_id' => 'ID of the provider offering the product.',
                    'price' => 'Base product price as a decimal string, or `null` if unavailable.',
                    'price_locale' => 'Base product price label formatted for display.',
                    'price_user' => 'Requester’s share of the price as a decimal string.',
                    'price_user_locale' => 'Requester’s price label formatted for display.',
                    'sponsor_subsidy' => 'Sponsor’s contribution as a decimal string, or `null` if unavailable.',
                    'sponsor_subsidy_locale' => 'Sponsor’s contribution label formatted for display.',
                    'photos' => 'Product photos.',
                    'organization' => 'Provider offering the product.',
                    'sponsor' => 'Organization sponsoring the fund-specific offer.',
                    'price_type' => 'Product price type.',
                    'price_discount' => 'Discount amount or percentage according to the price type, if applicable.',
                    'product_category' => 'Category assigned to the product.',
                    'product_category.id' => 'Product category ID.',
                    'product_category.key' => 'Language-independent category identifier.',
                    'product_category.name' => 'Localized category name, or `null` if unavailable.',
                ], 'anyOf[].'),
            ],
            'ProviderVoucherResource' => [
                'description' => 'A redeemable voucher in the provider’s product voucher list, with its fund and product.',
                'fields' => $providerVoucherFields,
            ],
            'ProviderVoucherTokenResource' => [
                'description' => implode(' ', [
                    'Voucher details after a provider scans a code. A budget voucher includes the organizations',
                    'that can accept it. A product voucher also identifies the product to redeem.',
                ]),
                'fields' => $providerVoucherFields,
            ],
            'ProviderVoucherTransactionEmployeeResource' => [
                'description' => implode(' ', [
                    'An entry in the provider’s transaction history, with the fund, provider,',
                    'any linked product, and the provider’s note.',
                ]),
                'fields' => [
                    ...$transactionFields,
                    'note' => 'Provider’s note on the transaction, or `null` if unavailable.',
                    'organization' => 'Provider that accepted the payment.',
                    'organization.id' => 'Provider organization ID.',
                    'organization.name' => 'Provider name.',
                    'organization.logo' => 'Provider logo, or `null` if unavailable.',
                    'product' => 'Product paid for, or `null` if none is linked.',
                    'product.id' => 'Product ID.',
                    'product.name' => 'Product name.',
                    'product.organization_id' => 'ID of the provider offering the product.',
                    'product.photos' => 'Product photos.',
                    'fund' => 'Fund that issued the voucher.',
                    'fund.id' => 'Fund ID.',
                    'fund.name' => 'Fund name.',
                    'fund.organization_id' => 'ID of the fund sponsor.',
                    'fund.organization' => 'Sponsor organization responsible for the fund.',
                    'fund.organization.id' => 'Sponsor organization ID.',
                    'fund.organization.name' => 'Sponsor organization name.',
                ],
            ],
            'VoucherTransactionResource' => [
                'description' => implode(' ', [
                    'A newly recorded voucher payment, with its status, voucher amount,',
                    'and any additional cash amount.',
                ]),
                'fields' => [
                    ...$transactionFields,
                    'state_locale' => 'Transaction status formatted for display.',
                    'target' => 'Transaction target: `provider`, `top_up`, `payout`, or `iban`.',
                ],
            ],
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
