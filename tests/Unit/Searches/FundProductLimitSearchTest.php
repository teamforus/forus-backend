<?php

namespace Tests\Unit\Searches;

use App\Models\FundProductLimit;
use App\Models\Organization;
use App\Searches\FundProductLimitSearch;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;

class FundProductLimitSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new FundProductLimitSearch([], FundProductLimit::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByQuery(): void
    {
        $orgNamePart = 'fundsearchorganizationone';

        $namePart1 = 'fundsearchnameone';
        $namePart2 = 'fundsearchnametwo';

        $descriptionTextPart1 = 'fundsearchdescriptionone';
        $descriptionTextPart2 = 'fundsearchdescriptiontwo';

        $descriptionShortPart1 = 'fundsearchshortone';
        $descriptionShortPart2 = 'fundsearchshorttwo';

        $organization = $this->makeTestOrganization($this->makeIdentity(), ['name' => "Organization $orgNamePart"]);

        $fund1 = $this->makeTestFund($organization, [
            'name' => "Fund $namePart1 name",
            'description_text' => "Fund $descriptionTextPart1 description text",
            'description_short' => "Fund $descriptionShortPart1 description short",
        ]);

        $fund2 = $this->makeTestFund($organization, [
            'name' => "Fund $namePart2 name",
            'description_text' => "Fund $descriptionTextPart2 description text",
            'description_short' => "Fund $descriptionShortPart2 description short",
        ]);

        $limit1 = FundProductLimit::create([
            'fund_id' => $fund1->id,
            'type' => FundProductLimit::TYPE_SELECTED,
            'state' => FundProductLimit::STATE_ACTIVE,
            'limit' => 1,
        ]);

        $limit2 = FundProductLimit::create([
            'fund_id' => $fund2->id,
            'type' => FundProductLimit::TYPE_SELECTED,
            'state' => FundProductLimit::STATE_ACTIVE,
            'limit' => 1,
        ]);

        // assert by organization name
        $this->assertSearchIds(['q' => $orgNamePart], [$limit1->id, $limit2->id], $organization);

        // assert by fund name
        $this->assertSearchIds(['q' => $namePart1], [$limit1->id], $organization);
        $this->assertSearchIds(['q' => $namePart2], [$limit2->id], $organization);

        // assert by description_text
        $this->assertSearchIds(['q' => $descriptionTextPart1], [$limit1->id], $organization);
        $this->assertSearchIds(['q' => $descriptionTextPart2], [$limit2->id], $organization);

        // assert by description_short
        $this->assertSearchIds(['q' => $descriptionShortPart1], [$limit1->id], $organization);
        $this->assertSearchIds(['q' => $descriptionShortPart2], [$limit2->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByFundId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $limit1 = FundProductLimit::create([
            'fund_id' => $fund1->id,
            'type' => FundProductLimit::TYPE_SELECTED,
            'state' => FundProductLimit::STATE_ACTIVE,
            'limit' => 1,
        ]);

        $limit2 = FundProductLimit::create([
            'fund_id' => $fund2->id,
            'type' => FundProductLimit::TYPE_SELECTED,
            'state' => FundProductLimit::STATE_ACTIVE,
            'limit' => 1,
        ]);

        $this->assertSearchIds(['fund_id' => $fund1->id], [$limit1->id], $organization);
        $this->assertSearchIds(['fund_id' => $fund2->id], [$limit2->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByState(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $limit1 = FundProductLimit::create([
            'fund_id' => $fund->id,
            'type' => FundProductLimit::TYPE_SELECTED,
            'state' => FundProductLimit::STATE_ACTIVE,
            'limit' => 1,
        ]);

        $limit2 = FundProductLimit::create([
            'fund_id' => $fund->id,
            'type' => FundProductLimit::TYPE_SELECTED,
            'state' => FundProductLimit::STATE_INACTIVE,
            'limit' => 1,
        ]);

        $this->assertSearchIds(['state' => FundProductLimit::STATE_ACTIVE], [$limit1->id], $organization);
        $this->assertSearchIds(['state' => FundProductLimit::STATE_INACTIVE], [$limit2->id], $organization);
    }

    /**
     * @return void
     */
    public function testFilterByCreatedAt(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $limit1 = FundProductLimit::create([
            'fund_id' => $fund->id,
            'type' => FundProductLimit::TYPE_SELECTED,
            'state' => FundProductLimit::STATE_ACTIVE,
            'limit' => 1,
        ]);

        $limit1->created_at = Carbon::now()->subDays(7);
        $limit1->save();

        $limit2 = FundProductLimit::create([
            'fund_id' => $fund->id,
            'type' => FundProductLimit::TYPE_SELECTED,
            'state' => FundProductLimit::STATE_INACTIVE,
            'limit' => 1,
        ]);

        $this->assertSearchIds([
            'from' => Carbon::now()->subDays(8)->format('Y-m-d'),
            'to' => Carbon::now()->subDays(5)->format('Y-m-d'),
        ], [$limit1->id], $organization);

        $this->assertSearchIds([
            'from' => Carbon::now()->subDays(5)->format('Y-m-d'),
            'to' => Carbon::now()->addDays(2)->format('Y-m-d'),
        ], [$limit2->id], $organization);

        $this->assertSearchIds([
            'from' => Carbon::now()->subDays(5)->format('Y-m-d'),
        ], [$limit2->id], $organization);

        $this->assertSearchIds([
            'from' => Carbon::now()->subDays(10)->format('Y-m-d'),
            'to' => Carbon::now()->addDays(2)->format('Y-m-d'),
        ], [$limit1->id, $limit2->id], $organization);
    }

    /**
     * @return void
     */
    public function testOrdersByCreatedAt(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $olderLimit = FundProductLimit::create([
            'fund_id' => $fund->id,
            'type' => FundProductLimit::TYPE_SELECTED,
            'state' => FundProductLimit::STATE_ACTIVE,
            'limit' => 1,
        ]);

        $olderLimit->created_at = Carbon::now()->subDays(7);
        $olderLimit->save();

        $newerLimit = FundProductLimit::create([
            'fund_id' => $fund->id,
            'type' => FundProductLimit::TYPE_SELECTED,
            'state' => FundProductLimit::STATE_INACTIVE,
            'limit' => 1,
        ]);

        $this->assertSearchOrder([
            'order_by' => 'created_at',
            'order_dir' => 'asc',
        ], [$olderLimit->id, $newerLimit->id], $organization);

        $this->assertSearchOrder([
            'order_by' => 'created_at',
            'order_dir' => 'desc',
        ], [$newerLimit->id, $olderLimit->id], $organization);
    }

    /**
     * @param array $filters
     * @param Organization $organization
     * @return FundProductLimitSearch
     */
    private function makeSearch(array $filters, Organization $organization): FundProductLimitSearch
    {
        return new FundProductLimitSearch(
            $filters,
            FundProductLimit::whereIn('fund_id', $organization->funds->pluck('id')->toArray())
        );
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
