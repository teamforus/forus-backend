<?php

namespace Tests\Feature;

use App\Events\FundProviders\FundProviderApprovedBudget;
use App\Events\FundProviders\FundProviderApprovedProducts;
use App\Events\Funds\FundProviderApplied;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\FundQuery;
use App\Services\Forus\TestData\TestData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ProductStockTest extends TestCase
{
    /**
     * @var array|string[]
     */
    protected array $urls = [
        'organization' => '/api/v1/platform/organizations',
        'reservations' => '/api/v1/platform/product-reservations',
        'products' => '/api/v1/platform/products',
        'provider' => '/api/v1/platform/provider',
    ];

    /**
     * @var Product[]
     */
    protected array $products = [];

    /**
     * @var Identity[]
     */
    protected array $identities = [];

    /**
     * @var Voucher[]
     */
    protected array $vouchers = [];

    /**
     * @var array|array[]
     */
    protected array $testCases = [[
        // products to create
        "products" => [[
            // stock
            "stock" => 20,

            // funds to apply
            "funds" => [1, 2, 3],

            // fund product limits
            "limits" => [
                ["fund_id" => 1, "limit_total" => 5, "limit_per_identity" => 2],
                ["fund_id" => 2, "limit_total" => 8, "limit_per_identity" => 3],
                // limit_total and limit_per_identity can be null
                // ["fund_id" => 3, "limit_total" => null, "limit_per_identity" => null],
            ],
        ], [
            "stock" => 30,
            "funds" => [1,3],
            "limits" => [
                ["fund_id" => 1, "limit_total" => 5, "limit_per_identity" => 2],
                ["fund_id" => 3, "limit_total" => 15, "limit_per_identity" => 2],
            ],
        ]],

        // identities to create
        "identities" => [[
            // identity vouchers
            "vouchers" => [
                ["amount" => 100, "fund_id" => 1, "limit_multiplier" => 1],
                ["amount" => 200, "fund_id" => 1, "limit_multiplier" => 2],
                ["amount" => 200, "fund_id" => 2, "limit_multiplier" => 2],
                ["amount" => 500, "fund_id" => 3, "limit_multiplier" => 3],
            ],
        ]],

        // assertions
        "asserts" => [[
            "base" => [[
                // product index
                "product_index" => 0,
                // identity index or null for guest
                "identity_index" => 0,
                // assert to see
                "assert" => [[
                    "fund_id" => 1,
                    // expect to see limit_available in webshop resource (could be null)
                    "limit_available" => 5,
                    // expect to see the product in products_available list (me-app scan endpoint)
                    "products_available" => true,
                    // expect to see the product.organization_id in product_organizations list (me-app scan endpoint)
                    "product_organizations" => true,
                ], [
                    "fund_id" => 2,
                    "limit_available" => 6,
                    "products_available" => true,
                    "product_organizations" => true,
                ], [
                    "fund_id" => 3,
                    "limit_available" => null,
                    "products_available" => true,
                    "product_organizations" => true,
                ]],
            ], [
                "product_index" => 1,
                "identity_index" => 0,
                "assert" => [[
                    "fund_id" => 1,
                    "limit_available" => 5,
                    "products_available" => true,
                    "product_organizations" => true,
                ], [
                    "fund_id" => 3,
                    "limit_available" => 6,
                    "products_available" => true,
                    "product_organizations" => true,
                ]],
            ]],
            // optional actions after assertion
            "after" => [[
                "type" => "reservations",
                "actions" => [[
                    "identity_index" => 0,
                    "voucher_index" => 1,
                    "product_index" => 0,
                    "assert_created" => "success",
                    "assert_limit" => [
                        "fund_id" => 1,
                        "limit_available" => 4,
                        "products_available" => true,
                        "product_organizations" => true,
                    ]
                ], [
                    "identity_index" => 0,
                    "voucher_index" => 1,
                    "product_index" => 0,
                    "state" => "canceled_by_client",
                    "assert_created" => "success",
                    "assert_limit" => [
                        "fund_id" => 1,
                        "limit_available" => 3,
                        "products_available" => true,
                        "product_organizations" => true,
                    ],
                    "assert_limit_after_state_change" => [
                        "fund_id" => 1,
                        "limit_available" => 4,
                        "products_available" => true,
                        "product_organizations" => true,
                    ]
                ], [
                    "identity_index" => 0,
                    "voucher_index" => 1,
                    "product_index" => 0,
                    "state" => "rejected",
                    "assert_created" => "success",
                    "assert_limit" => [
                        "fund_id" => 1,
                        "limit_available" => 3,
                        "products_available" => true,
                        "product_organizations" => true,
                    ],
                    "assert_limit_after_state_change" => [
                        "fund_id" => 1,
                        "limit_available" => 4,
                        "products_available" => true,
                        "product_organizations" => true,
                    ]
                ], [
                    "identity_index" => 0,
                    "voucher_index" => 1,
                    "product_index" => 0,
                    "state" => "accepted",
                    "assert_created" => "success",
                    "assert_limit" => [
                        "fund_id" => 1,
                        "limit_available" => 3,
                        "products_available" => true,
                        "product_organizations" => true,
                    ],
                    "assert_limit_after_state_change" => [
                        "fund_id" => 1,
                        "limit_available" => 3,
                        "products_available" => true,
                        "product_organizations" => true,
                    ]
                ], [
                    "identity_index" => 0,
                    "voucher_index" => 1,
                    "product_index" => 0,
                    "assert_created" => "success",
                    "assert_limit" => [
                        "fund_id" => 1,
                        "limit_available" => 2,
                        "products_available" => true,
                        "product_organizations" => true,
                    ],
                ], [
                    "identity_index" => 0,
                    "voucher_index" => 1,
                    "product_index" => 0,
                    "assert_created" => "success",
                    "assert_limit" => [
                        "fund_id" => 1,
                        "limit_available" => 1,
                        "products_available" => true,
                        "product_organizations" => true,
                    ],
                ], [
                    "identity_index" => 0,
                    "voucher_index" => 0,
                    "product_index" => 0,
                    "assert_created" => "success",
                    "assert_limit" => [
                        "fund_id" => 1,
                        "limit_available" => 0,
                        "products_available" => false,
                        "product_organizations" => true, // check it
                    ],
                ], [
                    "identity_index" => 0,
                    "voucher_index" => 1,
                    "product_index" => 0,
                    "assert_created" => "fail"
                ], [
                    "identity_index" => 0,
                    "voucher_index" => 3,
                    "product_index" => 0,
                    "assert_created" => "success",
                    "assert_limit" => [
                        "fund_id" => 3,
                        "limit_available" => null,
                        "products_available" => true,
                        "product_organizations" => true,
                    ]
                ], [
                    "identity_index" => 0,
                    "voucher_index" => 3,
                    "product_index" => 1,
                    "assert_created" => "success",
                    "assert_limit" => [
                        "fund_id" => 3,
                        "limit_available" => 5,
                        "products_available" => true,
                        "product_organizations" => true,
                    ]
                ], [
                    "identity_index" => 0,
                    "voucher_index" => 3,
                    "product_index" => 1,
                    "assert_created" => "success",
                    "assert_limit" => [
                        "fund_id" => 3,
                        "limit_available" => 4,
                        "products_available" => true,
                        "product_organizations" => true,
                    ]
                ]]
            ], [
                "type" => "update_limits",
                "actions" => [[
                    "product_index" => 0,
                    "identity_index" => 0,
                    "assert_update" => ["response" => "success"],
                    "limits" => ["fund_id" => 1, "limit_total" => 10, "limit_per_identity" => 3],
                    "assert_limit" => [
                        "fund_id" => 1,
                        "limit_available" => 4,
                        "products_available" => true,
                        "product_organizations" => true,
                    ],
                ], [
                    "product_index" => 1,
                    "identity_index" => 0,
                    "assert_update" => ["response" => "success"],
                    "limits" => ["fund_id" => 1, "limit_total" => 8, "limit_per_identity" => 3],
                    "assert_limit" => [
                        "fund_id" => 1,
                        "limit_available" => 8,
                        "products_available" => true,
                        "product_organizations" => true,
                    ],
                ], [
                    "product_index" => 1,
                    "identity_index" => 0,
                    "assert_update" => ["response" => "success"],
                    "limits" => ["fund_id" => 3, "limit_total" => 13, "limit_per_identity" => 3],
                    "assert_limit" => [
                        "fund_id" => 3,
                        "limit_available" => 7,
                        "products_available" => true,
                        "product_organizations" => true,
                    ],
                ], [
                    "product_index" => 1,
                    "identity_index" => 0,
                    "assert_update" => ["response" => "success"],
                    "limits" => ["fund_id" => 3, "limit_total" => 13, "limit_per_identity" => 1],
                    "assert_limit" => [
                        "fund_id" => 3,
                        "limit_available" => 1,
                        "products_available" => true,
                        "product_organizations" => true,
                    ],
                ]]
            ], [
                "type" => "voucher_transactions",
                "actions" => [
                    ["identity_index" => 0, "voucher_index" => 0, "product_index" => 0, "assert_created" => "success"],
                    ["identity_index" => 0, "voucher_index" => 0, "product_index" => 0, "assert_created" => "success"],
                    ["identity_index" => 0, "voucher_index" => 0, "product_index" => 0, "assert_created" => "fail"],
                    ["identity_index" => 0, "voucher_index" => 1, "product_index" => 0, "assert_created" => "success"],
                    ["identity_index" => 0, "voucher_index" => 1, "product_index" => 0, "assert_created" => "success"],
                    ["identity_index" => 0, "voucher_index" => 1, "product_index" => 0, "assert_created" => "fail"],
                    ["identity_index" => 0, "voucher_index" => 3, "product_index" => 1, "assert_created" => "success"],
                    ["identity_index" => 0, "voucher_index" => 3, "product_index" => 1, "assert_created" => "fail"],

                ]
            ], [
                "type" => "update_limits",
                "actions" => [
                    [
                        "product_index" => 0,
                        "assert_update" => ["response" => "fail_validation", "errors" => ["enable_products.0"]],
                        "limits" => ["fund_id" => 1, "limit_total" => 15, "limit_per_identity" => 4],
                    ], [
                        "product_index" => 1,
                        "assert_update" => ["response" => "fail_validation", "errors" => ["enable_products.0"]],
                        "limits" => ["fund_id" => 1, "limit_total" => 33, "limit_per_identity" => 5],
                    ], [
                        "product_index" => 1,
                        "assert_update" => ["response" => "fail_validation", "errors" => ["enable_products.0"]],
                        "limits" => ["fund_id" => 3, "limit_total" => 30, "limit_per_identity" => 4],
                    ],
                ]
            ]],
        ]],
    ], [
        "products" => [[
            "stock" => 15,
            "funds" => [2, 3],
            "limits" => [
                ["fund_id" => 2, "limit_total" => 8, "limit_per_identity" => 1],
                ["fund_id" => 3, "limit_total" => 10, "limit_per_identity" => 2],
            ],
        ], [
            "stock" => 30,
            "funds" => [3],
            "limits" => [
                ["fund_id" => 3, "limit_total" => 15, "limit_per_identity" => 2],
            ],
        ]],

        "identities" => [[
            "vouchers" => [
                ["amount" => 200, "fund_id" => 2, "limit_multiplier" => 2],
                ["amount" => 200, "fund_id" => 2, "limit_multiplier" => 3],
                ["amount" => 500, "fund_id" => 3, "limit_multiplier" => 1],
            ],
        ], [
            "vouchers" => [
                ["amount" => 200, "fund_id" => 2, "limit_multiplier" => 2],
                ["amount" => 500, "fund_id" => 3, "limit_multiplier" => 3],
            ],
        ]],

        "asserts" => [[
            "base" => [[
                "product_index" => 0,
                "identity_index" => 0,
                "assert" => [[
                    "fund_id" => 2,
                    "limit_available" => 5,
                    "products_available" => true,
                    "product_organizations" => true,
                ], [
                    "fund_id" => 3,
                    "limit_available" => 2,
                    "products_available" => true,
                    "product_organizations" => true,
                ]],
            ], [
                "product_index" => 1,
                "identity_index" => 1,
                "assert" => [[
                    "fund_id" => 3,
                    "limit_available" => 6,
                    "products_available" => true,
                    "product_organizations" => true,
                ]],
            ], [
                "product_index" => 1,
                "identity_index" => 0,
                "assert" => [[
                    "fund_id" => 3,
                    "limit_available" => 2,
                    "products_available" => true,
                    "product_organizations" => true,
                ]],
            ]],

            "after" => [[
                "type" => "reservations",
                "actions" => [[
                    "identity_index" => 0,
                    "voucher_index" => 0,
                    "product_index" => 0,
                    "assert_created" => "success",
                    "assert_limit" => [
                        "fund_id" => 2,
                        "limit_available" => 4,
                        "products_available" => true,
                        "product_organizations" => true,
                    ]
                ], [
                    "identity_index" => 1,
                    "voucher_index" => 4,
                    "product_index" => 1,
                    "state" => "canceled_by_client",
                    "assert_created" => "success",
                    "assert_limit" => [
                        "fund_id" => 3,
                        "limit_available" => 5,
                        "products_available" => true,
                        "product_organizations" => true,
                    ],
                    "assert_limit_after_state_change" => [
                        "fund_id" => 3,
                        "limit_available" => 6,
                        "products_available" => true,
                        "product_organizations" => true,
                    ]
                ]]
            ], [
                "type" => "update_limits",
                "actions" => [[
                    "product_index" => 0,
                    "identity_index" => 0,
                    "assert_update" => ["response" => "success"],
                    "limits" => ["fund_id" => 2, "limit_total" => 12, "limit_per_identity" => 2],
                    "assert_limit" => [
                        "fund_id" => 2,
                        "limit_available" => 9,
                        "products_available" => true,
                        "product_organizations" => true,
                    ],
                ], [
                    "product_index" => 0,
                    "identity_index" => 0,
                    "assert_update" => ["response" => "success"],
                    "limits" => ["fund_id" => 2, "limit_total" => 12, "limit_per_identity" => 1],
                    "assert_limit" => [
                        "fund_id" => 2,
                        "limit_available" => 4,
                        "products_available" => true,
                        "product_organizations" => true,
                    ],
                ], [
                    "product_index" => 1,
                    "identity_index" => 1,
                    "assert_update" => ["response" => "success"],
                    "limits" => ["fund_id" => 3, "limit_total" => 8, "limit_per_identity" => 2],
                    "assert_limit" => [
                        "fund_id" => 3,
                        "limit_available" => 6,
                        "products_available" => true,
                        "product_organizations" => true,
                    ],
                ]]
            ], [
                "type" => "voucher_transactions",
                "actions" => [
                    ["identity_index" => 0, "voucher_index" => 0, "product_index" => 0, "assert_created" => "success"],
                    ["identity_index" => 0, "voucher_index" => 0, "product_index" => 0, "assert_created" => "fail"],
                    ["identity_index" => 0, "voucher_index" => 1, "product_index" => 0, "assert_created" => "success"],
                    ["identity_index" => 0, "voucher_index" => 1, "product_index" => 0, "assert_created" => "success"],
                    ["identity_index" => 0, "voucher_index" => 1, "product_index" => 0, "assert_created" => "success"],
                    ["identity_index" => 0, "voucher_index" => 0, "product_index" => 0, "assert_created" => "fail"],
                    ["identity_index" => 0, "voucher_index" => 2, "product_index" => 0, "assert_created" => "success"],
                    ["identity_index" => 0, "voucher_index" => 2, "product_index" => 0, "assert_created" => "success"],
                    ["identity_index" => 0, "voucher_index" => 2, "product_index" => 0, "assert_created" => "fail"],
                    ["identity_index" => 1, "voucher_index" => 4, "product_index" => 1, "assert_created" => "success"],
                    ["identity_index" => 1, "voucher_index" => 4, "product_index" => 1, "assert_created" => "success"],
                    ["identity_index" => 1, "voucher_index" => 4, "product_index" => 1, "assert_created" => "success"],
                    ["identity_index" => 1, "voucher_index" => 4, "product_index" => 1, "assert_created" => "success"],
                    ["identity_index" => 1, "voucher_index" => 4, "product_index" => 1, "assert_created" => "success"],
                    ["identity_index" => 1, "voucher_index" => 4, "product_index" => 1, "assert_created" => "success"],
                    ["identity_index" => 1, "voucher_index" => 4, "product_index" => 1, "assert_created" => "fail"],
                ]
            ]],
        ]],
    ]];

    /**
     * @return void
     * @throws \Throwable
     */
    public function testProductStocks(): void
    {
        foreach ($this->testCases as $testCase) {
            \DB::beginTransaction();
            $this->processProductStocksTestCase($testCase);
            $this->resetProperties();
            \DB::rollBack();
        }
    }

    /**
     * @return void
     */
    protected function resetProperties(): void
    {
        $this->products = [];
        $this->vouchers = [];
        $this->identities = [];
    }

    /**
     * @throws \Throwable
     */
    protected function processProductStocksTestCase(array $testCase): void
    {
        $providerIdentity = $this->makeIdentity($this->makeUniqueEmail('provider_'));
        $provider = $this->makeProvider($providerIdentity, $testCase['products']);

        $this->makeIdentities($testCase['identities']);
        $this->processAsserts($provider, $testCase['asserts']);
    }

    /**
     * @param array $identities
     * @return void
     */
    protected function makeIdentities(array $identities): void
    {
        foreach ($identities as $identityArr) {
            $identity = $this->makeIdentity($this->makeUniqueEmail());
            $this->identities[] = $identity;

            // make vouchers
            foreach ($identityArr['vouchers'] as $voucherArr) {
                $fund = $this->findFund($voucherArr['fund_id']);

                $voucher = $fund->makeVoucher(
                    $identity, [], $voucherArr['amount'], null, $voucherArr['limit_multiplier']
                );
                $this->assertNotNull($voucher, 'Voucher not found');

                $this->vouchers[] = $voucher;
            }
        }
    }

    /**
     * @param Organization $provider
     * @param array $asserts
     * @return void
     * @throws \Throwable
     */
    protected function processAsserts(Organization $provider, array $asserts): void
    {
        foreach ($asserts as $assert) {
            foreach ($assert['base'] as $base) {
                $product = $this->products[$base['product_index']] ?? null;
                $this->assertNotNull($product, 'Product not found');

                $identity = $this->identities[$base['identity_index']] ?? null;
                $this->assertNotNull($identity, 'Identity not found');

                $this->assertProductLimits($provider, $identity, $product, $base['assert']);
            }

            // after actions
            foreach ($assert['after'] ?? [] as $after) {
                match ($after['type']) {
                    'reservations' => $this->processActionReservations($provider, $after['actions']),
                    'update_limits' => $this->processActionUpdateLimits($provider, $after['actions']),
                    'voucher_transactions' => $this->processVoucherTransactions($provider, $after['actions']),
                    'product_vouchers' => $this->processProductVouchers($provider, $after['actions']),
                    default => null,
                };
            }
        }
    }

    /**
     * @param Organization $provider
     * @param array $actions
     * @return void
     */
    protected function processProductVouchers(Organization $provider, array $actions): void
    {
        foreach ($actions as $action) {
            $fund = $this->findFund($action['fund_id']);

            $product = $this->products[$action['product_index']] ?? null;
            $this->assertNotNull($product, 'Product not found');

            $identity = $this->identities[$action['identity_index']] ?? null;
            $this->assertNotNull($identity, 'Identity not found');

            $productVoucher = $fund->makeProductVoucher($identity->address, [], $product->id);
            $this->assertNotNull($productVoucher, 'Product voucher not created');

            $this->assertProductLimits($provider, $identity, $product, [$action['assert_limit']]);
        }
    }

    /**
     * @param Organization $provider
     * @param array $actions
     * @return void
     */
    protected function processVoucherTransactions(Organization $provider, array $actions): void
    {
        foreach ($actions as $action) {
            $assertCreated = $action['assert_created'] === 'success';

            $product = $this->products[$action['product_index']] ?? null;
            $this->assertNotNull($product, 'Product not found');

            $voucher = $this->vouchers[$action['voucher_index']] ?? null;
            $this->assertNotNull($voucher, 'Voucher not found');

            $this->makeTransaction($provider, $voucher, $product, $assertCreated);
            $delay = Config::get('forus.transactions.hard_limit') + 1;
            sleep($delay);
        }
    }

    /**
     * @param Organization $provider
     * @param Voucher $voucher
     * @param Product $product
     * @param bool $assertCreated
     * @return void
     */
    protected function makeTransaction(
        Organization $provider,
        Voucher $voucher,
        Product $product,
        bool $assertCreated = true
    ): void {
        $proxy = $this->makeIdentityProxy($provider->identity);
        $headers = $this->makeApiHeaders($proxy);

        $voucherToken = $voucher->token_without_confirmation->address;

        $url = sprintf($this->urls['provider'] . '/vouchers/%s/transactions', $voucherToken);

        $response = $this->post($url, [
            'product_id' => $product->id,
            'organization_id' => $provider->id,
        ], $headers);

        if ($assertCreated) {
            $response->assertSuccessful();
            $transaction = VoucherTransaction::find($response->json('data.id'));
            $this->assertNotNull($transaction, 'Voucher transaction not found');
        } else {
            $response->assertJsonValidationErrorFor('product_id');
        }
    }

    /**
     * @param Organization $provider
     * @param array $actions
     * @return void
     * @throws \Throwable
     */
    protected function processActionReservations(Organization $provider, array $actions): void
    {
        foreach ($actions as $action) {
            $product = $this->products[$action['product_index']] ?? null;
            $this->assertNotNull($product, 'Product not found');

            $identity = $this->identities[$action['identity_index']] ?? null;
            $this->assertNotNull($identity, 'Identity not found');

            $voucher = $this->vouchers[$action['voucher_index']] ?? null;
            $this->assertNotNull($voucher, 'Voucher not found');

            $reservation = $this->makeProductReservation(
                $identity, $voucher, $product, $action['assert_created'] === 'success'
            );

            if ($action['assert_limit'] ?? false) {
                $this->assertProductLimits($provider, $identity, $product, [$action['assert_limit']]);
            }

            if ($reservation && ($action['state'] ?? false)) {
                $this->changeReservationState($provider, $reservation, $action['state']);
            }

            if ($action['assert_limit_after_state_change'] ?? false) {
                $this->assertProductLimits(
                    $provider, $identity, $product, [$action['assert_limit_after_state_change']]
                );
            }
        }
    }

    /**
     * @param Organization $provider
     * @param array $actions
     * @return void
     */
    protected function processActionUpdateLimits(Organization $provider, array $actions): void
    {
        foreach ($actions as $action) {
            $limits = $action['limits'];
            $fund = $this->findFund($limits['fund_id']);

            $product = $this->products[$action['product_index']] ?? null;
            $this->assertNotNull($product, 'Product not found');

            /** @var FundProvider $fundProvider */
            $fundProvider = $provider->fund_providers()
                ->where('fund_id', $limits['fund_id'])
                ->first();
            $this->assertNotNull($fundProvider, 'Fund Provider not found');

            $productsParams = [
                'enable_products' => [
                    array_merge([
                        'id' => $product->id,
                    ], array_only($limits, ['limit_per_identity', 'limit_total']))
                ]
            ];

            $this->updateProducts($fund, $fundProvider, $productsParams, $action['assert_update']);

            if ($action['assert_limit'] ?? false) {
                $identity = $this->identities[$action['identity_index']] ?? null;
                $this->assertNotNull($identity, 'Identity not found');

                $this->assertProductLimits($provider, $identity, $product, [$action['assert_limit']]);
            }
        }
    }

    /**
     * @param Organization $provider
     * @param ProductReservation $reservation
     * @param string $state
     * @return void
     */
    protected function changeReservationState(
        Organization $provider,
        ProductReservation $reservation,
        string $state
    ): void {
        if ($reservation->state === $state) {
            return;
        }

        match($state) {
            ProductReservation::STATE_REJECTED,
            ProductReservation::STATE_CANCELED_BY_PROVIDER => $this->cancelReservationByProvider(
                $provider, $reservation
            ),
            ProductReservation::STATE_ACCEPTED => $this->approveReservationByProvider(
                $provider, $reservation
            ),
            ProductReservation::STATE_CANCELED_BY_CLIENT => $this->cancelReservationByClient($reservation),
            default => $reservation
        };

        $reservation = ProductReservation::find($reservation->id);
        $this->assertTrue(
            $reservation->state === $state,
            "Reservation state $reservation->state not equals $state"
        );
    }

    /**
     * @param Organization $provider
     * @param ProductReservation $reservation
     * @return void
     */
    protected function cancelReservationByProvider(
        Organization $provider,
        ProductReservation $reservation
    ): void {
        $proxy = $this->makeIdentityProxy($provider->identity);
        $headers = $this->makeApiHeaders($proxy);
        $url = sprintf(
            $this->urls['organization'] . '/%s/product-reservations/%s/reject',
            $provider->id,
            $reservation->id
        );

        $response = $this->post($url, [], $headers);

        $response->assertSuccessful();
    }

    /**
     * @param Organization $provider
     * @param ProductReservation $reservation
     * @return void
     */
    protected function approveReservationByProvider(
        Organization $provider,
        ProductReservation $reservation
    ): void {
        $proxy = $this->makeIdentityProxy($provider->identity);
        $headers = $this->makeApiHeaders($proxy);
        $url = sprintf(
            $this->urls['organization'] . '/%s/product-reservations/%s/accept',
            $provider->id,
            $reservation->id
        );

        $response = $this->post($url, [], $headers);

        $response->assertSuccessful();
    }

    /**
     * @param ProductReservation $reservation
     * @return void
     */
    protected function cancelReservationByClient(ProductReservation $reservation): void
    {
        $identity = $reservation->voucher->identity;

        $proxy = $this->makeIdentityProxy($identity);
        $headers = $this->makeApiHeaders($proxy);
        $url = sprintf($this->urls['reservations'] . '/%s', $reservation->id);

        $response = $this->patch($url, [
            'state' => ProductReservation::STATE_CANCELED_BY_CLIENT
        ], $headers);

        $response->assertSuccessful();
    }

    /**
     * @param Organization $provider
     * @param Identity $identity
     * @param Product $product
     * @param array $asserts
     * @return void
     */
    protected function assertProductLimits(
        Organization $provider,
        Identity $identity,
        Product $product,
        array $asserts
    ): void {
        $products = $this->getProductsOnWebshop($provider, $identity);

        $productArr = array_first($products['data'], fn($item) => $product->id === $item['id']);
        $this->assertNotNull($productArr, 'Product not found');

        foreach ($asserts as $assert) {
            $fund = array_first($productArr['funds'], fn($item) => $assert['fund_id'] === $item['id']);
            $this->assertNotNull($fund, 'Fund not found');

            $this->assertEquals($assert['limit_available'], $fund['limit_available'], 'Limits not equals');

            $vouchers = array_filter(
                $this->vouchers, fn(Voucher $item) => $assert['fund_id'] === $item->fund_id
            );
            $this->assertNotEmpty($vouchers, 'Vouchers not found');

            $exists = false;
            foreach ($vouchers as $voucher) {
                if ($exists) {
                    continue;
                }

                $this->checkAllowedOrganizationsForProvider(
                    $provider, $voucher, $assert['product_organizations']
                );
                $products = $this->getProductsForProvider($provider, $voucher);

                $exists = array_first($products['data'], fn($item) => $product->id === $item['id']);
            }

            $assert['products_available']
                ? $this->assertNotNull($exists, 'Product not available for provider')
                : $this->assertNull($exists, 'Product must not but is available for provider');
        }
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @param Product $product
     * @param bool $assertCreated
     * @return ProductReservation|null
     */
    protected function makeProductReservation(
        Identity $identity,
        Voucher $voucher,
        Product $product,
        bool $assertCreated = true
    ): ?ProductReservation {
        $proxy = $this->makeIdentityProxy($identity);
        $headers = $this->makeApiHeaders($proxy);

        $response = $this->post($this->urls['reservations'], [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'user_note' => '',
            'voucher_address' => $voucher->token_without_confirmation->address,
            'product_id' => $product->id
        ], $headers);

        if ($assertCreated) {
            $response->assertSuccessful();
            /** @var ProductReservation $reservation */
            $reservation = ProductReservation::find($response->json('data.id'));
            $this->assertNotNull($reservation, 'Reservation not found');

            return $reservation;
        }

        $response->assertJsonValidationErrorFor('product_id');

        return null;
    }

    /**
     * @param Organization $organization
     * @param Identity $identity
     * @return Collection|TestResponse|array
     */
    protected function getProductsOnWebshop(
        Organization $organization,
        Identity $identity
    ): Collection|TestResponse|array {
        $proxy = $this->makeIdentityProxy($identity);
        $headers = $this->makeApiHeaders($proxy);

        $url = sprintf($this->urls['products'] . '?organization_id=%s', $organization->id);

        $response = $this->getJson($url, $headers);
        $response->assertSuccessful();

        return $response;
    }

    /**
     * @param Organization $provider
     * @param Voucher $voucher
     * @return Collection|TestResponse|array
     */
    protected function getProductsForProvider(
        Organization $provider,
        Voucher $voucher
    ): Collection|TestResponse|array {
        $proxy = $this->makeIdentityProxy($provider->identity);
        $headers = $this->makeApiHeaders($proxy);

        $voucherToken = $voucher->token_without_confirmation->address;

        $url = sprintf(
            $this->urls['provider'] . '/vouchers/%s/products?organization_id=%s',
            $voucherToken,
            $provider->id
        );

        $response = $this->getJson($url, $headers);
        $response->assertSuccessful();

        return $response;
    }

    /**
     * @param Organization $provider
     * @param Voucher $voucher
     * @param bool $exist
     * @return Collection|TestResponse|array
     */
    protected function checkAllowedOrganizationsForProvider(
        Organization $provider,
        Voucher $voucher,
        bool $exist = true
    ): Collection|TestResponse|array {
        $proxy = $this->makeIdentityProxy($provider->identity);
        $headers = $this->makeApiHeaders($proxy);

        $voucherToken = $voucher->token_without_confirmation->address;

        $url = sprintf($this->urls['provider'] . '/vouchers/%s', $voucherToken);

        $response = $this->getJson($url, $headers);
        $response->assertSuccessful();

        $organizationExists = array_first(
            $response['data']['allowed_product_organizations'],
            fn($item) => $provider->id === $item['id']
        );

        $exist
            ? $this->assertNotNull($organizationExists, 'Provider doesnt exists in list of available')
            : $this->assertNull($organizationExists, 'Provider still exists in list of available');

        return $response;
    }

    /**
     * @param Identity $identity
     * @param array $params
     * @return Organization
     * @throws \Throwable
     */
    protected function makeProvider(
        Identity $identity,
        array $params = []
    ): Organization {
        $testData = new TestData();

        $countOffices = $testData->config('provider_offices_count');
        $organization = $testData->makeOrganizations(
            "Provider", $identity->address, 1, [], $countOffices
        )[0];

        foreach ($params as $param) {
            $product = $testData->makeProducts($organization, 1, [
                'total_amount' => $param['stock'],
                'unlimited_stock' => false,
            ])[0];
            $this->assertNotNull($product, 'Product not found');
            $this->products[] = $product;

            foreach ($param['funds'] as $fundId) {
                $fund = $this->findFund($fundId);

                /** @var FundProvider $fundProvider */
                $fundProvider = $fund->providers()->firstOrCreate([
                    'organization_id'   => $organization->id,
                    'allow_budget'      => $fund->isTypeBudget(),
                    'allow_products'    => false,
                    'state'             => FundProvider::STATE_ACCEPTED,
                ]);

                FundProviderApplied::dispatch($fund, $fundProvider);

                if ($fundProvider->allow_budget) {
                    FundProviderApprovedBudget::dispatch($fundProvider);
                }

                if ($fundProvider->allow_products) {
                    FundProviderApprovedProducts::dispatch($fundProvider);
                }

                $limits = array_first($param['limits'], function ($arr) use ($fund) {
                    return $arr['fund_id'] === $fund->id;
                });

                $productsParams['enable_products'] = [array_merge([
                    'id' => $product->id,
                ], $limits ? array_only($limits, ['limit_per_identity', 'limit_total']) : [])];

                $this->updateProducts($fund, $fundProvider, $productsParams, ['response' => 'success']);
            }
        }

        return $organization;
    }

    /**
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @param array $params
     * @param array $assertType
     * @return void
     */
    protected function updateProducts(
        Fund $fund,
        FundProvider $fundProvider,
        array $params,
        array $assertType
    ): void {
        $proxy = $this->makeIdentityProxy($fund->organization->identity);
        $headers = $this->makeApiHeaders($proxy);
        $url = sprintf(
            $this->urls['organization'] . '/%s/funds/%s/providers/%s',
            $fund->organization->id,
            $fund->id,
            $fundProvider->id
        );

        $response = $this->patch($url, $params, $headers);

        if ($assertType['response'] === 'success') {
            $response->assertSuccessful();
        } elseif ($assertType['response'] === 'fail_validation') {
            $response->assertJsonValidationErrors($assertType['errors']);
        }
    }

    /**
     * @param $fundId
     * @return Fund
     */
    protected function findFund($fundId): Fund
    {
        /** @var Fund $fund */
        $fund = FundQuery::whereActiveFilter(Fund::where('id', $fundId))->first();
        $this->assertNotNull($fund, 'Fund not found');

        return $fund;
    }
}