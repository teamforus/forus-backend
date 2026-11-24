<?php

namespace Tests\Unit\Searches\Sponsor;

use App\Models\Household;
use App\Models\Organization;
use App\Searches\Sponsor\HouseholdSearch;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestHousehold;
use Tests\Traits\MakesTestOrganizations;
use Tests\Unit\Searches\SearchTestCase;

class HouseholdSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestHousehold;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $search = $this->makeSearch([], $organization);

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByQuery(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $cityPart1 = 'match';
        $cityPart2 = 'other';

        $streetPart1 = 'first';
        $streetPart2 = 'second';

        $postalCodePart1 = 'pretty';
        $postalCodePart2 = 'json';

        $neighborhoodNamePart1 = 'near';
        $neighborhoodNamePart2 = 'far';

        $municipalityNamePart1 = 'good';
        $municipalityNamePart2 = 'bad';

        $household1 = $this->makeTestHousehold($organization, uid: 'HH-2401', data: [
            'city' => "$cityPart1 city",
            'street' => "$streetPart1 street",
            'postal_code' => "$postalCodePart1 postal code",
            'neighborhood_name' => "$neighborhoodNamePart1 neighborhood",
            'municipality_name' => "$municipalityNamePart1 municipality",
        ]);

        $household2 = $this->makeTestHousehold($organization, uid: 'HH-2402', data: [
            'city' => "$cityPart2 city",
            'street' => "$streetPart2 street",
            'postal_code' => "$postalCodePart2 postal code",
            'neighborhood_name' => "$neighborhoodNamePart2 neighborhood",
            'municipality_name' => "$municipalityNamePart2 municipality",
        ]);

        $this->assertSearchIds(['q' => $cityPart1], [$household1->id], $organization);
        $this->assertSearchIds(['q' => $cityPart2], [$household2->id], $organization);

        $this->assertSearchIds(['q' => $streetPart1], [$household1->id], $organization);
        $this->assertSearchIds(['q' => $streetPart2], [$household2->id], $organization);

        $this->assertSearchIds(['q' => $postalCodePart1], [$household1->id], $organization);
        $this->assertSearchIds(['q' => $postalCodePart2], [$household2->id], $organization);

        $this->assertSearchIds(['q' => $neighborhoodNamePart1], [$household1->id], $organization);
        $this->assertSearchIds(['q' => $neighborhoodNamePart2], [$household2->id], $organization);

        $this->assertSearchIds(['q' => $municipalityNamePart1], [$household1->id], $organization);
        $this->assertSearchIds(['q' => $municipalityNamePart2], [$household2->id], $organization);

        $this->assertSearchIds(['q' => $household1->id], [$household1->id], $organization);
        $this->assertSearchIds(['q' => $household2->id], [$household2->id], $organization);

        $this->assertSearchIds(['q' => $household1->uid], [$household1->id], $organization);
        $this->assertSearchIds(['q' => $household2->uid], [$household2->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByLivingArrangement(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $living1 = Household::LIVING_ARRANGEMENT_MARRIED_OR_UNMARRIED_COHABITING;
        $living2 = Household::LIVING_ARRANGEMENT_COHABITING_WITH_OTHER_SINGLES;

        $household1 = $this->makeTestHousehold($organization, living_arrangement: $living1);
        $household2 = $this->makeTestHousehold($organization, living_arrangement: $living2);

        $this->assertSearchIds(['living_arrangement' => $living1], [$household1->id], $organization);
        $this->assertSearchIds(['living_arrangement' => $living2], [$household2->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByFundIdAndOrganizationId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($organization);

        $householdHasIdentity = $this->makeTestHousehold($organization);
        $this->makeTestHousehold($organization);

        $identity = $this->makeIdentity();
        $fund->makeVoucher($identity);

        $profile = $organization->findOrMakeProfile($identity);
        $this->makeTestHouseholdProfile($householdHasIdentity, $profile);

        $this->assertSearchIds(['fund_id' => $fund->id], [$householdHasIdentity->id], $organization);
    }

    /**
     * @return void
     */
    public function testOrdersByCreatedAt(): void
    {
        $city = 'match_city';
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $householdA = $this->makeTestHousehold($organization, data: ['city' => $city]);

        Carbon::setTestNow(now()->addDays(5));
        $householdB = $this->makeTestHousehold($organization, data: ['city' => $city]);

        $this->assertSearchOrder([
            'q' => $city,
            'order_by' => 'created_at',
            'order_dir' => 'asc',
        ], [$householdA->id, $householdB->id], $organization);

        $this->assertSearchOrder([
            'q' => $city,
            'order_by' => 'created_at',
            'order_dir' => 'desc',
        ], [$householdB->id, $householdA->id], $organization);
    }

    /**
     * @return void
     */
    public function testOrdersByUpdatedAt(): void
    {
        $city = 'match_city';
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $householdA = $this->makeTestHousehold($organization, data: ['city' => $city]);

        Carbon::setTestNow(now()->addDays(5));
        $householdB = $this->makeTestHousehold($organization, data: ['city' => $city]);

        $this->assertSearchOrder([
            'q' => $city,
            'order_by' => 'updated_at',
            'order_dir' => 'asc',
        ], [$householdA->id, $householdB->id], $organization);

        $this->assertSearchOrder([
            'q' => $city,
            'order_by' => 'updated_at',
            'order_dir' => 'desc',
        ], [$householdB->id, $householdA->id], $organization);
    }

    /**
     * @param array $filters
     * @param Organization $organization
     * @return HouseholdSearch
     */
    private function makeSearch(array $filters, Organization $organization): HouseholdSearch
    {
        return new HouseholdSearch([
            ...$filters,
            'organization_id' => $organization->id,
        ], Household::where('organization_id', $organization->id));
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

    /**
     * @param array $filters
     * @param array $expectedIds
     * @param Organization $organization
     * @return void
     */
    private function assertSearchOrder(array $filters, array $expectedIds, Organization $organization): void
    {
        $search = $this->makeSearch($filters, $organization);
        $actual = $search->query()->pluck('id')->toArray();

        $this->assertSame($expectedIds, $actual);
    }
}
