<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Models\Product;
use App\Traits\DoesTesting;

trait MakesTestFundProviders
{
    use DoesTesting;
    use MakesTestProducts;
    use MakesTestIdentities;
    use MakesTestOrganizations;

    /**
     * @param Fund $fund
     * @param int $countProducts
     * @return array
     */
    protected function makeProviderAndProducts(Fund $fund, int $countProducts = 5): array
    {
        $approvedProducts = $this->makeProductsFundFund($countProducts);
        $emptyStockProducts = $this->makeProductsFundFund($countProducts);
        $unapprovedProducts = $this->makeProductsFundFund($countProducts);

        foreach ($approvedProducts as $product) {
            $this->addProductFundToFund($fund, $product, false);
        }

        foreach ($emptyStockProducts as $product) {
            $this->addProductFundToFund($fund, $product, false);
            $product->update([ 'total_amount' => 0 ]);
            $product->updateSoldOutState();
        }

        return [
            'approved' => $approvedProducts,
            'unapproved' => $unapprovedProducts,
            'empty_stock' => $emptyStockProducts,
        ];
    }

    /**
     * @param Fund $fund
     * @param Product[] $products
     * @param string|null $recordTypeKeyMultiplier
     * @return void
     */
    protected function setFundFormulaProductsForFund(
        Fund $fund,
        array $products,
        ?string $recordTypeKeyMultiplier,
    ): void {
        $data = array_map(fn (Product $product) => [
            'product_id' => $product->id,
            'record_type_key_multiplier' => $recordTypeKeyMultiplier,
        ], $products);

        $fund->updateFormulaProducts($data);
        $fund->load('fund_formula_products');
    }

    /**
     * @param int $count
     * @param float $price
     * @return Product[]
     */
    protected function makeProductsFundFund(int $count, float $price = 10): array
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail('provider_'));
        $provider = $this->makeTestProviderOrganization($identity);
        $products = $this->makeTestProducts($provider, $count, $price);

        $this->assertNotEmpty($products, 'Products not created');

        return $products;
    }

    /**
     * @param Fund $fund
     * @param Product $product
     * @param bool $approveGlobal
     * @return void
     */
    protected function addProductFundToFund(
        Fund $fund,
        Product $product,
        bool $approveGlobal,
    ): void {
        /** @var FundProvider $fundProvider */
        $fundProvider = $fund->providers()->firstOrCreate([
            'state' => FundProvider::STATE_ACCEPTED,
            'allow_budget' => $fund->isTypeBudget(),
            'allow_products' => $approveGlobal,
            'organization_id' => $product->organization_id,
        ]);

        if (!$approveGlobal || $fund->isTypeSubsidy()) {
            $this->updateProductsRequest($fund, $fundProvider, [
                'enable_products' => [[
                    'id' => $product->id,
                    'limit_total' => $product->stock_amount,
                    'limit_per_identity' => 1,
                    ...($fund->isTypeSubsidy() ? ['amount' => $product->price] : []),
                ]],
            ]);
        }
    }

    /**
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @param array $params
     * @return void
     */
    protected function updateProductsRequest(
        Fund $fund,
        FundProvider $fundProvider,
        array $params,
    ): void {
        $response = $this->patch(
            "/api/v1/platform/organizations/$fund->organization_id/funds/$fund->id/providers/$fundProvider->id",
            $params,
            $this->makeApiHeaders($this->makeIdentityProxy($fund->organization->identity))
        );

        $response->assertSuccessful();
    }

    /**
     * @param Organization $providerOrganization
     * @param Fund $fund
     * @return FundProvider
     */
    private function makeTestFundProvider(Organization $providerOrganization, Fund $fund): FundProvider
    {
        return FundProvider::create([
            'state' => FundProvider::STATE_ACCEPTED,
            'fund_id' => $fund->id,
            'allow_budget' => true,
            'organization_id' => $providerOrganization->id,
            'allow_products' => true,
        ])->refresh();
    }
}
