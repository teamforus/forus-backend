<?php

namespace Tests\Unit\Searches\Sponsor;

use App\Models\HouseholdProfile;
use App\Searches\Sponsor\HouseholdProfilesSearch;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestHousehold;
use Tests\Traits\MakesTestOrganizations;
use Tests\Unit\Searches\SearchTestCase;

class HouseholdProfilesSearchTest extends SearchTestCase
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
        $search = new HouseholdProfilesSearch([], HouseholdProfile::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByQueryAndOrganizationId(): void
    {
        $email1 = 'a_b_unique_email';
        $email2 = 'other_unique_email';

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        // make first identity and profile
        $identity1 = $this->makeIdentity($this->makeUniqueEmail($email1));
        $fund->makeVoucher($identity1);

        $profile1 = $organization->findOrMakeProfile($identity1);
        $householdProfile1 = $this->makeTestHouseholdProfile($this->makeTestHousehold($organization), $profile1);

        // make second identity and profile
        $identity2 = $this->makeIdentity($this->makeUniqueEmail($email2));
        $fund->makeVoucher($identity2);

        $profile2 = $organization->findOrMakeProfile($identity2);
        $householdProfile2 = $this->makeTestHouseholdProfile($this->makeTestHousehold($organization), $profile2);

        $this->assertSearchIds([
            'q' => $email1,
            'organization_id' => $organization->id,
        ], [$householdProfile1->id]);

        $this->assertSearchIds([
            'q' => $email2,
            'organization_id' => $organization->id,
        ], [$householdProfile2->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByCreatedAt(): void
    {
        $email = 'match_email';

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        // make first identity and profile
        $identity1 = $this->makeIdentity($this->makeUniqueEmail("{$email}_first"));
        $fund->makeVoucher($identity1);

        $profile1 = $organization->findOrMakeProfile($identity1);
        $householdProfileA = $this->makeTestHouseholdProfile($this->makeTestHousehold($organization), $profile1);

        // make second identity and profile
        $identity2 = $this->makeIdentity($this->makeUniqueEmail("{$email}_second"));
        $fund->makeVoucher($identity2);

        $profile2 = $organization->findOrMakeProfile($identity2);
        Carbon::setTestNow(now()->addDays(5));
        $householdProfileB = $this->makeTestHouseholdProfile($this->makeTestHousehold($organization), $profile2);

        $this->assertSearchOrder([
            'q' => $email,
            'organization_id' => $organization->id,
            'order_by' => 'created_at',
            'order_dir' => 'asc',
        ], [$householdProfileA->id, $householdProfileB->id]);

        $this->assertSearchOrder([
            'q' => $email,
            'organization_id' => $organization->id,
            'order_by' => 'created_at',
            'order_dir' => 'desc',
        ], [$householdProfileB->id, $householdProfileA->id]);
    }

    /**
     * @param array $filters
     * @return HouseholdProfilesSearch
     */
    private function makeSearch(array $filters): HouseholdProfilesSearch
    {
        return new HouseholdProfilesSearch($filters, HouseholdProfile::query());
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
