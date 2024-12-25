<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Scopes\Builders\ProductQuery;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestVouchers;
use Tests\Traits\TestsReservations;

class ProductExclusionsTest extends TestCase
{
    use MakesTestFunds;
    use MakesTestVouchers;
    use TestsReservations;
    use AssertsSentEmails;
    use DatabaseTransactions;
    use MakesProductReservations;

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testProductExclusionByProviderFromFund(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        // One subsidy and one budget fund with 2 products and 2 providers each
        $this->makeProviderAndProducts($this->makeTestFund($organization), 2);
        $this->makeProviderAndProducts($this->makeTestSubsidyFund($organization), 2);

        foreach ($organization->funds as $fund) {
            $this->assertEquals(2, $fund->providers->count());

            $this->assertCount(2, $this->getApprovedProducts(
                $fund->providers[0]->products->pluck('id')->toArray(),
                $fund->id,
            ));

            // exclude first product from the fund
            $fund->providers[0]->products[0]->product_exclusions()->create([
                'fund_provider_id' => $fund->providers[0]->id,
            ]);

            // the second one should still be available
            $this->assertCount(1, $this->getApprovedProducts(
                $fund->providers[0]->products->pluck('id')->toArray(),
                $fund->id,
            ));
        }
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testProviderProductsExclusionBySponsorOnWebshop(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->assertNotNull($organization);
        $this->makeProviderAndProducts($this->makeTestFund($organization), 2);
        $this->makeProviderAndProducts($this->makeTestSubsidyFund($organization), 2);

        foreach ($organization->funds as $fund) {
            $this->assertEquals(2, $fund->providers->count());

            // Product is approved for all funds
            $approvedProducts = $this->getApprovedProducts(
                $fund->providers[0]->products->pluck('id')->toArray(),
                $fund->id,
            );

            $this->assertCount(2, $approvedProducts);

            // exclude $index product
            $fund->providers[0]->update([
                'excluded' => true,
            ]);

            // Product is approved for all funds
            $approvedProducts = $this->getApprovedProducts(
                $fund->providers[0]->products->pluck('id')->toArray(),
                $fund->id,
            );

            $this->assertCount(0, $approvedProducts);
        }
    }

    /**
     * @param array|int $products
     * @param array|int $funds
     * @return array
     */
    protected function getApprovedProducts(array|int $products, array|int $funds): array
    {
        $query = Product::whereIn('id', (array) $products);
        $query = ProductQuery::approvedForFundsAndActiveFilter($query, (array) $funds);

        return $query->pluck('id')->toArray();
    }
}
