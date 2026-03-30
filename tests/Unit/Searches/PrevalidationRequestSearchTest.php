<?php

namespace Tests\Unit\Searches;

use App\Models\Organization;
use App\Models\PrevalidationRequest;
use App\Searches\PrevalidationRequestSearch;
use App\Services\Forus\TestData\TestData;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;

class PrevalidationRequestSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new PrevalidationRequestSearch([], PrevalidationRequest::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByQuery(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $employee = $organization->employees()->first();

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $bsn1 = 11112222;
        $bsn2 = 33332222;

        PrevalidationRequest::makeFromArray($fund1, $employee, [['bsn' => $bsn1]]);
        $request1 = PrevalidationRequest::query()->where('fund_id', $fund1->id)->first();

        PrevalidationRequest::makeFromArray($fund2, $employee, [['bsn' => $bsn2]]);
        $request2 = PrevalidationRequest::query()->where('fund_id', $fund2->id)->first();

        $this->assertSearchIds(['q' => '1111'], [$request1->id], $organization);
        $this->assertSearchIds(['q' => '3333'], [$request2->id], $organization);
        $this->assertSearchIds(['q' => '2222'], [$request1->id, $request2->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByFundId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $employee = $organization->employees()->first();

        PrevalidationRequest::makeFromArray($fund1, $employee, [['bsn' => TestData::randomFakeBsn()]]);
        $request1 = PrevalidationRequest::query()->where('fund_id', $fund1->id)->first();

        PrevalidationRequest::makeFromArray($fund2, $employee, [['bsn' => TestData::randomFakeBsn()]]);
        $request2 = PrevalidationRequest::query()->where('fund_id', $fund2->id)->first();

        $this->assertSearchIds(['fund_id' => $fund1->id], [$request1->id], $organization);
        $this->assertSearchIds(['fund_id' => $fund2->id], [$request2->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByState(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);
        $employee = $organization->employees()->first();

        PrevalidationRequest::makeFromArray($fund, $employee, [
            ['bsn' => TestData::randomFakeBsn()],
            ['bsn' => TestData::randomFakeBsn()],
        ]);

        $requests = PrevalidationRequest::query()->where('fund_id', $fund->id)->get();

        $request1 = $requests->first();
        $request2 = $requests->last();

        $this->assertSearchIds(['state' => PrevalidationRequest::STATE_PENDING], [$request1->id, $request2->id], $organization);
        $this->assertSearchIds(['state' => PrevalidationRequest::STATE_SUCCESS], [], $organization);

        $request2->update(['state' => PrevalidationRequest::STATE_SUCCESS]);
        $this->assertSearchIds(['state' => PrevalidationRequest::STATE_SUCCESS], [$request2->id], $organization);
    }

    /**
     * @return void
     */
    public function testFilterByCreatedAt(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);
        $employee = $organization->employees()->first();

        PrevalidationRequest::makeFromArray($fund, $employee, [
            ['bsn' => TestData::randomFakeBsn()],
            ['bsn' => TestData::randomFakeBsn()],
        ]);

        $requests = PrevalidationRequest::query()->where('fund_id', $fund->id)->get();

        $request1 = $requests->first();
        $request1->created_at = Carbon::now()->subDays(7);
        $request1->save();

        $request2 = $requests->last();

        $this->assertSearchIds([
            'from' => Carbon::now()->subDays(8)->format('Y-m-d'),
            'to' => Carbon::now()->subDays(5)->format('Y-m-d'),
        ], [$request1->id], $organization);

        $this->assertSearchIds([
            'from' => Carbon::now()->subDays(5)->format('Y-m-d'),
            'to' => Carbon::now()->addDays(2)->format('Y-m-d'),
        ], [$request2->id], $organization);

        $this->assertSearchIds([
            'from' => Carbon::now()->subDays(5)->format('Y-m-d'),
        ], [$request2->id], $organization);

        $this->assertSearchIds([
            'from' => Carbon::now()->subDays(10)->format('Y-m-d'),
            'to' => Carbon::now()->addDays(2)->format('Y-m-d'),
        ], [$request1->id, $request2->id], $organization);
    }

    /**
     * @return void
     */
    public function testOrdersByCreatedAt(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);
        $employee = $organization->employees()->first();

        PrevalidationRequest::makeFromArray($fund, $employee, [
            ['bsn' => TestData::randomFakeBsn()],
            ['bsn' => TestData::randomFakeBsn()],
        ]);

        $requests = PrevalidationRequest::query()->where('fund_id', $fund->id)->get();

        $olderRequest = $requests->first();
        $olderRequest->created_at = Carbon::now()->subDays(7);
        $olderRequest->save();

        $newerRequest = $requests->last();

        $this->assertSearchOrder([
            'order_by' => 'created_at',
            'order_dir' => 'asc',
        ], [$olderRequest->id, $newerRequest->id], $organization);

        $this->assertSearchOrder([
            'order_by' => 'created_at',
            'order_dir' => 'desc',
        ], [$newerRequest->id, $olderRequest->id], $organization);
    }

    /**
     * @param array $filters
     * @param Organization $organization
     * @return PrevalidationRequestSearch
     */
    private function makeSearch(array $filters, Organization $organization): PrevalidationRequestSearch
    {
        return new PrevalidationRequestSearch(
            $filters,
            PrevalidationRequest::where('organization_id', $organization->id)
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
