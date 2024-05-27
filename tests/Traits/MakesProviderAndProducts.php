<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Product;
use App\Services\Forus\TestData\TestData;
use App\Traits\DoesTesting;

trait MakesProviderAndProducts
{
    use DoesTesting;
    use MakesTestIdentities;

    /**
     * @var string
     */
    protected string $apiOrganizationForProductsUrl = '/api/v1/platform/organizations/%s';

    /**
     * @var Product[]
     */
    protected array $approvedProducts = [];

    /**
     * @var Product[]
     */
    protected array $unapprovedProducts = [];

    /**
     * @var Product[]
     */
    protected array $emptyStockProducts = [];

    /**
     * @return array
     */
    public function getApprovedProducts(): array
    {
        return $this->approvedProducts;
    }

    /**
     * @return array
     */
    public function getEmptyStockProducts(): array
    {
        return $this->emptyStockProducts;
    }

    /**
     * @return array
     */
    public function getUnapprovedProducts(): array
    {
        return $this->unapprovedProducts;
    }

    /**
     * @param Fund $fund
     * @param string|null $recordTypeKeyMultiplier
     * @return void
     * @throws \Throwable
     */
    protected function makeProviderAndProducts(Fund $fund, ?string $recordTypeKeyMultiplier = null): void
    {
        $this->approvedProducts = $this->makeProducts($fund);
        $this->emptyStockProducts = $this->makeProducts($fund, 0, 'global');
        $this->unapprovedProducts = $this->makeProducts();

        if ($fund->isTypeBudget()) {
            $this->makeFundFormulaProducts($fund, $recordTypeKeyMultiplier);
        }
    }

    /**
     * @param Fund $fund
     * @param string|null $recordTypeKeyMultiplier
     * @return void
     */
    protected function makeFundFormulaProducts(Fund $fund, ?string $recordTypeKeyMultiplier): void
    {
        if (!$fund->fund_formula_products->count()) {
            array_map(function () use ($fund, $recordTypeKeyMultiplier) {
                /** @var Product $product */
                $product = array_random($this->approvedProducts);
                $fund->fund_formula_products()->updateOrCreate([
                    'product_id' => $product->id,
                ], [
                    'price' => $product->price,
                    'record_type_key_multiplier' => $recordTypeKeyMultiplier,
                ]);
            }, range(0, 3));

            $fund->load('fund_formula_products');
        }
    }

    /**
     * @param Fund|null $fund
     * @param int $stock
     * @param string $allowProducts
     * @return array
     * @throws \Throwable
     */
    protected function makeProducts(
        ?Fund $fund = null,
        int $stock = 50,
        string $allowProducts = 'individual',
    ): array {
        $testData = new TestData();
        $identity = $this->makeIdentity($this->makeUniqueEmail('provider_'));
        $provider = $testData->makeOrganizations("Provider", $identity->address)[0];

        $products = $testData->makeProducts($provider, 5, [
            'sold_out' => $stock === 0,
            'total_amount' => $stock,
            'unlimited_stock' => false,
        ]);

        $this->assertNotEmpty($products, 'Products not created');

        if ($fund) {
            /** @var FundProvider $fundProvider */
            $fundProvider = $fund->providers()->firstOrCreate([
                'state' => FundProvider::STATE_ACCEPTED,
                'allow_budget' => $fund->isTypeBudget(),
                'allow_products' => $allowProducts == 'global',
                'organization_id' => $provider->id,
            ]);

            if ($allowProducts === 'individual') {
                $this->updateProducts($fund, $fundProvider, [
                    'enable_products' => array_map(fn (Product $product) => array_merge([
                        'id' => $product->id,
                        'limit_total' => rand(1, $stock),
                        'limit_per_identity' => 1,
                    ], $fund->isTypeSubsidy() ? [
                        'amount' => $product->price,
                    ] : []), $products),
                ]);
            }
        }

        return $products;
    }

    /**
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @param array $params
     * @return void
     */
    protected function updateProducts(
        Fund $fund,
        FundProvider $fundProvider,
        array $params,
    ): void {
        $proxy = $this->makeIdentityProxy($fund->organization->identity);
        $headers = $this->makeApiHeaders($proxy);

        $url = sprintf(
            $this->apiOrganizationForProductsUrl . '/funds/%s/providers/%s',
            $fund->organization->id,
            $fund->id,
            $fundProvider->id
        );

        $response = $this->patch($url, $params, $headers);
        $response->assertSuccessful();
    }
}