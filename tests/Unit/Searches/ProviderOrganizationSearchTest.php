<?php

namespace Tests\Unit\Searches;

use App\Models\FundProvider;
use App\Models\Organization;
use App\Searches\OrganizationSearch;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;
use Tests\Traits\MakesTestBusinessType;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizationOffices;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class ProviderOrganizationSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestBusinessType;
    use MakesTestFundRequests;
    use MakesTestOrganizations;
    use MakesTestOrganizationOffices;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new OrganizationSearch([], Organization::query());

        $this->assertQueryBuilds($search->queryProviders());
    }

    /**
     * @return void
     */
    public function testFiltersByQuery(): void
    {
        $identity = $this->makeIdentity();
        $sponsor = $this->makeTestOrganization($identity);

        $namePart1 = 'match';
        $namePart2 = 'other';

        $emailPart1 = 'something_un';
        $emailPart2 = 'any_un';

        $phonePart1 = '22233';
        $phonePart2 = '55566';

        $websitePart1 = 'forus';
        $websitePart2 = 'dashboard';

        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity(), [
            'name' => "$namePart1 name",
            'email' => $this->makeUniqueEmail($emailPart1),
            'email_public' => true,
            'phone' => "{$phonePart1}444",
            'phone_public' => true,
            'website' => "https://$websitePart1.example.com",
            'website_public' => true,
        ]);

        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity(), [
            'name' => "$namePart2 name",
            'email' => $this->makeUniqueEmail($emailPart2),
            'email_public' => true,
            'phone' => "{$phonePart2}444",
            'phone_public' => true,
            'website' => "https://$websitePart2.example.com",
            'website_public' => true,
        ]);

        $fund = $this->makeTestFund($sponsor);
        $this->makeTestFundProvider($provider1, $fund);
        $this->makeTestFundProvider($provider2, $fund);

        $this->assertSearchIds(['q' => $namePart1], [$provider1->id]);
        $this->assertSearchIds(['q' => $namePart2], [$provider2->id]);

        $this->assertSearchIds(['q' => $emailPart1], [$provider1->id]);
        $this->assertSearchIds(['q' => $emailPart2], [$provider2->id]);

        $this->assertSearchIds(['q' => $phonePart1], [$provider1->id]);
        $this->assertSearchIds(['q' => $phonePart2], [$provider2->id]);

        $this->assertSearchIds(['q' => $websitePart1], [$provider1->id]);
        $this->assertSearchIds(['q' => $websitePart2], [$provider2->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByQueryOfficeAddress(): void
    {
        $addressPart1 = 'primary';
        $addressPart2 = 'secondary';

        $identity = $this->makeIdentity();
        $sponsor = $this->makeTestOrganization($identity);
        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($sponsor);
        $this->makeTestFundProvider($provider1, $fund);
        $this->makeTestFundProvider($provider2, $fund);

        $this->makeOrganizationOffice($provider1, ['address' => "$addressPart1 office address"]);
        $this->makeOrganizationOffice($provider2, ['address' => "$addressPart2 office address"]);

        $this->assertSearchIds(['q' => $addressPart1], [$provider1->id]);
        $this->assertSearchIds(['q' => $addressPart2], [$provider2->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByQueryBusinessTypeName(): void
    {
        $typePart1 = 'primary';
        $typePart2 = 'secondary';

        $identity = $this->makeIdentity();
        $sponsor = $this->makeTestOrganization($identity);
        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($sponsor);
        $this->makeTestFundProvider($provider1, $fund);
        $this->makeTestFundProvider($provider2, $fund);

        $businessType1 = $this->makeTestBusinessType("$typePart1 type");
        $businessType2 = $this->makeTestBusinessType("$typePart2 type");

        $provider1->update(['business_type_id' => $businessType1->id]);
        $provider2->update(['business_type_id' => $businessType2->id]);

        $this->assertSearchIds(['q' => $typePart1], [$provider1->id]);
        $this->assertSearchIds(['q' => $typePart2], [$provider2->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersById(): void
    {
        $identity = $this->makeIdentity();
        $sponsor = $this->makeTestOrganization($identity);
        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($sponsor);
        $this->makeTestFundProvider($provider1, $fund);
        $this->makeTestFundProvider($provider2, $fund);

        $this->assertSearchIds(['organization_id' => $provider1->id], [$provider1->id]);
        $this->assertSearchIds(['organization_id' => $provider2->id], [$provider2->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByFundId(): void
    {
        $identity = $this->makeIdentity();
        $sponsor = $this->makeTestOrganization($identity);
        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($sponsor);
        $fund2 = $this->makeTestFund($sponsor);
        $fundProvider1 = $this->makeTestFundProvider($provider1, $fund1);
        $this->makeTestFundProvider($provider2, $fund2);

        $this->assertSearchIds(['fund_id' => $fund1->id], [$provider1->id]);
        $this->assertSearchIds(['fund_id' => $fund2->id], [$provider2->id]);

        $this->assertSearchIds(['fund_ids' => [$fund1->id]], [$provider1->id]);
        $this->assertSearchIds(['fund_ids' => [$fund2->id]], [$provider2->id]);
        $this->assertSearchIds(['fund_ids' => [$fund1->id, $fund2->id]], [$provider1->id, $provider2->id]);

        // reject first provider and accept visible only approved provider
        $fundProvider1->update(['state' => FundProvider::STATE_REJECTED]);
        $this->assertSearchIds(['fund_ids' => [$fund1->id, $fund2->id]], [$provider2->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByBusinessTypeIds(): void
    {
        $identity = $this->makeIdentity();
        $sponsor = $this->makeTestOrganization($identity);
        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($sponsor);
        $this->makeTestFundProvider($provider1, $fund);
        $this->makeTestFundProvider($provider2, $fund);

        $businessType1 = $this->makeTestBusinessType('type');
        $businessType2 = $this->makeTestBusinessType('type2');

        $provider1->update(['business_type_id' => $businessType1->id]);
        $provider2->update(['business_type_id' => $businessType2->id]);

        $this->assertSearchIds(['business_type_id' => $businessType1->id], [$provider1->id]);
        $this->assertSearchIds(['business_type_id' => $businessType2->id], [$provider2->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByProductCategoryIds(): void
    {
        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));

        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity());

        $category1 = $this->makeProductCategory();
        $product1 = $this->makeTestProducts($provider1)[0];
        $this->addProductToFund($fund, $product1, false);
        $product1->update(['product_category_id' => $category1->id]);

        $category2 = $this->makeProductCategory();
        $product2 = $this->makeTestProducts($provider2)[0];
        $this->addProductToFund($fund, $product2, false);
        $product2->update(['product_category_id' => $category2->id]);

        $this->assertSearchIds(['product_category_id' => $category1->id], [$provider1->id]);
        $this->assertSearchIds(['product_category_id' => $category2->id], [$provider2->id]);

        $this->assertSearchIds(['product_category_ids' => [$category1->id]], [$provider1->id]);
        $this->assertSearchIds(['product_category_ids' => [$category2->id]], [$provider2->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByDistance(): void
    {
        $identity = $this->makeIdentity();
        $sponsor = $this->makeTestOrganization($identity);
        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($sponsor);
        $this->makeTestFundProvider($provider1, $fund);
        $this->makeTestFundProvider($provider2, $fund);

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
        ], [$provider1->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'postcode' => '9721 AN',
            'distance' => 10000,
        ], [$provider1->id, $provider2->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByCreatedAt(): void
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity());

        $olderProvider = $this->makeTestOrganization($this->makeIdentity());

        Carbon::setTestNow(now()->addDays(5));
        $newerProvider = $this->makeTestOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($sponsor);
        $this->makeTestFundProvider($olderProvider, $fund);
        $this->makeTestFundProvider($newerProvider, $fund);

        $this->assertSearchOrder([
            'fund_id' => $fund->id,
            'order_by' => 'created_at',
            'order_dir' => 'asc',
        ], [$olderProvider->id, $newerProvider->id]);

        $this->assertSearchOrder([
            'fund_id' => $fund->id,
            'order_by' => 'created_at',
            'order_dir' => 'desc',
        ], [$newerProvider->id, $olderProvider->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByName(): void
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity());

        $olderProvider = $this->makeTestOrganization($this->makeIdentity(), ['name' => 'A provider']);
        $newerProvider = $this->makeTestOrganization($this->makeIdentity(), ['name' => 'B provider']);

        $fund = $this->makeTestFund($sponsor);
        $this->makeTestFundProvider($olderProvider, $fund);
        $this->makeTestFundProvider($newerProvider, $fund);

        $this->assertSearchOrder([
            'fund_id' => $fund->id,
            'order_by' => 'name',
            'order_dir' => 'asc',
        ], [$olderProvider->id, $newerProvider->id]);

        $this->assertSearchOrder([
            'fund_id' => $fund->id,
            'order_by' => 'name',
            'order_dir' => 'desc',
        ], [$newerProvider->id, $olderProvider->id]);
    }

    /**
     * @param array $filters
     * @return OrganizationSearch
     */
    private function makeSearch(array $filters): OrganizationSearch
    {
        return new OrganizationSearch($filters, Organization::query());
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
        $actual = collect($search->queryProviders()->pluck('id')->toArray())->sort()->values()->toArray();

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
        $actual = $search->queryProviders()->pluck('id')->toArray();

        $this->assertSame($expectedIds, $actual);
    }
}
