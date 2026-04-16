<?php

namespace Tests\Unit\Searches;

use App\Models\FundProvider;
use App\Models\Identity;
use App\Models\Organization;
use App\Searches\FundProviderSearch;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestBusinessType;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class FundProviderSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestBusinessType;
    use MakesTestOrganizations;
    use MakesProductReservations;

    protected Identity $identity;
    protected Organization $sponsor;
    protected Organization $otherSponsor;
    protected Organization $provider;
    protected Organization $otherProvider;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->identity = $this->makeIdentity();

        $this->sponsor = $this->makeTestOrganization($this->identity);
        $this->makeTestFund($this->sponsor);
        $this->provider = $this->makeTestProviderOrganization($this->makeIdentity());

        $this->otherSponsor = $this->makeTestOrganization($this->makeIdentity());
        $this->makeTestFund($this->otherSponsor);
        $this->otherProvider = $this->makeTestProviderOrganization($this->makeIdentity());
    }

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);

        $search = new FundProviderSearch([], FundProvider::query(), $organization);

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByOrganizationId(): void
    {
        // prepare two funds for sponsor
        $fund1Sponsor = $this->sponsor->funds()->first();
        $fund2Sponsor = $this->makeTestFund($this->sponsor);

        // make two fund providers for provider and prepared funds
        $fundProviderFund1Sponsor = $this->makeTestFundProvider($this->provider, $fund1Sponsor);
        $fundProviderFund2Sponsor = $this->makeTestFundProvider($this->provider, $fund2Sponsor);

        // assert filter by provider using sponsor organization fund providers are visible
        $this->assertSearchIds(['organization_id' => $this->provider->id], [
            $fundProviderFund1Sponsor->id,
            $fundProviderFund2Sponsor->id,
        ], $this->sponsor);

        // assert filter by other provider using different sponsors gives empty results
        $this->assertSearchIds(['organization_id' => $this->otherProvider->id], [], $this->sponsor);
        $this->assertSearchIds(['organization_id' => $this->otherProvider->id], [], $this->otherSponsor);

        // prepare fund provider for other provider using sponsor and assert only this fund provider visible
        $fundSponsor = $this->sponsor->funds()->first();
        $fundProvider = $this->makeTestFundProvider($this->otherProvider, $fundSponsor);
        $this->assertSearchIds(['organization_id' => $this->otherProvider->id], [$fundProvider->id], $this->sponsor);

        // prepare fund provider for other provider using other sponsor and assert only this fund provider visible
        $fundOtherSponsor = $this->otherSponsor->funds()->first();
        $fundProviderOtherSponsor = $this->makeTestFundProvider($this->otherProvider, $fundOtherSponsor);

        $this->assertSearchIds(['organization_id' => $this->otherProvider->id], [
            $fundProviderOtherSponsor->id,
        ], $this->otherSponsor);
    }

    /**
     * @return void
     */
    public function testFiltersByFundId(): void
    {
        // prepare two funds for sponsor
        $fund1Sponsor = $this->sponsor->funds()->first();
        $fund2Sponsor = $this->makeTestFund($this->sponsor);

        // make two fund providers for provider and prepared funds
        $fundProviderFund1 = $this->makeTestFundProvider($this->provider, $fund1Sponsor);
        $fundProviderFund2 = $this->makeTestFundProvider($this->provider, $fund2Sponsor);

        $this->assertSearchIds(['fund_id' => $fund1Sponsor->id], [$fundProviderFund1->id], $this->sponsor);
        $this->assertSearchIds(['fund_ids' => [$fund1Sponsor->id]], [$fundProviderFund1->id], $this->sponsor);
        $this->assertSearchIds(['fund_id' => $fund2Sponsor->id], [$fundProviderFund2->id], $this->sponsor);
        $this->assertSearchIds(['fund_ids' => [$fund2Sponsor->id]], [$fundProviderFund2->id], $this->sponsor);
    }

    /**
     * @return void
     */
    public function testFiltersIfFundArchived(): void
    {
        // prepare two funds for sponsor
        $fund1Sponsor = $this->sponsor->funds()->first();
        $fund2Sponsor = $this->makeTestFund($this->sponsor);

        // make two fund providers for provider and prepared funds
        $fundProviderFund1 = $this->makeTestFundProvider($this->provider, $fund1Sponsor);
        $fundProviderFund2 = $this->makeTestFundProvider($this->provider, $fund2Sponsor);

        $this->assertSearchIds(['fund_id' => $fund1Sponsor->id], [$fundProviderFund1->id], $this->sponsor);
        $this->assertSearchIds(['fund_id' => $fund2Sponsor->id], [$fundProviderFund2->id], $this->sponsor);

        // archive fund and assert empty results with filter by archived fund
        $fund1Sponsor->archive($this->sponsor->employees()->first());
        $this->assertSearchIds(['fund_id' => $fund1Sponsor->id], [], $this->sponsor);

        // assert only not archived funds visible with empty filters
        $this->assertSearchIds([], [$fundProviderFund2->id], $this->sponsor);
    }

    /**
     * @return void
     */
    public function testFiltersByQueryMatchesFundProviderByOrganizationName(): void
    {
        $namePart1 = 'match';
        $namePart2 = 'other';

        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity(), ['name' => "$namePart1 name"]);
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity(), ['name' => "$namePart2 name"]);

        $fund = $this->sponsor->funds()->first();

        $fundProvider1 = $this->makeTestFundProvider($provider1, $fund);
        $fundProvider2 = $this->makeTestFundProvider($provider2, $fund);

        $this->assertSearchIds(['q' => $namePart1], [$fundProvider1->id], $this->sponsor);
        $this->assertSearchIds(['q' => $namePart2], [$fundProvider2->id], $this->sponsor);
    }

    /**
     * @return void
     */
    public function testFiltersByQueryMatchesOfficeByOrganizationContact(): void
    {
        $emailPart1 = 'unique';
        $emailPart2 = 'other';

        $phonePart1 = '222333';
        $phonePart2 = '555666';

        $kvkPart1 = '9999';
        $kvkPart2 = '7777';

        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity(), [
            'email' => $this->makeUniqueEmail($emailPart1),
            'email_public' => true,
            'phone' => "{$phonePart1}444",
            'phone_public' => true,
            'kvk' => "{$kvkPart1}8888",
        ]);

        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity(), [
            'email' => $this->makeUniqueEmail($emailPart2),
            'email_public' => true,
            'phone' => "{$phonePart2}444",
            'phone_public' => true,
            'kvk' => "{$kvkPart2}8888",
        ]);

        $fund = $this->sponsor->funds()->first();

        $fundProvider1 = $this->makeTestFundProvider($provider1, $fund);
        $fundProvider2 = $this->makeTestFundProvider($provider2, $fund);

        // by email
        $this->assertSearchIds(['q' => $emailPart1], [$fundProvider1->id], $this->sponsor);
        $this->assertSearchIds(['q' => $emailPart2], [$fundProvider2->id], $this->sponsor);

        // by phone
        $this->assertSearchIds(['q' => $phonePart1], [$fundProvider1->id], $this->sponsor);
        $this->assertSearchIds(['q' => $phonePart2], [$fundProvider2->id], $this->sponsor);

        // by kvk
        $this->assertSearchIds(['q' => $kvkPart1], [$fundProvider1->id], $this->sponsor);
        $this->assertSearchIds(['q' => $kvkPart2], [$fundProvider2->id], $this->sponsor);
    }

    /**
     * @return void
     */
    public function testFiltersByQueryMatchesFundProviderByFundName(): void
    {
        $namePart1 = 'unique';
        $namePart2 = 'other';

        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($this->sponsor, ['name' => "$namePart1 fund name"]);
        $fund2 = $this->makeTestFund($this->sponsor, ['name' => "$namePart2 fund name"]);

        $fundProvider1 = $this->makeTestFundProvider($provider1, $fund1);
        $fundProvider2 = $this->makeTestFundProvider($provider1, $fund2);

        $this->assertSearchIds(['q' => $namePart1], [$fundProvider1->id], $this->sponsor);
        $this->assertSearchIds(['q' => $namePart2], [$fundProvider2->id], $this->sponsor);
    }

    /**
     * @return void
     */
    public function testFiltersByImplementationId(): void
    {
        $implementation1 = $this->makeTestImplementation($this->sponsor);
        $implementation2 = $this->makeTestImplementation($this->sponsor);

        $fund1 = $this->makeTestFund($this->sponsor, implementation: $implementation1);
        $fund2 = $this->makeTestFund($this->sponsor, implementation: $implementation2);

        $fundProvider1 = $this->makeTestFundProvider($this->provider, $fund1);
        $fundProvider2 = $this->makeTestFundProvider($this->provider, $fund2);

        $this->assertSearchIds([], [$fundProvider1->id, $fundProvider2->id], $this->sponsor);
        $this->assertSearchIds(['implementation_id' => $implementation1->id], [$fundProvider1->id], $this->sponsor);
        $this->assertSearchIds(['implementation_id' => $implementation2->id], [$fundProvider2->id], $this->sponsor);
    }

    /**
     * @return void
     */
    public function testFiltersByState(): void
    {
        $fund1 = $this->makeTestFund($this->sponsor);
        $fund2 = $this->makeTestFund($this->sponsor);

        $fundProvider1 = $this->makeTestFundProvider($this->provider, $fund1);
        $fundProvider2 = $this->makeTestFundProvider($this->provider, $fund2);

        $this->assertSearchIds([], [$fundProvider1->id, $fundProvider2->id], $this->sponsor);
        $this->assertSearchIds(['state' => FundProvider::STATE_ACCEPTED], [$fundProvider1->id, $fundProvider2->id], $this->sponsor);
        $this->assertSearchIds(['state' => FundProvider::STATE_REJECTED], [], $this->sponsor);

        $fundProvider2->update(['state' => FundProvider::STATE_REJECTED]);
        $this->assertSearchIds(['state' => FundProvider::STATE_ACCEPTED], [$fundProvider1->id], $this->sponsor);
        $this->assertSearchIds(['state' => FundProvider::STATE_REJECTED], [$fundProvider2->id], $this->sponsor);
    }

    /**
     * @return void
     */
    public function testFiltersByAllowExtraPayment(): void
    {
        $fund1 = $this->makeTestFund($this->sponsor);
        $fund2 = $this->makeTestFund($this->sponsor);

        $fundProvider1 = $this->makeTestFundProvider($this->provider, $fund1);
        $fundProvider2 = $this->makeTestFundProvider($this->provider, $fund2);

        $this->assertSearchIds([], [$fundProvider1->id, $fundProvider2->id], $this->sponsor);
        $this->assertSearchIds(['allow_extra_payments' => false], [$fundProvider1->id, $fundProvider2->id], $this->sponsor);
        $this->assertSearchIds(['allow_extra_payments' => true], [], $this->sponsor);

        $fundProvider2->update(['allow_extra_payments' => true]);
        $this->assertSearchIds(['allow_extra_payments' => false], [$fundProvider1->id], $this->sponsor);
        $this->assertSearchIds(['allow_extra_payments' => true], [$fundProvider2->id], $this->sponsor);
    }

    /**
     * @return void
     */
    public function testFiltersByAllowBudget(): void
    {
        $fund1 = $this->makeTestFund($this->sponsor);
        $fund2 = $this->makeTestFund($this->sponsor);

        $fundProvider1 = $this->makeTestFundProvider($this->provider, $fund1);
        $fundProvider2 = $this->makeTestFundProvider($this->provider, $fund2);

        $this->assertSearchIds([], [$fundProvider1->id, $fundProvider2->id], $this->sponsor);
        $this->assertSearchIds(['allow_budget' => true], [$fundProvider1->id, $fundProvider2->id], $this->sponsor);
        $this->assertSearchIds(['allow_budget' => false], [], $this->sponsor);

        $fundProvider2->update(['allow_budget' => false]);
        $this->assertSearchIds(['allow_budget' => true], [$fundProvider1->id], $this->sponsor);
        $this->assertSearchIds(['allow_budget' => false], [$fundProvider2->id], $this->sponsor);
    }

    /**
     * @return void
     */
    public function testFiltersByAllowProducts(): void
    {
        $fund1 = $this->makeTestFund($this->sponsor);
        $fund2 = $this->makeTestFund($this->sponsor);

        // create fund providers with allow_products is true by default
        $fundProvider1 = $this->makeTestFundProvider($this->provider, $fund1);
        $fundProvider2 = $this->makeTestFundProvider($this->provider, $fund2);

        // create product
        $this->createProductForReservation($this->provider, [$fund1]);
        $product2 = $this->createProductForReservation($this->provider, [$fund2]);

        // assert that both fund providers without filters are visible
        $this->assertSearchIds([], [$fundProvider1->id, $fundProvider2->id], $this->sponsor);

        // assert that both fund providers with filter allow_products visibility
        $this->assertSearchIds(['allow_products' => true], [$fundProvider1->id, $fundProvider2->id], $this->sponsor);
        $this->assertSearchIds(['allow_products' => false], [], $this->sponsor);

        // update allow_products to false for second fund provider
        $fundProvider2->update(['allow_products' => false]);

        // assert that both fund providers are visible even if second fund provider has allow_products = false,
        // but fund provider has product
        $this->assertSearchIds(['allow_products' => true], [$fundProvider1->id, $fundProvider2->id], $this->sponsor);
        $this->assertSearchIds(['allow_products' => false], [$fundProvider2->id], $this->sponsor);

        // delete product from fund provider products and assert filter by allow_products = true
        // return only first provider
        $product2->fund_provider_products()->delete();
        $this->assertSearchIds(['allow_products' => true], [$fundProvider1->id], $this->sponsor);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByHasProducts(): void
    {
        $fund1 = $this->makeTestFund($this->sponsor);
        $fund2 = $this->makeTestFund($this->sponsor);

        // create fund providers with allow_products is true by default
        $fundProvider1 = $this->makeTestFundProvider($this->provider, $fund1);
        $fundProvider2 = $this->makeTestFundProvider($this->otherProvider, $fund2);

        // create products
        $product1 = $this->createProductForReservation($this->provider, [$fund1]);
        $this->createProductForReservation($this->otherProvider, [$fund2]);

        // assert that both fund providers without filters are visible
        $this->assertSearchIds([], [$fundProvider1->id, $fundProvider2->id], $this->sponsor);

        // assert that both fund providers with filter allow_products visibility
        $this->assertSearchIds(['has_products' => true], [$fundProvider1->id, $fundProvider2->id], $this->sponsor);
        $this->assertSearchIds(['has_products' => false], [], $this->sponsor);

        // make first product expired
        DB::beginTransaction();
        $product1->update(['expire_at' => Carbon::now()->subDay()]);
        $this->assertSearchIds(['has_products' => true], [$fundProvider2->id], $this->sponsor);
        DB::rollBack();

        $this->assertSearchIds(['has_products' => true], [$fundProvider1->id, $fundProvider2->id], $this->sponsor);

        $fundProvider1->product_exclusions()->firstOrCreate([
            'product_id' => $product1->id,
        ]);

        $this->assertSearchIds(['has_products' => true], [$fundProvider2->id], $this->sponsor);
        $this->assertSearchIds(['has_products' => false], [$fundProvider1->id], $this->sponsor);
    }

    /**
     * @param array $filters
     * @param Organization $organization
     * @return FundProviderSearch
     */
    private function makeSearch(array $filters, Organization $organization): FundProviderSearch
    {
        return new FundProviderSearch($filters, FundProvider::query(), $organization);
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @param Organization $organization
     * @return void
     */
    private function assertSearchIds(array $filters, array $expectedIds, Organization $organization): void
    {
        $expected = collect($expectedIds)->sort()->values()->toArray();
        $search = $this->makeSearch($filters, $organization);
        $actual = collect($search->query()->pluck('id')->toArray())->sort()->values()->toArray();

        $this->assertSame($expected, $actual);
    }
}
