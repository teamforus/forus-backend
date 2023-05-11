<?php

namespace Tests\Unit;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Implementation;
use App\Models\Product;
use App\Scopes\Builders\ProductQuery;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\CreatesApplication;
use Tests\TestCase;

class ProductExclusionsTest extends TestCase
{
    use DoesTesting, DatabaseTransactions, CreatesApplication;

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testProductExclusionFromFunds(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $product = Product::first();
        $funds = $implementation->funds;
        $providers = $this->approveProductToFunds($product, $funds);

        // Product is approved for all funds
        $approvedProducts = $this->getApprovedProducts($product->id, $funds->pluck('id')->toArray());
        $this->assertTrue(count($approvedProducts) == 1 && $approvedProducts[0] == $product->id);

        foreach ($funds as $index => $fund) {
            // exclude $index product
            $product->product_exclusions()->create([
                'fund_provider_id' => $providers[$index]->id,
            ]);

            foreach ($funds as $index2 => $fund2) {
                // Assert only the excluded product is no longer approved
                $approvedProducts = $this->getApprovedProducts($product->id, $funds[$index2]->id);
                $this->assertTrue(count($approvedProducts) == ($index === $index2 ? 0 : 1));
            }

            // Product is approved as long as non excluded funds are still in the list
            $approvedProducts = $this->getApprovedProducts($product->id, $funds->pluck('id')->toArray());
            $this->assertTrue(count($approvedProducts) == 1 && $approvedProducts[0] == $product->id);

            // reset exclusions
            $product->product_exclusions()->forceDelete();
        }
    }

    /**
     * @param Product $product
     * @param Collection $funds
     * @return Collection
     */
    protected function approveProductToFunds(Product $product, Collection $funds): Collection
    {
        return $funds->map(function(Fund $fund) use ($product) {
            $provider = $fund->providers()->updateOrCreate($product->only([
                'organization_id',
            ]), [
                'state' => FundProvider::STATE_ACCEPTED,
            ]);

            if ($fund->isTypeBudget()) {
                $provider->update([
                    'allow_products' => true,
                    'allow_budget' => true,
                ]);
            } else {
                $provider->approveProducts([[
                    'id' => $product->id,
                    'amount' => $product->price,
                    'limit_total' => 10,
                    'limit_total_unlimited' => false,
                    'limit_per_identity' => 2,
                ]]);
            }

            return $provider;
        });
    }

    /**
     * @param array|int $products
     * @param array|int $funds
     * @return array
     */
    protected function getApprovedProducts(array|int $products, array|int $funds): array
    {
        $query = Product::query()->whereIn('id', (array) $products);
        $query = ProductQuery::approvedForFundsAndActiveFilter($query, (array) $funds);

        return $query->pluck('id')->toArray();
    }
}
