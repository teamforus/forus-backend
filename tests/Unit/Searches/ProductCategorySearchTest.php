<?php

namespace Tests\Unit\Searches;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\ProductCategory;
use App\Searches\ProductCategorySearch;
use App\Traits\DoesTesting;
use Tests\Traits\MakesTestFunds;

class ProductCategorySearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new ProductCategorySearch([], ProductCategory::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByQuery(): void
    {
        $namePart1 = 'first';
        $namePart2 = 'last';

        $category1 = $this->makeProductCategory(name: "$namePart1 category");
        $category2 = $this->makeProductCategory(name: "$namePart2 category");

        $this->assertSearchIds(['q' => $namePart1], [$category1->id]);
        $this->assertSearchIds(['q' => $namePart2], [$category2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByParentId(): void
    {
        $parent = $this->makeProductCategory();
        $category1 = $this->makeProductCategory(parentId: $parent->id);
        $this->makeProductCategory();

        $this->assertSearchIds(['parent_id' => $parent->id], [$category1->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByUsed(): void
    {
        // disabled other funds
        Implementation::activeFundsQuery()->update(['state' => Fund::STATE_PAUSED]);

        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));

        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity());

        $category1 = $this->makeProductCategory();
        $product1 = $this->makeTestProducts($provider1)[0];
        $this->addProductToFund($fund, $product1, false);
        $product1->update(['product_category_id' => $category1->id]);

        $category2 = $this->makeProductCategory();

        // assert only first category are visible as it used for product
        $this->assertSearchIds(['used' => true], [$category1->id]);

        $product2 = $this->makeTestProducts($provider2)[0];
        $this->addProductToFund($fund, $product2, false);
        $product2->update(['product_category_id' => $category2->id]);

        // assert both categories are visible
        $this->assertSearchIds(['used' => true], [$category1->id, $category2->id]);
    }

    /**
     * @param array $filters
     * @return ProductCategorySearch
     */
    private function makeSearch(array $filters): ProductCategorySearch
    {
        return new ProductCategorySearch($filters, ProductCategory::query());
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @return void
     */
    private function assertSearchIds(array $filters, array $expectedIds): void
    {
        $expected = collect($expectedIds)->sort()->values()->toArray();
        $search = $this->makeSearch($filters);
        $actual = collect($search->query()->pluck('id')->toArray())->sort()->values()->toArray();

        $this->assertSame($expected, $actual);
    }
}
