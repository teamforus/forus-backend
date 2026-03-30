<?php

namespace Tests\Unit\Searches;

use App\Models\Organization;
use App\Models\PhysicalCardType;
use App\Searches\PhysicalCardTypeSearch;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestPhysicalCardTypes;

class PhysicalCardTypeSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestOrganizations;
    use MakesTestPhysicalCardTypes;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new PhysicalCardTypeSearch([], PhysicalCardType::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByQuery(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $type1 = $this->makeTestPhysicalCardType(
            $organization,
            name: 'match type',
            description: 'unique description'
        );

        $type2 = $this->makeTestPhysicalCardType(
            $organization,
            name: 'other type',
            description: 'second description'
        );

        // match name
        $this->assertSearchIds(['q' => 'match'], [$type1->id], $organization);
        $this->assertSearchIds(['q' => 'type'], [$type1->id, $type2->id], $organization);

        // match description
        $this->assertSearchIds(['q' => 'unique'], [$type1->id], $organization);
        $this->assertSearchIds(['q' => 'description'], [$type1->id, $type2->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByFundId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $type1 = $this->makeTestPhysicalCardType($organization);
        $this->makeTestFundPhysicalCardType($fund1, $type1);

        $type2 = $this->makeTestPhysicalCardType($organization);
        $this->makeTestFundPhysicalCardType($fund1, $type2);
        $this->makeTestFundPhysicalCardType($fund2, $type2);

        $this->assertSearchIds(['fund_id' => $fund2->id], [$type2->id], $organization);
        $this->assertSearchIds(['fund_id' => $fund1->id], [$type1->id, $type2->id], $organization);
    }

    /**
     * @return void
     */
    public function testOrdersByCreatedAt(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $olderType = $this->makeTestPhysicalCardType($organization);

        Carbon::setTestNow(now()->addDays(5));
        $newerType = $this->makeTestPhysicalCardType($organization);

        $this->assertSearchOrder([
            'order_by' => 'created_at',
            'order_dir' => 'asc',
        ], [$olderType->id, $newerType->id], $organization);

        $this->assertSearchOrder([
            'order_by' => 'created_at',
            'order_dir' => 'desc',
        ], [$newerType->id, $olderType->id], $organization);
    }

    /**
     * @param array $filters
     * @param Organization $organization
     * @return PhysicalCardTypeSearch
     */
    private function makeSearch(array $filters, Organization $organization): PhysicalCardTypeSearch
    {
        return new PhysicalCardTypeSearch(
            $filters,
            PhysicalCardType::where('organization_id', $organization->id)
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
