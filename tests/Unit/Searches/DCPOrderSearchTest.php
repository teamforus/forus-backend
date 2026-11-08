<?php

namespace Tests\Unit\Searches;

use App\Models\Organization;
use App\Searches\DCPOrderSearch;
use App\Services\DCPService\Models\DCPOrder;
use App\Traits\DoesTesting;
use Illuminate\Support\Arr;
use Tests\Traits\MakesTestDCP;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;

class DCPOrderSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestDCP;
    use MakesTestFunds;
    use MakesTestVouchers;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setupDCP();
    }

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new DCPOrderSearch([
            'q' => 'test',
            'state' => 'approved',
            'state_group' => DCPOrderSearch::STATE_GROUP_IN_PROGRESS,
            'awaiting_approval' => true,
        ], DCPOrder::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByStateAndStateGroup(): void
    {
        [$setup, $orders] = $this->makeTestDCPOrdersInEachState();
        $organization = $setup['organization'];

        $this->assertSearchIds($organization, ['state_group' => 'pending'], [$orders['pending']]);
        $this->assertSearchIds($organization, ['state_group' => 'error'], [$orders['error']]);
        $this->assertSearchIds($organization, ['state_group' => 'duplicate'], [$orders['duplicate']]);
        $this->assertSearchIds($organization, ['state_group' => 'in_progress'], [$orders['approved'], $orders['sent']]);
        $this->assertSearchIds($organization, ['state_group' => 'done'], [$orders['closed'], $orders['canceled']]);

        // the states: pending orders in a bulk are "approved", duplicates are "sent"
        $this->assertSearchIds($organization, ['state' => 'pending'], [$orders['pending'], $orders['approved']]);
        $this->assertSearchIds($organization, ['state' => 'approved'], [$orders['approved']]);
        $this->assertSearchIds($organization, ['state' => 'sent'], [$orders['sent'], $orders['duplicate']]);
        $this->assertSearchIds($organization, ['awaiting_approval' => true], [$orders['pending']]);

        $this->assertSearchIds($organization, [
            'dcp_order_bulk_id' => DCPOrder::find($orders['approved'])->dcp_order_bulk_id,
        ], Arr::except($orders, 'pending'));
    }

    /**
     * @return void
     */
    public function testStateGroupCounts(): void
    {
        [$setup, $orders] = $this->makeTestDCPOrdersInEachState();
        $organization = $setup['organization'];
        $builder = DCPOrder::where('organization_id', $organization->id);

        $this->assertSame([
            'all' => 7,
            'pending' => 1,
            'error' => 1,
            'duplicate' => 1,
            'in_progress' => 2,
            'done' => 2,
        ], DCPOrderSearch::stateGroupCounts(['state_group' => 'error', 'state' => 'error'], $builder));

        // the counts follow the other filters
        DCPOrder::find($orders['error'])->update(['order_id_dcp' => 'SEARCH-ME-1']);
        DCPOrder::find($orders['closed'])->update(['order_id_dcp' => 'SEARCH-ME-2']);

        $this->assertSame([
            'all' => 2,
            'pending' => 0,
            'error' => 1,
            'duplicate' => 0,
            'in_progress' => 0,
            'done' => 1,
        ], DCPOrderSearch::stateGroupCounts(['q' => 'search-me'], $builder));
    }

    /**
     * @return void
     */
    public function testFiltersBySearchQuery(): void
    {
        $order = $this->makeTestDCPOrder($this->makeTestDCPSetup());
        $organization = $order->organization;

        $order->update(['order_id_dcp' => 'DCP-SEARCH-42']);

        $this->assertSearchIds($organization, ['q' => $order->order_id], [$order->id]);
        $this->assertSearchIds($organization, ['q' => 'dcp-search-42'], [$order->id]);
        $this->assertSearchIds($organization, ['q' => 'john doe'], [$order->id]);
        $this->assertSearchIds($organization, ['q' => 'hoofdstraat 12'], [$order->id]);
        $this->assertSearchIds($organization, ['q' => '1234ab'], [$order->id]);
        $this->assertSearchIds($organization, ['q' => 'nijmegen'], [$order->id]);
        $this->assertSearchIds($organization, ['q' => 'not-existing'], []);
    }

    /**
     * @param Organization $organization
     * @param array $filters
     * @param array $expectedIds
     * @return void
     */
    protected function assertSearchIds(Organization $organization, array $filters, array $expectedIds): void
    {
        $search = new DCPOrderSearch($filters, DCPOrder::where('organization_id', $organization->id));

        $this->assertEqualsCanonicalizing(
            array_values($expectedIds),
            $search->query()->pluck('id')->toArray(),
            'Filters: ' . json_encode($filters),
        );
    }
}
