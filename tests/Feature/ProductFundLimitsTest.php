<?php

namespace Tests\Feature;

use App\Helpers\Arr;
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
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ProductFundLimitsTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var array|string[]
     */
    protected array $urls = [
        'organization' => '/api/v1/platform/organizations',
        'reservations' => '/api/v1/platform/product-reservations',
        'fundProviders' => '/api/v1/organizations/:organizationId/provider/funds',
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
    protected array $testCase1 = [
        "data" => [
            "products" => [[
                "total_amount" => 20,
                "unlimited_stock" => false,
                "funds" => [
                    ["fund_id" => 1, 'allow_budget' => true, 'allow_products' => false],
                    ["fund_id" => 2, 'allow_budget' => true, 'allow_products' => false],
                    ["fund_id" => 3, 'allow_budget' => true, 'allow_products' => true],
                ],
                "limits" => [
                    ["fund_id" => 1, "limit_total" => 5, "limit_per_identity" => 2],
                    ["fund_id" => 2, "limit_total" => 8, "limit_per_identity" => 3],
                ],
            ], [
                "total_amount" => 30,
                "unlimited_stock" => false,
                "funds" => [
                    ["fund_id" => 1, 'allow_budget' => true, 'allow_products' => false],
                    ["fund_id" => 3, 'allow_budget' => true, 'allow_products' => false],
                ],
                "limits" => [
                    ["fund_id" => 1, "limit_total" => 5, "limit_per_identity" => 2],
                    ["fund_id" => 3, "limit_total" => 15, "limit_per_identity" => 2],
                ],
            ]],
            "identities" => [[
                "vouchers" => [
                    ["amount" => 100, "fund_id" => 1, "limit_multiplier" => 1],
                    ["amount" => 200, "fund_id" => 1, "limit_multiplier" => 2],
                    ["amount" => 200, "fund_id" => 2, "limit_multiplier" => 2],
                    ["amount" => 500, "fund_id" => 3, "limit_multiplier" => 3],
                ],
            ]],
        ],
        "tests" => [[
            'type' => 'assertion',
            'actions' => [[
                'data' => ["identity" => 0, "product" => 0],
                "assert_limits" => [
                    ["fund_id" => 1, "limit_available" => 5, "products" => true, "organizations" => true],
                    ["fund_id" => 2, "limit_available" => 6, "products" => true, "organizations" => true],
                    ["fund_id" => 3, "limit_available" => null, "products" => true, "organizations" => true],
                ],
            ], [
                'data' => ["identity" => 0, "product" => 1],
                "assert_limits" => [
                    ["fund_id" => 1, "limit_available" => 5, "products" => true, "organizations" => true],
                    ["fund_id" => 3, "limit_available" => 6, "products" => true, "organizations" => true],
                ],
            ]]
        ], [
            "type" => "reservations",
            "actions" => [[
                'data' => ["identity" => 0, "voucher" => 1, "product" => 0],
                "assert_success" => true,
                "assert_limits" => [["fund_id" => 1, "limit_available" => 4, "products" => true, "organizations" => true]],
            ], [
                'data' => ["identity" => 0, "voucher" => 1, "product" => 0, "state" => "canceled_by_client"],
                "assert_success" => true,
                "assert_limits" => [["fund_id" => 1, "limit_available" => 3, "products" => true, "organizations" => true]],
                "assert_limits_after" => [["fund_id" => 1, "limit_available" => 4, "products" => true, "organizations" => true]],
            ], [
                'data' => ["identity" => 0,"voucher" => 1, "product" => 0, "state" => "rejected"],
                "assert_success" => true,
                "assert_limits" => [["fund_id" => 1, "limit_available" => 3, "products" => true, "organizations" => true]],
                "assert_limits_after" => [["fund_id" => 1, "limit_available" => 4, "products" => true, "organizations" => true]],
            ], [
                'data' => ["identity" => 0, "voucher" => 1, "product" => 0, "state" => "accepted"],
                "assert_success" => true,
                "assert_limits" => [["fund_id" => 1, "limit_available" => 3, "products" => true, "organizations" => true]],
                "assert_limits_after" => [["fund_id" => 1, "limit_available" => 3, "products" => true, "organizations" => true]],
            ], [
                'data' => ["identity" => 0, "voucher" => 1, "product" => 0],
                "assert_success" => true,
                "assert_limits" => [["fund_id" => 1, "limit_available" => 2, "products" => true, "organizations" => true]],
            ], [
                'data' => ["identity" => 0, "voucher" => 1, "product" => 0],
                "assert_success" => true,
                "assert_limits" => [["fund_id" => 1, "limit_available" => 1, "products" => true, "organizations" => true]],
            ], [
                'data' => ["identity" => 0, "voucher" => 0, "product" => 0],
                "assert_success" => true,
                "assert_limits" => [["fund_id" => 1, "limit_available" => 0, "products" => false, "organizations" => true]],
            ], [
                'data' => ["identity" => 0, "voucher" => 1, "product" => 0,],
                "assert_success" => false,
            ], [
                'data' => ["identity" => 0, "voucher" => 3, "product" => 0],
                "assert_success" => true,
                "assert_limits" => [["fund_id" => 3, "limit_available" => null, "products" => true, "organizations" => true]],
            ], [
                'data' => ["identity" => 0, "voucher" => 3, "product" => 1],
                "assert_success" => true,
                "assert_limits" => [["fund_id" => 3, "limit_available" => 5, "products" => true, "organizations" => true]],
            ], [
                'data' => ["identity" => 0, "voucher" => 3, "product" => 1],
                "assert_success" => true,
                "assert_limits" => [["fund_id" => 3, "limit_available" => 4, "products" => true, "organizations" => true]],
            ]]
        ], [
            "type" => "update_limits",
            "actions" => [[
                "product" => 0,
                "identity" => 0,
                "assert_success" => true,
                "limits" => ["fund_id" => 1, "limit_total" => 10, "limit_per_identity" => 3],
                "assert_limits" => [["fund_id" => 1, "limit_available" => 4, "products" => true, "organizations" => true]],
            ], [
                "product" => 1,
                "identity" => 0,
                "assert_success" => true,
                "limits" => ["fund_id" => 1, "limit_total" => 8, "limit_per_identity" => 3],
                "assert_limits" => [["fund_id" => 1, "limit_available" => 8, "products" => true, "organizations" => true]],
            ], [
                "product" => 1,
                "identity" => 0,
                "assert_success" => true,
                "limits" => ["fund_id" => 3, "limit_total" => 13, "limit_per_identity" => 3],
                "assert_limits" => [["fund_id" => 3, "limit_available" => 7, "products" => true, "organizations" => true]],
            ], [
                "product" => 1,
                "identity" => 0,
                "assert_success" => true,
                "limits" => ["fund_id" => 3, "limit_total" => 13, "limit_per_identity" => 1],
                "assert_limits" => [["fund_id" => 3, "limit_available" => 1, "products" => true, "organizations" => true]],
            ]]
        ], [
            "type" => "voucher_transactions",
            "actions" => [
                ["identity" => 0, "voucher" => 0, "product" => 0, "assert_success" => true],
                ["identity" => 0, "voucher" => 0, "product" => 0, "assert_success" => true],
                ["identity" => 0, "voucher" => 0, "product" => 0, "assert_success" => false],
                ["identity" => 0, "voucher" => 1, "product" => 0, "assert_success" => true],
                ["identity" => 0, "voucher" => 1, "product" => 0, "assert_success" => true],
                ["identity" => 0, "voucher" => 1, "product" => 0, "assert_success" => false],
                ["identity" => 0, "voucher" => 3, "product" => 1, "assert_success" => true],
                ["identity" => 0, "voucher" => 3, "product" => 1, "assert_success" => false],
            ],
        ], [
            "type" => "update_limits",
            "actions" => [[
                "product" => 0,
                "assert_success" => false,
                "assert_errors" => ["enable_products.0"],
                "limits" => ["fund_id" => 1, "limit_total" => 15, "limit_per_identity" => 4],
            ], [
                "product" => 1,
                "assert_success" => false,
                "assert_errors" => ["enable_products.0"],
                "limits" => ["fund_id" => 1, "limit_total" => 33, "limit_per_identity" => 5],
            ], [
                "product" => 1,
                "assert_success" => false,
                "assert_errors" => ["enable_products.0"],
                "limits" => ["fund_id" => 3, "limit_total" => 30, "limit_per_identity" => 4],
            ]],
        ]],
    ];

    protected array $testCase2 = [
        "data" => [
            "products" => [[
                "total_amount" => 15,
                "unlimited_stock" => false,
                "funds" => [
                    ["fund_id" => 2, 'allow_budget' => true, 'allow_products' => false],
                    ["fund_id" => 3, 'allow_budget' => true, 'allow_products' => false],
                ],
                "limits" => [
                    ["fund_id" => 2, "limit_total" => 8, "limit_per_identity" => 1],
                    ["fund_id" => 3, "limit_total" => 10, "limit_per_identity" => 2],
                ],
            ], [
                "total_amount" => 30,
                "unlimited_stock" => false,
                "funds" => [
                    ["fund_id" => 3, 'allow_budget' => true, 'allow_products' => false],
                ],
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
        ],
        "tests" => [[
            "type" => "assertion",
            "actions" => [[
                "data" => ["product" => 0, "identity" => 0],
                "assert_limits" => [
                    ["fund_id" => 2, "limit_available" => 5, "products" => true, "organizations" => true],
                    ["fund_id" => 3, "limit_available" => 2, "products" => true, "organizations" => true],
                ],
            ], [
                "data" => ["product" => 1, "identity" => 1],
                "assert_limits" => [
                    ["fund_id" => 3, "limit_available" => 6, "products" => true, "organizations" => true],
                ],
            ], [
                "data" => ["product" => 1, "identity" => 0],
                "assert_limits" => [
                    ["fund_id" => 3, "limit_available" => 2, "products" => true, "organizations" => true],
                ],
            ]],
        ], [
            "type" => "reservations",
            "actions" => [[
                "data" => ["identity" => 0, "voucher" => 0, "product" => 0],
                "assert_success" => true,
                "assert_limits" => [["fund_id" => 2, "limit_available" => 4, "products" => true, "organizations" => true]],
            ], [
                "data" => ["identity" => 1, "voucher" => 4, "product" => 1, "state" => "canceled_by_client"],
                "assert_success" => true,
                "assert_limits" => [["fund_id" => 3, "limit_available" => 5, "products" => true, "organizations" => true]],
                "assert_limits_after" => [["fund_id" => 3, "limit_available" => 6, "products" => true, "organizations" => true]],
            ]]
        ], [
            "type" => "update_limits",
            "actions" => [[
                "product" => 0,
                "identity" => 0,
                "assert_success" => true,
                "limits" => ["fund_id" => 2, "limit_total" => 12, "limit_per_identity" => 2],
                "assert_limits" => [["fund_id" => 2, "limit_available" => 9, "products" => true, "organizations" => true]],
            ], [
                "product" => 0,
                "identity" => 0,
                "assert_success" => true,
                "limits" => ["fund_id" => 2, "limit_total" => 12, "limit_per_identity" => 1],
                "assert_limits" => [["fund_id" => 2, "limit_available" => 4, "products" => true, "organizations" => true]],
            ], [
                "product" => 1,
                "identity" => 1,
                "assert_success" => true,
                "limits" => ["fund_id" => 3, "limit_total" => 8, "limit_per_identity" => 2],
                "assert_limits" => [["fund_id" => 3, "limit_available" => 6, "products" => true, "organizations" => true]],
            ]]
        ], [
            "type" => "voucher_transactions",
            "actions" => [
                ["identity" => 0, "voucher" => 0, "product" => 0, "assert_success" => true],
                ["identity" => 0, "voucher" => 0, "product" => 0, "assert_success" => false],
                ["identity" => 0, "voucher" => 1, "product" => 0, "assert_success" => true],
                ["identity" => 0, "voucher" => 1, "product" => 0, "assert_success" => true],
                ["identity" => 0, "voucher" => 1, "product" => 0, "assert_success" => true],
                ["identity" => 0, "voucher" => 0, "product" => 0, "assert_success" => false],
                ["identity" => 0, "voucher" => 2, "product" => 0, "assert_success" => true],
                ["identity" => 0, "voucher" => 2, "product" => 0, "assert_success" => true],
                ["identity" => 0, "voucher" => 2, "product" => 0, "assert_success" => false],
                ["identity" => 1, "voucher" => 4, "product" => 1, "assert_success" => true],
                ["identity" => 1, "voucher" => 4, "product" => 1, "assert_success" => true],
                ["identity" => 1, "voucher" => 4, "product" => 1, "assert_success" => true],
                ["identity" => 1, "voucher" => 4, "product" => 1, "assert_success" => true],
                ["identity" => 1, "voucher" => 4, "product" => 1, "assert_success" => true],
                ["identity" => 1, "voucher" => 4, "product" => 1, "assert_success" => true],
                ["identity" => 1, "voucher" => 4, "product" => 1, "assert_success" => false],
            ],
        ]],
    ];

    /**
     * @return void
     * @throws \Throwable
     */
    public function testProductFundLimitsCase1(): void
    {
        $this->processTestCase($this->testCase1);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testProductFundLimitsCase2(): void
    {
        $this->processTestCase($this->testCase2);
    }

    /**
     * @throws \Throwable
     */
    protected function processTestCase(array $testCase): void
    {
        $configs = $this->setConfigs();

        $this->makeTestData($testCase['data']);
        $this->processCaseTests($testCase['tests']);
        $this->rollbackConfigs($configs);
    }

    /**
     * @param array $testData
     * @return void
     * @throws \Throwable
     */
    protected function makeTestData(array $testData): void
    {
        $this->makeProducts($testData['products']);
        $this->makeIdentities($testData['identities']);
    }

    /**
     * @return array
     */
    protected function setConfigs(): array
    {
        $initialValue = [
            'forus.transactions.soft_limit' => Config::get('forus.transactions.soft_limit'),
            'forus.transactions.hard_limit' => Config::get('forus.transactions.hard_limit'),
        ];

        Cache::clear();
        Config::set('forus.transactions.soft_limit', 0);
        Config::set('forus.transactions.hard_limit', 0);

        return $initialValue;
    }

    /**
     * @param array $configs
     * @return void
     */
    protected function rollbackConfigs(array $configs): void
    {
        foreach ($configs as $key => $value) {
            Config::set($key, $value);
        }
    }

    /**
     * @param array $identitiesData
     * @return void
     */
    protected function makeIdentities(array $identitiesData): void
    {
        foreach ($identitiesData as $identityData) {
            $identity = $this->makeIdentity($this->makeUniqueEmail());
            $this->identities[] = $identity;

            // make vouchers
            foreach ($identityData['vouchers'] as $voucherArr) {
                $fund = $this->findFund($voucherArr['fund_id']);
                $limit = $voucherArr['limit_multiplier'];
                $voucher = $fund->makeVoucher($identity, [], $voucherArr['amount'], null, $limit);

                $this->assertNotNull($voucher, 'Voucher not found');
                $this->vouchers[] = $voucher;
            }
        }
    }

    /**
     * @param array $productsData
     * @return void
     * @throws \Throwable
     */
    protected function makeProducts(array $productsData): void
    {
        $testData = new TestData();

        foreach ($productsData as $productData) {
            $organization = $testData->makeOrganization("Provider I", $this->makeIdentity());

            $product = $testData->makeProduct($organization, Arr::only($productData, [
                'total_amount', 'unlimited_stock',
            ]));

            foreach ($productData['funds'] as $fundData) {
                $fund = $this->findFund($fundData['fund_id']);
                $limits = array_filter($productData['limits'], fn ($limit) => $limit['fund_id'] == $fund->id);

                /** @var FundProvider $fundProvider */
                $fundProvider = $fund->providers()->updateOrCreate([
                    'organization_id' => $organization->id,
                ], [
                    'state' => FundProvider::STATE_ACCEPTED,
                    'allow_budget' => Arr::get($fundData, 'allow_budget', true),
                    'allow_products' => Arr::get($fundData, 'allow_products', false),
                ]);

                $this->updateProvider($fund, $fundProvider, [
                    'enable_products' => array_map(fn ($productLimit) => [
                        'id' => $product->id,
                        'limit_total' => Arr::get($productLimit, 'limit_total'),
                        'limit_per_identity' => Arr::get($productLimit, 'limit_per_identity'),
                    ], $limits),
                ]);
            }

            $this->products[] = $product;
        }
    }

    /**
     * @param array $tests
     * @return void
     * @throws \Throwable
     */
    protected function processCaseTests(array $tests): void
    {
        foreach ($tests as $test) {
            match ($test['type']) {
                'assertion' => $this->processActionAssertion($test['actions']),
                'reservations' => $this->processActionReservations($test['actions']),
                'update_limits' => $this->processActionUpdateLimits($test['actions']),
                'voucher_transactions' => $this->processVoucherTransactions($test['actions']),
                'product_vouchers' => $this->processProductVouchers($test['actions']),
                default => null,
            };
        }
    }

    /**
     * @param array $actions
     * @return void
     */
    protected function processProductVouchers(array $actions): void
    {
        foreach ($actions as $action) {
            $fund = $this->findFund($action['fund_id']);

            $product = $this->products[$action['product']] ?? null;
            $this->assertNotNull($product, 'Product not found');

            $identity = $this->identities[$action['identity']] ?? null;
            $this->assertNotNull($identity, 'Identity not found');

            $productVoucher = $fund->makeProductVoucher($identity->address, [], $product->id);
            $this->assertNotNull($productVoucher, 'Product voucher not created');

            $this->assertProductLimits($identity, $product, $action['assert_limits']);
        }
    }

    /**
     * @param array $actions
     * @return void
     */
    protected function processVoucherTransactions(array $actions): void
    {
        foreach ($actions as $action) {
            $product = $this->products[$action['product']] ?? null;
            $this->assertNotNull($product, 'Product not found');

            $voucher = $this->vouchers[$action['voucher']] ?? null;
            $this->assertNotNull($voucher, 'Voucher not found');

            $this->makeTransaction($product->organization, $voucher, $product, $action['assert_success']);

            usleep(10 * 1000);
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

        $response = $this->postJson($url, [
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
     * @param array $actions
     * @return void
     * @throws \Throwable
     */
    protected function processActionAssertion(array $actions): void
    {
        foreach ($actions as $action) {
            $product = $this->products[$action['data']['product']] ?? null;
            $this->assertNotNull($product, 'Product not found');

            $identity = $this->identities[$action['data']['identity']] ?? null;
            $this->assertNotNull($identity, 'Identity not found');

            $this->assertProductLimits($identity, $product, $action['assert_limits']);
        }
    }

    /**
     * @param array $actions
     * @return void
     * @throws \Throwable
     */
    protected function processActionReservations(array $actions): void
    {
        foreach ($actions as $action) {
            $product = $this->products[$action['data']['product']] ?? null;
            $this->assertNotNull($product, 'Product not found');

            $provider = $product->organization;
            $identity = $this->identities[$action['data']['identity']] ?? null;
            $this->assertNotNull($identity, 'Identity not found');

            $voucher = $this->vouchers[$action['data']['voucher']] ?? null;
            $this->assertNotNull($voucher, 'Voucher not found');

            $reservation = $this->makeProductReservation($identity, $voucher, $product, $action['assert_success']);

            if ($action['assert_limits'] ?? false) {
                $this->assertProductLimits($identity, $product, $action['assert_limits']);
            }

            if ($reservation && ($action['data']['state'] ?? false)) {
                $this->changeReservationState($provider, $reservation, $action['data']['state']);
            }

            if ($action['assert_limits_after'] ?? false) {
                $this->assertProductLimits($identity, $product, $action['assert_limits_after']);
            }
        }
    }

    /**
     * @param array $actions
     * @return void
     */
    protected function processActionUpdateLimits(array $actions): void
    {
        foreach ($actions as $action) {
            $limits = $action['limits'];
            $fund = $this->findFund($limits['fund_id']);

            $product = $this->products[$action['product']] ?? null;
            $this->assertNotNull($product, 'Product not found');

            /** @var FundProvider $fundProvider */
            $fundProvider = $product->organization->fund_providers()->where('fund_id', $fund->id)->first();
            $this->assertNotNull($fundProvider, 'Fund Provider not found');

            $this->updateProvider($fund, $fundProvider, [
                'enable_products' => [[
                    'id' => $product->id,
                    'limit_total' => $limits['limit_total'],
                    'limit_per_identity' => $limits['limit_per_identity'],
                ]],
            ], $action['assert_success'] ? null : ($action['assert_errors'] ?? []));

            if ($action['assert_limits'] ?? false) {
                $identity = $this->identities[$action['identity']] ?? null;
                $this->assertNotNull($identity, 'Identity not found');
                $this->assertProductLimits($identity, $product, $action['assert_limits']);
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

        $response = $this->postJson($url, [], $headers);

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

        $this->postJson($url, [], $headers)->assertSuccessful();
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
     * @param Identity $identity
     * @param Product $product
     * @param array $asserts
     * @return void
     */
    protected function assertProductLimits(
        Identity $identity,
        Product $product,
        array $asserts,
    ): void {
        $provider = $product->organization;
        $productFunds = $this->getProductOnWebshop($product, $identity)['funds'];

        foreach ($asserts as $assert) {
            $fund = array_first($productFunds, fn($item) => $assert['fund_id'] === $item['id']);

            $this->assertNotNull($fund, 'Fund not found');
            $this->assertEquals($assert['limit_available'], $fund['limit_available'], 'Limits not equals');

            $vouchers = array_filter($this->vouchers, fn(Voucher $item) => $assert['fund_id'] === $item->fund_id);
            $this->assertNotEmpty($vouchers, 'Vouchers not found');

            $exists = false;

            foreach ($vouchers as $voucher) {
                if ($exists) {
                    continue;
                }

                $this->checkAllowedOrganizationsForProvider($provider, $voucher, $assert['organizations']);
                $products = $this->getProductsForProvider($provider, $voucher);

                $exists = array_first($products['data'], fn($item) => $product->id === $item['id']);
            }

            $assert['products']
                ? $this->assertNotNull($exists, 'The product be available to the provider.')
                : $this->assertNull($exists, 'The product must not be available to the provider.');
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

        $response = $this->postJson($this->urls['reservations'], [
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
     * @param Product $product
     * @param Identity $identity
     * @return Collection|TestResponse|array
     */
    protected function getProductOnWebshop(
        Product $product,
        Identity $identity
    ): Collection|TestResponse|array {
        $proxy = $this->makeIdentityProxy($identity);
        $headers = $this->makeApiHeaders($proxy);

        $url = sprintf($this->urls['products'] . '?organization_id=%s', $product->organization_id);

        $response = $this->getJson($url, $headers);
        $response->assertSuccessful();

        $productArr = array_first($response['data'], fn($item) => $product->id === $item['id']);
        $this->assertNotNull($productArr, 'Product not found');

        return $productArr;
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
            $response['data']['allowed_organizations'],
            fn($item) => $provider->id === $item['id']
        );

        $exist
            ? $this->assertNotNull($organizationExists, 'The provider organization should be in the list.')
            : $this->assertNull($organizationExists, 'The provider organization must not be in the list.');

        return $response;
    }

    /**
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @param array $params
     * @param array|null $assertErrors
     * @return void
     */
    protected function updateProvider(
        Fund $fund,
        FundProvider $fundProvider,
        array $params,
        ?array $assertErrors = null,
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

        if (is_null($assertErrors)) {
            $response->assertSuccessful();
        } else  {
            $response->assertJsonValidationErrors($assertErrors);
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