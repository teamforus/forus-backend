<?php

use App\Services\MeAppApiService\Http\Resources\MobileAnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

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
    'Budget amount' => [
        'amount' => 2.5,
        'organization_id' => 1,
        'note' => 'Example payment',
    ],
    'Budget product' => [
        'product_id' => 1,
        'organization_id' => 1,
        'note' => 'Example payment',
    ],
    'Product voucher' => (object) [],
];

$mediaUrl = 'https://api.example.org/storage/media';

$regularPrice = [
    'type' => 'regular',
    'value' => '€ 10,99',
];

$subsidizedPrice = [
    'type' => 'regular',
    'value' => '€ 5,-',
];

$regularFund = [
    'id' => 1,
    'name' => 'Mobility Fund',
    'logo' => null,
    'limit' => null,
    'user_price' => $regularPrice,
    'feature_scanning_enabled' => true,
    'feature_reservations_enabled' => false,
    'feature_reservation_extra_payments_enabled' => false,
    'end_at' => '2027-05-31',
    'end_at_locale' => '31 mei, 2027',
    'expire_at' => '2027-05-31',
    'expire_at_locale' => '31 mei, 2027',
];

$subsidizedFund = [
    ...$regularFund,
    'id' => 2,
    'name' => 'Bike Subsidy Fund',
    'user_price' => $subsidizedPrice,
];

$regularProduct = [
    'id' => 10,
    'name' => 'City bike day rental',
    'sold_out' => false,
    'organization_id' => 3,
    'alternative_text' => 'Blue city bike',
    'description_html' => '<p>City bike rental for one day.</p>',
    'info_duration' => 'One day',
    'info_when' => null,
    'info_where' => null,
    'info_more_info' => null,
    'info_attention' => null,
    'feature_scanning_enabled' => true,
    'feature_reservations_enabled' => false,
    'feature_reservation_extra_payments_enabled' => false,
    'bookmarked' => false,
    'user_price' => $regularPrice,
    'photos' => [
        [
            'sizes' => [
                'thumbnail' => $mediaUrl . '/6cfc5a1be2d7493084a9d0b30782f1ce73f419620c5a8e44e4e553789312ac08.jpg',
                'small' => $mediaUrl . '/a4b79e180fcb6231b93615fe5803ac92223bd10c8ab5c073e19f4542c60d84ef.jpg',
                'large' => $mediaUrl . '/93d42bf2e8c901a6c5b8a18f307abc924de17f08a46b3d5962c4e119be523fd7.jpg',
            ],
        ],
        [
            'sizes' => [
                'thumbnail' => $mediaUrl . '/b16a4f8c2e970315fd201369c34a87752f61ce097e15ad683b40ac5d9812eb9a.jpg',
                'small' => $mediaUrl . '/82f10e5b7da921c364b01d32f9ac8457af988b1c18e635d20920bd8e75b19c43.jpg',
                'large' => $mediaUrl . '/3c78d19bf2e45c06a559819dee76c8ca81d4ef679654bf21a705573243dc0a19.jpg',
            ],
        ],
    ],
    'product_category' => null,
    'funds' => [$regularFund],
    'offices' => [],
    'organization' => [
        'id' => 3,
        'name' => 'City Bike Rentals',
        'email' => null,
        'phone' => null,
        'logo' => null,
        'business_type' => null,
    ],
    'expire_at' => '2027-05-31',
    'expire_at_locale' => '31 mei, 2027',
];

$voucherFund = [
    'id' => 1,
    'name' => 'Mobility Fund',
    'type' => 'budget',
    'url_webshop' => 'https://webshop.example.org',
    'logo' => null,
    'organization' => [
        'id' => 8,
        'name' => 'Mobility Foundation',
        'email' => 'info@example.org',
        'phone' => null,
        'logo' => null,
    ],
];

$voucherProduct = [
    'id' => 10,
    'name' => 'City bike day rental',
    'description' => 'City bike rental for one day.',
    'price' => '10.00',
    'price_locale' => '€ 10,-',
    'organization_id' => 3,
    'photos' => [],
    'organization' => [
        'id' => 3,
        'name' => 'City Bike Rentals',
        'email' => 'info@example.org',
        'phone' => null,
        'logo' => null,
    ],
];

$childVoucher = [
    'id' => 42,
    'amount' => '10.00',
    'amount_locale' => '€ 10,-',
    'product' => [
        'id' => 10,
        'name' => 'City bike day rental',
        'organization_id' => 3,
    ],
    'created_at' => '2027-05-12 09:30:00',
    'created_at_locale' => '09:30 - 12 mei, 2027',
];

$budgetVoucher = [
    'id' => 41,
    'expired' => false,
    'deactivated' => false,
    'external' => false,
    'address' => '0x8e31c681fe034192bdf63418012434215736cd21',
    'type' => 'regular',
    'used' => false,
    'amount' => '25.00',
    'amount_locale' => '€ 25,-',
    'product' => null,
    'offices' => [],
    'transactions' => [],
    'fund' => $voucherFund,
    'product_vouchers' => [$childVoucher],
    'last_active_day' => '2027-05-31',
    'last_active_day_locale' => '31 mei, 2027',
    'last_transaction_at' => null,
    'last_transaction_at_locale' => null,
    'expire_at' => '2027-05-31 00:00:00',
    'expire_at_locale' => '00:00 - 31 mei, 2027',
];

$voucherExamples = [
    'Budget voucher' => $budgetVoucher,
    'Product voucher' => [
        ...$budgetVoucher,
        'id' => $childVoucher['id'],
        'address' => '0x372ca85fc4f1430390eca00cc2ee298d7bdc4712',
        'type' => 'product',
        'amount' => $childVoucher['amount'],
        'amount_locale' => $childVoucher['amount_locale'],
        'product' => $voucherProduct,
        'product_vouchers' => [],
    ],
];

$providerProduct = [
    ...$voucherProduct,
    'product_category_id' => 755173,
    'price_user' => '0.00',
    'price_user_locale' => 'Gratis',
    'sponsor_subsidy' => '10.00',
    'sponsor_subsidy_locale' => '€ 10,-',
];

$providerOrganization = [
    'id' => 3,
    'name' => 'City Bike Rentals',
    'logo' => null,
];

$providerVoucherFund = [
    'id' => 1,
    'name' => 'Mobility Fund',
    'state' => 'active',
    'type' => 'budget',
    'organization' => $voucherFund['organization'],
    'logo' => null,
];

$creationTime = [
    'created_at' => '2027-05-12 09:30:00',
    'created_at_locale' => '09:30 - 12 mei, 2027',
];

$providerProductVoucher = [
    'fund_id' => 1,
    'type' => 'product',
    'address' => '0x372ca85fc4f1430390eca00cc2ee298d7bdc4712',
    'fund' => $providerVoucherFund,
    'allowed_organizations' => [$providerOrganization],
    'product' => $providerProduct,
    ...$creationTime,
];

$providerPayment = [
    'id' => 84,
    'organization_id' => 3,
    'product_id' => null,
    'state' => 'pending',
    'amount' => '10.00',
    'amount_locale' => '€ 10,-',
    'amount_extra_cash' => '2.00',
    'amount_extra_cash_locale' => '€ 2,-',
    ...$creationTime,
];

$identityExamples = [
    'Signed-in user' => [
        'address' => '0x1234567890abcdef1234567890abcdef12345678',
        'email' => 'alex@example.org',
    ],
];

$categoryExamples = [
    'Language courses' => [
        'id' => 755054,
        'key' => 'language-courses',
        'name' => 'Taalcursussen',
        'parent_id' => 755053,
    ],
];

$fundExamples = [
    'Mobility Fund' => [
        'id' => 1,
        'name' => 'Mobility Fund',
    ],
];

$productExamples = [
    'Product at regular price' => $regularProduct,
    'Product with a subsidized price' => [
        ...$regularProduct,
        'user_price' => $subsidizedPrice,
        'funds' => [$regularFund, $subsidizedFund],
    ],
];

$providerExamples = [
    'Bicycle shop' => [
        'id' => 3,
        'name' => 'City Bike Rentals',
        'email' => 'info@example.org',
        'phone' => null,
        'logo' => null,
        'business_type' => [
            'id' => 530,
            'key' => 'bicycle-shop',
            'name' => 'Fietsenwinkel',
        ],
    ],
];

$demoPaymentExamples = [
    'Accepted demo payment' => [
        'id' => 12,
        'state' => 'accepted',
        ...$creationTime,
    ],
];

$providerProductExamples = [
    'Product without a fund-specific offer' => $providerProduct,
    'Product with a fund-specific offer' => [
        'id' => 10,
        'name' => 'City bike day rental',
        'organization_id' => 3,
        'price_type' => 'regular',
        'price_discount' => null,
        'price' => '10.00',
        'price_locale' => '€ 10,-',
        'price_user' => '5.00',
        'price_user_locale' => 'Prijs: € 5,-',
        'sponsor_subsidy' => '5.00',
        'sponsor_subsidy_locale' => '€ 5,-',
        'photos' => [],
        'sponsor' => $voucherFund['organization'],
        'organization' => $voucherProduct['organization'],
        'product_category' => [
            'id' => 755173,
            'key' => 'bicycle',
            'name' => 'Fiets',
        ],
    ],
];

$redeemableVoucherExamples = [
    'Redeemable product voucher' => $providerProductVoucher,
];

$fundOfferVoucher = [
    ...$providerProductVoucher,
    'product' => $providerProductExamples['Product with a fund-specific offer'],
];

$scannedVoucherExamples = [
    'Scanned budget voucher' => [
        'fund_id' => 1,
        'type' => 'regular',
        'fund' => $providerVoucherFund,
        'address' => $budgetVoucher['address'],
        'allowed_organizations' => [$providerOrganization],
        'allowed_product_organizations' => [$providerOrganization],
        'amount' => '25.00',
        'amount_locale' => '€ 25,-',
        'amount_visible' => true,
        ...$creationTime,
    ],
    'Scanned product voucher' => $providerProductVoucher,
];

$employeePaymentExamples = [
    'Provider payment with a note' => [
        ...$providerPayment,
        'note' => 'Bike rental',
        'organization' => $providerOrganization,
        'product' => null,
        'fund' => [
            'id' => 1,
            'name' => 'Mobility Fund',
            'organization_id' => 8,
            'organization' => [
                'id' => 8,
                'name' => 'Mobility Foundation',
            ],
        ],
    ],
];

$providerPaymentExamples = [
    'Recorded provider payment' => [
        ...$providerPayment,
        'state_locale' => 'In afwachting',
        'target' => 'provider',
    ],
];

$accessToken = str_repeat('0123456789abcdef0123', 10);

$inlineResponse = static fn (array $examples, int $status = 200): array => [
    $status => [
        'application/json' => $examples,
    ],
];

$detailResponse = static function (array $examples, array $overrides = [], int $status = 200): array {
    return [
        $status => [
            'application/json' => array_map(static fn (array $row) => [
                'data' => [...$row, ...$overrides],
            ], $examples),
        ],
    ];
};

$pageResponse = static function (
    array $examples,
    int $perPage = 15,
    array $metaByExample = [],
    ?string $emptyExample = null,
): array {
    $pages = [];

    $items = array_map(static fn (array $row) => [$row], $examples);

    if ($emptyExample !== null) {
        $items[$emptyExample] = [];
    }

    foreach ($items as $name => $rows) {
        $page = new LengthAwarePaginator($rows, count($rows), $perPage, 1);
        $pages[$name] = (new MobileAnonymousResourceCollection($page, JsonResource::class))
            ->additional(['meta' => $metaByExample[$name] ?? []])
            ->response()
            ->getData(true);
    }

    return [
        200 => [
            'application/json' => $pages,
        ],
    ];
};

return [
    'shared' => [
        'endpoints' => [
            'v1.status' => [
                'responses' => $inlineResponse([
                    'API available' => [
                        'success' => true,
                    ],
                ]),
            ],
            'v1.identity.proxy.code' => [
                'responses' => $inlineResponse([
                    'PIN login request' => [
                        'access_token' => $accessToken,
                        'auth_code' => 483721,
                    ],
                ], 201),
            ],
            'v1.identity.proxy.check-token' => [
                'responses' => $inlineResponse([
                    'Pending token' => [
                        'message' => 'pending',
                    ],
                    'Active token' => [
                        'message' => 'active',
                    ],
                    'Invalid token' => [
                        'message' => 'invalid',
                    ],
                ]),
            ],
            'v1.identity.proxy.email.exchange' => [
                'responses' => $inlineResponse([
                    'Exchanged token' => [
                        'access_token' => $accessToken,
                    ],
                ]),
            ],
            'v1.identity.proxy.confirmation.exchange' => [
                'responses' => $inlineResponse([
                    'Exchanged token' => [
                        'access_token' => $accessToken,
                    ],
                ]),
            ],
            'v1.identity.show' => [
                'responses' => $detailResponse($identityExamples),
            ],
        ],
        'schemas' => [
            'IdentityResource' => [
                'examples' => $identityExamples,
            ],
            'MediaSmallResource' => [
                'examples' => [
                    'Product photo' => $regularProduct['photos'][0],
                ],
            ],
            'OrganizationResource' => [
                'examples' => [
                    'Product provider' => [
                        'id' => 3,
                        'name' => 'City Bike Rentals',
                        'email' => 'info@example.org',
                        'phone' => null,
                        'logo' => null,
                    ],
                ],
            ],
            'ProductCategoryResource' => [
                'examples' => $categoryExamples,
            ],
        ],
    ],
    'requester' => [
        'endpoints' => [
            'v1.vouchers.send-email' => [
                'responses' => $inlineResponse([
                    'Voucher emailed' => [],
                ]),
            ],
            'v1.funds.index' => [
                'responses' => $pageResponse($fundExamples),
            ],
            'v1.product-categories.index' => [
                'responses' => $pageResponse($categoryExamples),
            ],
            'v1.products.index' => [
                'responses' => $pageResponse(
                    $productExamples,
                    metaByExample: [
                        'Product at regular price' => ['price_max' => '10.99'],
                        'Product with a subsidized price' => ['price_max' => '5.00'],
                    ],
                ),
            ],
            'v1.products.show' => [
                'responses' => $detailResponse($productExamples),
            ],
            'v1.products.bookmark' => [
                'responses' => $detailResponse($productExamples, ['bookmarked' => true]),
            ],
            'v1.products.remove-bookmark' => [
                'responses' => $detailResponse($productExamples, ['bookmarked' => false]),
            ],
            'v1.providers.index' => [
                'responses' => $pageResponse($providerExamples),
            ],
            'v1.vouchers.index' => [
                'responses' => $pageResponse($voucherExamples, perPage: 1000),
            ],
            'v1.vouchers.show' => [
                'responses' => $detailResponse($voucherExamples),
            ],
        ],
        'schemas' => [
            'BusinessTypeResource' => [
                'examples' => [
                    'Bicycle shop' => [
                        'id' => 530,
                        'key' => 'bicycle-shop',
                        'name' => 'Fietsenwinkel',
                    ],
                ],
            ],
            'FundResource' => [
                'examples' => $fundExamples,
            ],
            'OfficeScheduleResource' => [
                'examples' => [
                    'Monday opening hours' => [
                        'id' => 12,
                        'office_id' => 3,
                        'week_day' => 0,
                        'start_time' => '09:00',
                        'end_time' => '17:30',
                    ],
                ],
            ],
            'ProductResource' => [
                'examples' => $productExamples,
            ],
            'ProductFundResource' => [
                'examples' => [
                    'Product with a fund subsidy' => $subsidizedFund,
                ],
            ],
            'ProductOfficeResource' => [
                'examples' => [
                    'Bike rental office' => [
                        'id' => 7,
                        'organization_id' => 3,
                        'address' => 'Stationsstraat 12, Arnhem',
                        'phone' => null,
                        'lat' => '51.9843',
                        'lon' => '5.9001',
                        'photo' => null,
                    ],
                ],
            ],
            'ProductUserPriceResource' => [
                'examples' => [
                    'Free' => [
                        'type' => 'free',
                        'value' => 'Gratis',
                    ],
                    'Regular price' => $regularPrice,
                    'Regular price with subsidy' => $subsidizedPrice,
                    'Informational' => [
                        'type' => 'informational',
                        'value' => 'Alleen in winkel',
                    ],
                    'Fixed discount' => [
                        'type' => 'discount_fixed',
                        'value' => 'Korting: € 5,-',
                    ],
                    'Percentage discount' => [
                        'type' => 'discount_percentage',
                        'value' => 'Korting: 20%',
                    ],
                ],
            ],
            'ProviderResource' => [
                'examples' => $providerExamples,
            ],
            'VoucherFundResource' => [
                'examples' => [
                    'Budget voucher fund' => $voucherFund,
                ],
            ],
            'VoucherOfficeResource' => [
                'examples' => [
                    'Bike rental office with Monday opening hours' => [
                        'id' => 7,
                        'organization_id' => 3,
                        'address' => 'Stationsstraat 12, Arnhem',
                        'phone' => null,
                        'lon' => '5.9001',
                        'lat' => '51.9843',
                        'postcode' => '6811 AB',
                        'postcode_number' => '6811',
                        'postcode_addition' => 'AB',
                        'photo' => null,
                        'organization' => [
                            'id' => 3,
                            'name' => 'City Bike Rentals',
                            'email' => 'info@example.org',
                            'phone' => null,
                            'logo' => null,
                        ],
                        'schedule' => [
                            [
                                'id' => 12,
                                'office_id' => 7,
                                'week_day' => 0,
                                'start_time' => '09:00',
                                'end_time' => '17:30',
                            ],
                        ],
                    ],
                ],
            ],
            'VoucherProductResource' => [
                'examples' => [
                    'City bike day rental' => $voucherProduct,
                ],
            ],
            'VoucherProductVoucherResource' => [
                'examples' => [
                    'City bike rental voucher' => $childVoucher,
                ],
            ],
            'VoucherResource' => [
                'examples' => $voucherExamples,
            ],
            'VoucherTransactionResource' => [
                'examples' => [
                    'Payment to a provider' => [
                        'id' => 84,
                        'organization_id' => 3,
                        'product_id' => null,
                        'address' => '0x1234567890abcdef1234567890abcdef12345678',
                        'state' => 'pending',
                        'state_locale' => 'In afwachting',
                        'target' => 'provider',
                        'amount' => '10.00',
                        'amount_locale' => '€ 10,-',
                        'amount_extra_cash' => '2.00',
                        'amount_extra_cash_locale' => '€ 2,-',
                        'organization' => [
                            'id' => 3,
                            'name' => 'City Bike Rentals',
                            'logo' => null,
                        ],
                        'product' => null,
                        'fund' => [
                            'id' => 1,
                            'name' => 'Mobility Fund',
                        ],
                        'reimbursement' => null,
                        'created_at' => '2027-05-12 09:30:00',
                        'created_at_locale' => '09:30 - 12 mei, 2027',
                        'transfer_at' => '2027-05-14 09:30:00',
                        'transfer_at_locale' => '09:30 - 14 mei, 2027',
                    ],
                ],
            ],
        ],
    ],
    'provider' => [
        'endpoints' => [
            'v1.identity.proxy.token' => [
                'responses' => $inlineResponse([
                    'QR login request' => [
                        'access_token' => $accessToken,
                        'auth_token' => str_repeat('5e6f', 16),
                    ],
                ], 201),
            ],
            'v1.identity.proxy.authorize.token' => [
                'responses' => $inlineResponse([
                    'QR login approved' => [
                        'success' => true,
                    ],
                ]),
            ],
            'v1.demo.transactions.update' => [
                'responses' => $detailResponse($demoPaymentExamples),
            ],
            'v1.product-categories.index' => [
                'responses' => $pageResponse($categoryExamples),
            ],
            'v1.transactions.index' => [
                'responses' => $pageResponse($employeePaymentExamples),
            ],
            'v1.vouchers.show' => [
                'responses' => $detailResponse([
                    ...$scannedVoucherExamples,
                    'Budget voucher with balance display disabled' => [
                        ...$scannedVoucherExamples['Scanned budget voucher'],
                        'amount_visible' => false,
                    ],
                    'Budget voucher for product redemption only' => [
                        ...$scannedVoucherExamples['Scanned budget voucher'],
                        'allowed_organizations' => [],
                        'amount' => null,
                        'amount_locale' => null,
                        'amount_visible' => null,
                    ],
                    'Product voucher with a fund-specific offer' => $fundOfferVoucher,
                ]),
            ],
            'v1.vouchers.products.index' => [
                'responses' => $pageResponse($providerProductExamples),
            ],
            'v1.vouchers.product-vouchers.index' => [
                'responses' => $pageResponse(
                    [
                        ...$redeemableVoucherExamples,
                        'Product voucher with a fund-specific offer' => $fundOfferVoucher,
                    ],
                    emptyExample: 'No redeemable product vouchers',
                ),
            ],
            'v1.vouchers.transactions.store' => [
                'request' => [
                    'application/json' => $paymentExamples,
                ],
                'responses' => $detailResponse($providerPaymentExamples, status: 201) + [
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
            'DemoTransactionResource' => [
                'examples' => $demoPaymentExamples,
            ],
            'ProviderProductResource' => [
                'examples' => $providerProductExamples,
            ],
            'ProviderVoucherResource' => [
                'examples' => $redeemableVoucherExamples,
            ],
            'ProviderVoucherTokenResource' => [
                'examples' => $scannedVoucherExamples,
            ],
            'ProviderVoucherTransactionEmployeeResource' => [
                'examples' => $employeePaymentExamples,
            ],
            'VoucherTransactionResource' => [
                'examples' => $providerPaymentExamples,
            ],
            'StoreVoucherTransactionRequest' => [
                'examples' => $paymentExamples,
                // 'variants' => [
                //     'budget_amount' => [
                //         'Budget amount' => $paymentExamples['Budget amount'],
                //     ],
                //     'budget_product' => [
                //         'Budget product' => $paymentExamples['Budget product'],
                //     ],
                //     'product_voucher' => [
                //         'Product voucher' => $paymentExamples['Product voucher'],
                //         'With note' => [
                //             'note' => 'Example redemption',
                //         ],
                //     ],
                // ],
            ],
        ],
    ],
];
