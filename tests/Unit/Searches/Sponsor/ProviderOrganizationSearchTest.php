<?php

namespace Tests\Unit\Searches\Sponsor;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\ProductReservation;
use App\Searches\OrganizationSearch;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestBusinessType;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizationOffices;
use Tests\Traits\MakesTestOrganizations;
use Tests\Unit\Searches\SearchTestCase;
use Throwable;

class ProviderOrganizationSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestBusinessType;
    use MakesTestFundRequests;
    use MakesTestOrganizations;
    use MakesProductReservations;
    use MakesTestOrganizationOffices;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new OrganizationSearch([], Organization::query());
        $sponsor = $this->makeTestOrganization($this->makeIdentity());

        $this->assertQueryBuilds($search->searchProviderOrganizations($sponsor));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByRelatedProvider(): void
    {
        $identity = $this->makeIdentity();
        $sponsor = $this->makeTestOrganization($identity);
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());

        $this->assertSearchIds([], [], $sponsor);

        $fund = $this->makeTestFund($sponsor);
        $this->makeTestFundProvider($provider, $fund);

        $this->assertSearchIds([], [$provider->id], $sponsor);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByProviderIds(): void
    {
        $identity = $this->makeIdentity();
        $sponsor = $this->makeTestOrganization($identity);
        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($sponsor);
        $this->makeTestFundProvider($provider1, $fund);
        $this->makeTestFundProvider($provider2, $fund);

        $this->assertSearchIds(['provider_ids' => [$provider1->id]], [$provider1->id], $sponsor);

        $this->assertSearchIds(['provider_ids' => [
            $provider1->id,
            $provider2->id,
        ]], [$provider1->id, $provider2->id], $sponsor);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByOfficePostcode(): void
    {
        $identity = $this->makeIdentity();
        $sponsor = $this->makeTestOrganization($identity);
        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($sponsor);
        $this->makeTestFundProvider($provider1, $fund);
        $this->makeTestFundProvider($provider2, $fund);

        $this->makeOrganizationOffice($provider1, ['postcode_number' => 'first postcode']);
        $this->makeOrganizationOffice($provider2, ['postcode_number' => 'second postcode']);

        $this->assertSearchIds(['postcodes' => ['first postcode']], [$provider1->id], $sponsor);

        $this->assertSearchIds(['postcodes' => [
            'first postcode',
            'second postcode',
        ]], [$provider1->id, $provider2->id], $sponsor);
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

        $this->assertSearchIds(['business_type_ids' => [$businessType1->id]], [$provider1->id], $sponsor);

        $this->assertSearchIds(['business_type_ids' => [
            $businessType1->id,
            $businessType2->id,
        ]], [$provider1->id, $provider2->id], $sponsor);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByDates(): void
    {
        $now = Carbon::now();
        $identity = $this->makeIdentity();
        $sponsor = $this->makeTestOrganization($identity);
        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($sponsor);
        $this->makeTestFundProvider($provider1, $fund);
        $this->makeTestFundProvider($provider2, $fund);

        $this->prepareReservation($fund, $provider1, $identity)->acceptProvider();

        Carbon::setTestNow($now->copy()->addDays(5));
        $this->prepareReservation($fund, $provider2, $identity)->acceptProvider();

        $this->assertSearchIds([
            'date_from' => $now,
            'date_to' => $now->clone()->addDays(10),
        ], [$provider1->id, $provider2->id], $sponsor);

        $this->assertSearchIds([
            'date_from' => $now->copy()->addDays(3),
            'date_to' => $now->clone()->addDays(10),
        ], [$provider2->id], $sponsor);

        $this->assertSearchIds([
            'date_from' => $now,
            'date_to' => $now->copy()->addDays(3),
        ], [$provider1->id], $sponsor);
    }

    /**
     * @param Fund $fund
     * @param Organization $provider
     * @param Identity $identity
     * @throws Throwable
     * @return ProductReservation
     */
    private function prepareReservation(Fund $fund, Organization $provider, Identity $identity): ProductReservation
    {
        $voucher = $this->makeTestVoucher($fund, $identity);
        $product = $this->createProductForReservation($provider, [$fund]);

        return $voucher->reserveProduct($product);
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
     * @param Organization $sponsor
     * @return void
     */
    private function assertSearchIds(array $filters, array $expectedIds, Organization $sponsor): void
    {
        $expected = collect($expectedIds)->sort()->values()->toArray();
        $search = $this->makeSearch($filters);
        $actual = collect($search->searchProviderOrganizations($sponsor)->pluck('id')->toArray())->sort()->values()->toArray();

        $this->assertSame($expected, $actual);
    }
}
