<?php

namespace Tests\Unit\Searches;

use App\Models\FundProvider;
use App\Models\Product;
use App\Searches\ProductSearch;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestProducts;

class ProductSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestProducts;
    use MakesTestOrganizations;
    use MakesProductReservations;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new ProductSearch([], Product::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByQuery(): void
    {
        $productNamePart1 = 'match';
        $productNamePart2 = 'other';

        $productDescriptionTextPart1 = 'second';
        $productDescriptionTextPart2 = 'third';

        $categoryNamePart1 = 'next';
        $categoryNamePart2 = 'previous';

        $organizationNamePart1 = 'first';
        $organizationNamePart2 = 'last';

        $organization1 = $this->makeTestOrganization($this->makeIdentity(), ['name' => "$organizationNamePart1 organization"]);
        $organization2 = $this->makeTestOrganization($this->makeIdentity(), ['name' => "$organizationNamePart2 organization"]);

        $category1 = $this->makeProductCategory(name: "$categoryNamePart1 category");
        $category2 = $this->makeProductCategory(name: "$categoryNamePart2 category");

        $product1 = $this->makeTestProduct($organization1, product_category_id: $category1->id);

        $product1->update([
            'name' => "$productNamePart1 product name",
            'description_text' => "$productDescriptionTextPart1 product description",
        ]);

        $product2 = $this->makeTestProduct($organization2, product_category_id: $category2->id);

        $product2->update([
            'name' => "$productNamePart2 product name",
            'description_text' => "$productDescriptionTextPart2 product description",
        ]);

        // assert by product name
        $this->assertSearchIds(['q' => $productNamePart1], [$product1->id]);
        $this->assertSearchIds(['q' => $productNamePart2], [$product2->id]);

        // assert by product description
        $this->assertSearchIds(['q' => $productDescriptionTextPart1], [$product1->id]);
        $this->assertSearchIds(['q' => $productDescriptionTextPart2], [$product2->id]);

        // assert by product category name
        $this->assertSearchIds(['q' => $categoryNamePart1], [$product1->id]);
        $this->assertSearchIds(['q' => $categoryNamePart2], [$product2->id]);

        // assert by organization name
        $this->assertSearchIds(['q' => $organizationNamePart1], [$product1->id]);
        $this->assertSearchIds(['q' => $organizationNamePart2], [$product2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByFundId(): void
    {
        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity());

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $product1 = $this->createProductForReservation($provider1, [$fund1]);
        $product2 = $this->createProductForReservation($provider2, [$fund2]);

        $this->assertSearchIds(['fund_id' => $fund1->id], [$product1->id]);
        $this->assertSearchIds(['fund_id' => $fund2->id], [$product2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByUnlimitedStock(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $product1 = $this->createProductForReservation($organization, [$fund]);
        $product1->update(['unlimited_stock' => true]);

        $product2 = $this->createProductForReservation($organization, [$fund]);
        $product2->update(['unlimited_stock' => false]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'unlimited_stock' => true,
        ], [$product1->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'unlimited_stock' => false,
        ], [$product2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByPrice(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $product1 = $this->createProductForReservation($organization, [$fund], 5);
        $product2 = $this->createProductForReservation($organization, [$fund]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'price_min' => 4,
        ], [$product1->id, $product2->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'price_min' => 8,
        ], [$product2->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'price_max' => 4,
        ], []);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'price_max' => 8,
        ], [$product1->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'price_max' => 12,
        ], [$product1->id, $product2->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'price_min' => 8,
            'price_max' => 12,
        ], [$product2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByCreatedAt(): void
    {
        $now = Carbon::now();
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $product1 = $this->createProductForReservation($organization, [$fund]);

        Carbon::setTestNow($now->copy()->addDays(5));
        $product2 = $this->createProductForReservation($organization, [$fund]);

        // assert "from date" filter
        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'from' => $now->format('Y-m-d'),
        ], [$product1->id, $product2->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'from' => $now->copy()->addDays(3)->format('Y-m-d'),
        ], [$product2->id]);

        // assert "to date" filter
        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'to' => $now->copy()->addDays(6)->format('Y-m-d'),
        ], [$product1->id, $product2->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'to' => $now->copy()->addDays(3)->format('Y-m-d'),
        ], [$product1->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'from' => $now->format('Y-m-d'),
            'to' => $now->copy()->addDays(3)->format('Y-m-d'),
        ], [$product1->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByHasReservation(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $product1 = $this->createProductForReservation($organization, [$fund]);
        $product2 = $this->createProductForReservation($organization, [$fund]);

        $this->makeReservation($this->makeTestVoucher($fund, identity: $this->makeIdentity()), $product1);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'has_reservations' => true,
        ], [$product1->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'has_reservations' => false,
        ], [$product2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByUpdatedDate(): void
    {
        $now = Carbon::now();
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $product1 = $this->createProductForReservation($organization, [$fund]);

        $this->makeProductUpdateRequest($organization, $product1, [
            ...$product1->only([
                'name', 'description', 'price', 'price_type', 'product_category_id', 'total_amount',
            ]),
            'name' => 'unique',
        ])->assertSuccessful();

        $product2 = $this->createProductForReservation($organization, [$fund]);
        Carbon::setTestNow($now->copy()->addDays(5));

        $this->makeProductUpdateRequest($organization, $product2, [
            ...$product2->only([
                'name', 'description', 'price', 'price_type', 'product_category_id', 'total_amount',
            ]),
            'name' => 'unique',
        ])->assertSuccessful();

        // assert "updated_from date" filter
        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'updated_from' => $now->format('Y-m-d'),
        ], [$product1->id, $product2->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'updated_from' => $now->copy()->addDays(3)->format('Y-m-d'),
        ], [$product2->id]);

        // assert "updated_to date" filter
        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'updated_to' => $now->copy()->addDays(6)->format('Y-m-d'),
        ], [$product1->id, $product2->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'updated_to' => $now->copy()->addDays(3)->format('Y-m-d'),
        ], [$product1->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'updated_from' => $now->format('Y-m-d'),
            'updated_to' => $now->copy()->addDays(3)->format('Y-m-d'),
        ], [$product1->id]);
    }

    /**
     * @return void
     */
    public function testFiltersBySource(): void
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($sponsor);
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());

        $productSponsor = $this->createProductForReservation($provider, [$fund]);
        $productSponsor->update(['sponsor_organization_id' => $sponsor->id]);

        $productProvider = $this->createProductForReservation($provider, [$fund]);
        $productTrashed = $this->createProductForReservation($provider, [$fund]);
        $productTrashed->delete();

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'source' => 'provider',
        ], [$productProvider->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'source' => 'sponsor',
        ], [$productSponsor->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'source' => 'archive',
        ], [$productTrashed->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByState(): void
    {
        $productName = 'same_product_name_for_this_products';

        $sponsor = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($sponsor);

        $providerWithoutFund = $this->makeTestProviderOrganization($this->makeIdentity());
        $providerApproved = $this->makeTestProviderOrganization($this->makeIdentity());
        $providerPending = $this->makeTestProviderOrganization($this->makeIdentity());

        $productWithoutFund = $this->makeTestProduct($providerWithoutFund);
        $productWithoutFund->update(['name' => $productName]);

        $productApproved = $this->createProductForReservation($providerApproved, [$fund]);
        $productApproved->update(['name' => $productName]);

        $productPending = $this->makeTestProduct($providerPending);
        $productPending->update(['name' => $productName]);

        $productPending->fund_providers()->firstOrCreate([
            'organization_id' => $providerPending->id,
            'fund_id' => $fund->id,
            'state' => FundProvider::STATE_PENDING,
            'allow_budget' => true,
            'allow_products' => true,
        ]);

        $this->assertSearchIds([
            'fund_ids' => [$fund->id],
            'state' => 'approved',
        ], [$productApproved->id]);

        // filter by same name but assert that only not approved products returned
        $this->assertSearchIds([
            'q' => $productName,
            'fund_ids' => [$fund->id],
            'state' => 'pending',
        ], [$productPending->id, $productWithoutFund->id]);
    }

    /**
     * @return void
     */
    public function testOrdersById(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $productA = $this->createProductForReservation($organization, [$fund]);
        $productB = $this->createProductForReservation($organization, [$fund]);

        $this->assertSearchOrder([
            'fund_id' => $fund->id,
            'order_by' => 'id',
            'order_dir' => 'asc',
        ], [$productA->id, $productB->id]);

        $this->assertSearchOrder([
            'fund_id' => $fund->id,
            'order_by' => 'id',
            'order_dir' => 'desc',
        ], [$productB->id, $productA->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByName(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $productA = $this->createProductForReservation($organization, [$fund]);
        $productA->update(['name' => 'A name']);

        $productB = $this->createProductForReservation($organization, [$fund]);
        $productB->update(['name' => 'B name']);

        $this->assertSearchOrder([
            'fund_id' => $fund->id,
            'order_by' => 'name',
            'order_dir' => 'asc',
        ], [$productA->id, $productB->id]);

        $this->assertSearchOrder([
            'fund_id' => $fund->id,
            'order_by' => 'name',
            'order_dir' => 'desc',
        ], [$productB->id, $productA->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByPrice(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $productA = $this->createProductForReservation($organization, [$fund], 5);
        $productB = $this->createProductForReservation($organization, [$fund]);

        $this->assertSearchOrder([
            'fund_id' => $fund->id,
            'order_by' => 'price',
            'order_dir' => 'asc',
        ], [$productA->id, $productB->id]);

        $this->assertSearchOrder([
            'fund_id' => $fund->id,
            'order_by' => 'price',
            'order_dir' => 'desc',
        ], [$productB->id, $productA->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByExpireAt(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $productA = $this->createProductForReservation($organization, [$fund]);
        $productA->update(['expire_at' => Carbon::now()->addDays(5)]);

        $productB = $this->createProductForReservation($organization, [$fund]);
        $productB->update(['expire_at' => Carbon::now()->addDays(10)]);

        $this->assertSearchOrder([
            'fund_id' => $fund->id,
            'order_by' => 'expire_at',
            'order_dir' => 'asc',
        ], [$productA->id, $productB->id]);

        $this->assertSearchOrder([
            'fund_id' => $fund->id,
            'order_by' => 'expire_at',
            'order_dir' => 'desc',
        ], [$productB->id, $productA->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByCreatedAt(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $productA = $this->createProductForReservation($organization, [$fund]);

        Carbon::setTestNow(Carbon::now()->addDays(5));
        $productB = $this->createProductForReservation($organization, [$fund]);

        $this->assertSearchOrder([
            'fund_id' => $fund->id,
            'order_by' => 'created_at',
            'order_dir' => 'asc',
        ], [$productA->id, $productB->id]);

        $this->assertSearchOrder([
            'fund_id' => $fund->id,
            'order_by' => 'created_at',
            'order_dir' => 'desc',
        ], [$productB->id, $productA->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByLastMonitoredChangeAt(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $productA = $this->createProductForReservation($organization, [$fund]);

        $this->makeProductUpdateRequest($organization, $productA, [
            ...$productA->only([
                'name', 'description', 'price', 'price_type', 'product_category_id', 'total_amount',
            ]),
            'name' => 'unique',
        ])->assertSuccessful();

        $productB = $this->createProductForReservation($organization, [$fund]);
        Carbon::setTestNow(Carbon::now()->addDays(5));

        $this->makeProductUpdateRequest($organization, $productB, [
            ...$productB->only([
                'name', 'description', 'price', 'price_type', 'product_category_id', 'total_amount',
            ]),
            'name' => 'unique',
        ])->assertSuccessful();

        $this->assertSearchOrder([
            'fund_id' => $fund->id,
            'order_by' => 'last_monitored_change_at',
            'order_dir' => 'asc',
        ], [$productA->id, $productB->id]);

        $this->assertSearchOrder([
            'fund_id' => $fund->id,
            'order_by' => 'last_monitored_change_at',
            'order_dir' => 'desc',
        ], [$productB->id, $productA->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByStockAmount(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $productA = $this->createProductForReservation($organization, [$fund]);
        $productB = $this->createProductForReservation($organization, [$fund]);

        $this->makeReservation($this->makeTestVoucher($fund, identity: $this->makeIdentity()), $productA);

        $this->assertSearchOrder([
            'fund_id' => $fund->id,
            'order_by' => 'stock_amount',
            'order_dir' => 'asc',
        ], [$productA->id, $productB->id]);

        $this->assertSearchOrder([
            'fund_id' => $fund->id,
            'order_by' => 'stock_amount',
            'order_dir' => 'desc',
        ], [$productB->id, $productA->id]);
    }

    /**
     * @param array $filters
     * @return ProductSearch
     */
    private function makeSearch(array $filters): ProductSearch
    {
        return new ProductSearch($filters, Product::query());
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

    /**
     * @param array $filters
     * @param array $expectedIds
     * @return void
     */
    private function assertSearchOrder(array $filters, array $expectedIds): void
    {
        $search = $this->makeSearch($filters);
        $actual = $search->query()->pluck('id')->toArray();

        $this->assertSame($expectedIds, $actual);
    }
}
