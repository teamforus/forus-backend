<?php

namespace Tests\Unit\Searches;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\FundProviderProduct;
use App\Models\Implementation;
use App\Models\Product;
use App\Searches\ProductSearch;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\Traits\MakesMollieConnection;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizationOffices;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestProducts;
use Throwable;

class ProductWebshopSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestProducts;
    use RollbackModelsTrait;
    use MakesMollieConnection;
    use MakesTestOrganizations;
    use MakesProductReservations;
    use MakesTestOrganizationOffices;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new ProductSearch([], Product::query());

        $this->assertQueryBuilds($search->queryWebshopSearch());
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

        $sponsor = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($sponsor);

        $organization1 = $this->makeTestOrganization($this->makeIdentity(), ['name' => "$organizationNamePart1 organization"]);
        $organization2 = $this->makeTestOrganization($this->makeIdentity(), ['name' => "$organizationNamePart2 organization"]);

        $category1 = $this->makeProductCategory(name: "$categoryNamePart1 category");
        $category2 = $this->makeProductCategory(name: "$categoryNamePart2 category");

        $product1 = $this->createProductForReservation($organization1, [$fund]);
        $product2 = $this->createProductForReservation($organization2, [$fund]);

        $product1->update([
            'name' => "$productNamePart1 product name",
            'description_text' => "$productDescriptionTextPart1 product description",
            'product_category_id' => $category1->id,
        ]);

        $product2->update([
            'name' => "$productNamePart2 product name",
            'description_text' => "$productDescriptionTextPart2 product description",
            'product_category_id' => $category2->id,
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
     * @throws Throwable
     * @return void
     */
    public function testFiltersByApprovedForFundsFilter()
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity());

        $product1 = $this->createProductForReservation($provider1, [$fund1]);
        $product2 = $this->createProductForReservation($provider2, [$fund2]);

        // assert filter return only first product if second provider is excluded from webshop
        DB::beginTransaction();
        $this->assertSearchIds([
            'fund_ids' => [$fund1->id, $fund2->id],
        ], [$product1->id, $product2->id]);

        $provider2->fund_providers()->update(['excluded' => true]);

        $this->assertSearchIds([
            'fund_ids' => [$fund1->id, $fund1->id],
        ], [$product1->id]);
        DB::rollBack();

        // assert filter return only first product if second fund provider is pending
        DB::beginTransaction();
        $this->assertSearchIds([
            'fund_ids' => [$fund1->id, $fund2->id],
        ], [$product1->id, $product2->id]);

        $provider2->fund_providers()->update(['state' => FundProvider::STATE_PENDING]);

        $this->assertSearchIds([
            'fund_ids' => [$fund1->id, $fund1->id],
        ], [$product1->id]);
        DB::rollBack();

        // assert filter return only first product if second fund provider dont allow products
        DB::beginTransaction();
        $this->assertSearchIds([
            'fund_ids' => [$fund1->id, $fund2->id],
        ], [$product1->id, $product2->id]);

        $provider2->fund_providers()->update(['allow_products' => false]);

        $this->assertSearchIds([
            'fund_ids' => [$fund1->id, $fund1->id],
        ], [$product1->id]);
        DB::rollBack();

        // assert filter return only first product if second fund not active (closed)
        DB::beginTransaction();
        $this->assertSearchIds([
            'fund_ids' => [$fund1->id, $fund2->id],
        ], [$product1->id, $product2->id]);

        $fund2->update(['state' => Fund::STATE_CLOSED]);

        $this->assertSearchIds([
            'fund_ids' => [$fund1->id, $fund1->id],
        ], [$product1->id]);
        DB::rollBack();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByInStockAndActiveFilter()
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity());

        $product1 = $this->createProductForReservation($provider1, [$fund1]);
        $product2 = $this->createProductForReservation($provider2, [$fund2]);

        // assert filter return only first product if second product is sold out
        DB::beginTransaction();
        $this->assertSearchIds([
            'fund_ids' => [$fund1->id, $fund2->id],
        ], [$product1->id, $product2->id]);

        $product2->update(['sold_out' => true]);

        $this->assertSearchIds([
            'fund_ids' => [$fund1->id, $fund1->id],
        ], [$product1->id]);
        DB::rollBack();

        // assert filter return only first product if second product is expired
        DB::beginTransaction();
        $this->assertSearchIds([
            'fund_ids' => [$fund1->id, $fund2->id],
        ], [$product1->id, $product2->id]);

        $product2->update(['expire_at' => Carbon::now()->subDay()]);

        $this->assertSearchIds([
            'fund_ids' => [$fund1->id, $fund1->id],
        ], [$product1->id]);
        DB::rollBack();
    }

    /**
     * @return void
     */
    public function testFiltersByProductCategoryId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $category1 = $this->makeProductCategory();
        $category2 = $this->makeProductCategory();

        $product1 = $this->createProductForReservation($organization, [$fund]);
        $product2 = $this->createProductForReservation($organization, [$fund]);

        $product1->update([
            'product_category_id' => $category1->id,
        ]);

        $product2->update([
            'product_category_id' => $category2->id,
        ]);

        $this->assertSearchIds(['product_category_id' => $category1->id], [$product1->id]);
        $this->assertSearchIds(['product_category_id' => $category2->id], [$product2->id]);

        $this->assertSearchIds(['product_category_ids' => [$category1->id]], [$product1->id]);
        $this->assertSearchIds(['product_category_ids' => [$category2->id]], [$product2->id]);
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

        $this->assertSearchIds(['fund_ids' => [$fund1->id]], [$product1->id]);
        $this->assertSearchIds(['fund_ids' => [$fund2->id]], [$product2->id]);
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
    }

    /**
     * @return void
     */
    public function testFiltersByOrganizationId(): void
    {
        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity());

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $product1 = $this->createProductForReservation($provider1, [$fund]);
        $product2 = $this->createProductForReservation($provider2, [$fund]);

        $this->assertSearchIds(['organization_id' => $provider1->id], [$product1->id]);
        $this->assertSearchIds(['organization_id' => $provider2->id], [$product2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByReservation(): void
    {
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $product1 = $this->createProductForReservation($provider, [$fund]);
        $product2 = $this->createProductForReservation($provider, [$fund]);
        $product2->update(['reservation_enabled' => false]);

        $this->assertSearchIds([
            'organization_id' => $provider->id,
            'reservation' => true,
        ], [$product1->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByPriceType(): void
    {
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $includeMap = [
            'free' => Product::PRICE_TYPE_FREE,
            'regular' => Product::PRICE_TYPE_REGULAR,
            'informational' => Product::PRICE_TYPE_INFORMATIONAL,
            'discount_fixed' => Product::PRICE_TYPE_DISCOUNT_FIXED,
            'discount_percentage' => Product::PRICE_TYPE_DISCOUNT_PERCENTAGE,
        ];

        $product1 = $this->createProductForReservation($provider, [$fund]);
        $product2 = $this->createProductForReservation($provider, [$fund]);

        foreach ($includeMap as $type => $priceType) {
            $product1->update(['price_type' => $priceType]);

            $product2->update([
                'price_type' => $priceType === Product::PRICE_TYPE_FREE
                    ? Product::PRICE_TYPE_REGULAR
                    : Product::PRICE_TYPE_FREE,
            ]);

            $this->assertSearchIds([
                'organization_id' => $provider->id,
                $type => true,
            ], [$product1->id]);

            $this->assertSearchIds([
                'organization_id' => $provider->id,
                'price_type' => $type,
            ], [$product1->id]);
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByPayout(): void
    {
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $product1 = $this->createProductForReservation($provider, [$fund]);
        $this->createProductForReservation($provider, [$fund]);

        $implementation = Implementation::active();

        $this->rollbackModels([
            [$implementation, $implementation->only(['voucher_payout_informational_product_id'])],
        ], function () use ($implementation, $provider, $product1) {
            $implementation->forceFill([
                'voucher_payout_informational_product_id' => $product1->id,
            ])->save();

            $this->assertSearchIds([
                'organization_id' => $provider->id,
                'payout' => true,
            ], [$product1->id]);
        });
    }

    /**
     * @return void
     */
    public function testFiltersByDistance(): void
    {
        $identity = $this->makeIdentity();
        $sponsor = $this->makeTestOrganization($identity);
        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($sponsor);

        $product1 = $this->createProductForReservation($provider1, [$fund]);
        $product2 = $this->createProductForReservation($provider2, [$fund]);

        $this->makeOrganizationOffice($provider1, [
            'postcode' => '9721 AN',
            'lat' => 53.1935717,
            'lon' => 6.5825892,
        ]);

        $this->makeOrganizationOffice($provider2, [
            'postcode' => '9721 AN',
            'lat' => 43.1935717,
            'lon' => 6.5825892,
        ]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'postcode' => '9721 AN',
            'distance' => 5,
        ], [$product1->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'postcode' => '9721 AN',
            'distance' => 10000,
        ], [$product1->id, $product2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByExtraPayment(): void
    {
        $identity = $this->makeIdentity();
        $sponsor = $this->makeTestOrganization($identity);
        $sponsor->update(['allow_provider_extra_payments' => true]);

        $fund = $this->makeTestFund($sponsor);

        // create and prepare provider - enable mollie connection
        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $product1 = $this->createProductForReservation($provider1, [$fund]);
        $fundProvider1 = $provider1->fund_providers()->first();

        $this->enableFundProviderExtraPayments($sponsor, $fund, $fundProvider1);
        $connection = $this->createPendingMollieConnection($provider1, false);
        $this->activateMollieConnection($connection);
        $this->assertConnectionActiveAndOnboarded($provider1, $connection);

        // create another provider without mollie connection and product
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity());
        $product2 = $this->createProductForReservation($provider2, [$fund]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'extra_payment' => true,
        ], [$product1->id]);

        // make mollie connection for second provider but disable extra payments for second product
        // and assert that it still not available with filter
        $fundProvider2 = $provider2->fund_providers()->first();
        $this->enableFundProviderExtraPayments($sponsor, $fund, $fundProvider2);
        $connection = $this->createPendingMollieConnection($provider2, false);
        $this->activateMollieConnection($connection);
        $this->assertConnectionActiveAndOnboarded($provider2, $connection);
        $product2->update(['reservation_extra_payments' => Product::RESERVATION_EXTRA_PAYMENT_NO]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'extra_payment' => true,
        ], [$product1->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByQr(): void
    {
        $identity = $this->makeIdentity();
        $sponsor = $this->makeTestOrganization($identity);
        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($sponsor);

        $product1 = $this->createProductForReservation($provider1, [$fund]);
        $product1->update(['qr_enabled' => true]);

        $product2 = $this->createProductForReservation($provider2, [$fund]);
        $product2->update(['qr_enabled' => false]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'qr' => true,
        ], [$product1->id]);
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
    public function testOrdersByPriceMinOrPriceMax(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $productA = $this->createProductForReservation($organization, [$fund1, $fund2]);

        foreach ($productA->fund_provider_products()->get() as $item) {
            $item->update([
                'payment_type' => FundProviderProduct::PAYMENT_TYPE_SUBSIDY,
                'amount' => $item->fund_provider->fund_id === $fund1->id ? 4 : 7,
            ]);
        }

        $productB = $this->createProductForReservation($organization, [$fund1, $fund2]);

        foreach ($productB->fund_provider_products()->get() as $item) {
            $item->update([
                'payment_type' => FundProviderProduct::PAYMENT_TYPE_SUBSIDY,
                'amount' => $item->fund_provider->fund_id === $fund1->id ? 3 : 5,
            ]);
        }

        $this->assertSearchOrder([
            'fund_ids' => [$fund1->id, $fund2->id],
            'order_by' => 'price_min',
            'order_dir' => 'asc',
        ], [$productA->id, $productB->id]);

        $this->assertSearchOrder([
            'fund_ids' => [$fund1->id, $fund2->id],
            'order_by' => 'price_min',
            'order_dir' => 'desc',
        ], [$productB->id, $productA->id]);

        $this->assertSearchOrder([
            'fund_ids' => [$fund1->id, $fund2->id],
            'order_by' => 'price_max',
            'order_dir' => 'asc',
        ], [$productA->id, $productB->id]);

        $this->assertSearchOrder([
            'fund_ids' => [$fund1->id, $fund2->id],
            'order_by' => 'price_max',
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
     * @throws Throwable
     * @return void
     */
    public function testOrdersByMostPopular(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $productA = $this->createProductForReservation($organization, [$fund]);
        $productB = $this->createProductForReservation($organization, [$fund]);

        $reservation = $this->makeReservation($fund->makeVoucher($this->makeIdentity()), $productB);
        $reservation->acceptProvider($organization->employees()->first());

        $this->assertSearchOrder([
            'fund_id' => $fund->id,
            'order_by' => 'most_popular',
            'order_dir' => 'asc',
        ], [$productA->id, $productB->id]);

        $this->assertSearchOrder([
            'fund_id' => $fund->id,
            'order_by' => 'most_popular',
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
        $actual = collect($search->queryWebshopSearch()->pluck('id')->toArray())->sort()->values()->toArray();

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
        $actual = $search->queryWebshopSearch()->pluck('id')->toArray();

        $this->assertSame($expectedIds, $actual);
    }
}
