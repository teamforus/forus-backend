<?php

namespace Tests\Unit\Searches\Sponsor;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\ProductReservation;
use App\Scopes\Builders\OrganizationQuery;
use App\Searches\OrganizationSearch;
use App\Traits\DoesTesting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
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
     * @return void
     */
    public function testFiltersByGroupStateActive(): void
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity());
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());

        // prepare two funds for sponsor
        $fund1 = $this->makeTestFund($sponsor);
        $fund2 = $this->makeTestFund($sponsor);

        // make two fund providers for provider and prepared funds
        $this->makeTestFundProvider($provider, $fund1);
        $this->makeTestFundProvider($provider, $fund2);

        $baseQuery = OrganizationQuery::whereIsProviderOrganization(Organization::query(), $sponsor);

        // assert provider only available by active state when all fund providers are active and all funds are active
        $this->assertIdsByGroupStates($sponsor, $baseQuery, activeIds: [$provider->id]);

        $this->closeFund($fund1);

        // assert provider only available by active state when all fund providers are active
        // and one fund is closed and another still active
        $this->assertIdsByGroupStates($sponsor, $baseQuery, activeIds: [$provider->id]);

        $this->closeFund($fund2);

        // assert provider only available by rejected state when all fund providers are active and all funds are closed
        $this->assertIdsByGroupStates($sponsor, $baseQuery, rejectedIds: [$provider->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByGroupStatePending(): void
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity());
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());

        // prepare two funds for sponsor
        $fund1 = $this->makeTestFund($sponsor);
        $fund2 = $this->makeTestFund($sponsor);

        // make two fund providers for provider and prepared funds
        $fundProviderFund1 = $this->makeTestFundProvider($provider, $fund1);
        $fundProviderFund2 = $this->makeTestFundProvider($provider, $fund2);

        // set fund providers as pending
        $fundProviderFund1->update(['state' => FundProvider::STATE_PENDING]);
        $fundProviderFund2->update(['state' => FundProvider::STATE_PENDING]);

        $baseQuery = OrganizationQuery::whereIsProviderOrganization(Organization::query(), $sponsor);

        // assert provider only available by pending state when all fund providers are pending and all funds are active
        $this->assertIdsByGroupStates($sponsor, $baseQuery, pendingIds: [$provider->id]);

        $this->closeFund($fund1);

        // assert provider only available by pending state when all fund providers are pending
        // and one fund is closed and another still active
        $this->assertIdsByGroupStates($sponsor, $baseQuery, pendingIds: [$provider->id]);

        // set fund provider 1 as accepted (related fund is closed)
        $fundProviderFund1->update(['state' => FundProvider::STATE_ACCEPTED]);

        // assert provider only available by pending state when one fund provider are pending and related fund active
        // and another fund provider is pending and related fund is closed
        $this->assertIdsByGroupStates($sponsor, $baseQuery, pendingIds: [$provider->id]);

        // set fund provider 1 as pending (related fund is closed) and fund provider 2 as accepted (related fund is active)
        $fundProviderFund1->update(['state' => FundProvider::STATE_PENDING]);
        $fundProviderFund2->update(['state' => FundProvider::STATE_ACCEPTED]);

        // assert provider only available by active state when one fund provider are pending and related fund closed
        // and another fund provider is active and related fund is active
        $this->assertIdsByGroupStates($sponsor, $baseQuery, activeIds: [$provider->id]);

        $this->closeFund($fund2);

        // assert provider only available by rejected state when all funds are closed
        $this->assertIdsByGroupStates($sponsor, $baseQuery, rejectedIds: [$provider->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByGroupStateUnsubscribed(): void
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity());
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());

        // prepare two funds for sponsor
        $fund1 = $this->makeTestFund($sponsor);
        $fund2 = $this->makeTestFund($sponsor);

        // make two fund providers for provider and prepared funds
        $fundProviderFund1 = $this->makeTestFundProvider($provider, $fund1);
        $fundProviderFund2 = $this->makeTestFundProvider($provider, $fund2);

        // set fund providers as unsubscribed
        $fundProviderFund1->update(['state' => FundProvider::STATE_UNSUBSCRIBED]);
        $fundProviderFund2->update(['state' => FundProvider::STATE_UNSUBSCRIBED]);

        $baseQuery = OrganizationQuery::whereIsProviderOrganization(Organization::query(), $sponsor);

        // assert provider only available by unsubscribed state when all fund providers are unsubscribed and all funds are active
        $this->assertIdsByGroupStates($sponsor, $baseQuery, unsubscribedIds: [$provider->id]);

        $this->closeFund($fund1);

        // assert provider only available by unsubscribed state when all fund providers are unsubscribed
        // and one fund is closed and another still active
        $this->assertIdsByGroupStates($sponsor, $baseQuery, unsubscribedIds: [$provider->id]);

        // set fund provider 1 as accepted (related fund is closed)
        $fundProviderFund1->update(['state' => FundProvider::STATE_ACCEPTED]);

        // assert provider only available by unsubscribed state when one fund provider are unsubscribed and related fund active
        // and another fund provider is unsubscribed and related fund is closed
        $this->assertIdsByGroupStates($sponsor, $baseQuery, unsubscribedIds: [$provider->id]);

        $this->closeFund($fund2);

        // assert provider only available by rejected state when all funds are closed
        $this->assertIdsByGroupStates($sponsor, $baseQuery, rejectedIds: [$provider->id]);
    }

    /**
     * @param Fund $fund
     * @return void
     */
    private function closeFund(Fund $fund): void
    {
        $fund->update(['state' => Fund::STATE_CLOSED]);
    }

    /**
     * @param Organization $sponsor
     * @param Builder $query
     * @param array $activeIds
     * @param array $pendingIds
     * @param array $rejectedIds
     * @param array $unsubscribedIds
     * @return void
     */
    private function assertIdsByGroupStates(
        Organization $sponsor,
        Builder $query,
        array $activeIds = [],
        array $pendingIds = [],
        array $rejectedIds = [],
        array $unsubscribedIds = [],
    ): void {
        // query by active state
        $activeQuery = OrganizationQuery::whereGroupState(clone $query, $sponsor, 'active');
        $this->assertSameIds($activeQuery, $activeIds);

        // query by pending state
        $pendingQuery = OrganizationQuery::whereGroupState(clone $query, $sponsor, 'pending');
        $this->assertSameIds($pendingQuery, $pendingIds);

        // query by rejected state
        $rejectedQuery = OrganizationQuery::whereGroupState(clone $query, $sponsor, 'rejected');
        $this->assertSameIds($rejectedQuery, $rejectedIds);

        // query by unsubscribed state
        $unsubscribedQuery = OrganizationQuery::whereGroupState(clone $query, $sponsor, 'unsubscribed');
        $this->assertSameIds($unsubscribedQuery, $unsubscribedIds);
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
        $search = $this->makeSearch($filters);
        $this->assertSameIds($search->searchProviderOrganizations($sponsor), $expectedIds);
    }

    /**
     * @param Builder $builder
     * @param array $expectedIds
     * @return void
     */
    private function assertSameIds(Builder $builder, array $expectedIds): void
    {
        $expected = collect($expectedIds)->sort()->values()->toArray();
        $actual = collect($builder->pluck('id')->toArray())->sort()->values()->toArray();

        $this->assertSame($expected, $actual);
    }
}
