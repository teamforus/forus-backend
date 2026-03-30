<?php

namespace Tests\Unit\Searches;

use App\Models\PhysicalCardRequest;
use App\Searches\PhysicalCardRequestSearch;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestPhysicalCardTypes;
use Tests\Traits\MakesTestVouchers;

class PhysicalCardRequestSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestVouchers;
    use MakesTestOrganizations;
    use MakesTestPhysicalCardTypes;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new PhysicalCardRequestSearch([], PhysicalCardRequest::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByFundId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $type = $this->makeTestPhysicalCardType($organization);

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund1, $this->makeIdentity($this->makeUniqueEmail()), amount: 100);
        $voucher2 = $this->makeTestVoucher($fund2, $this->makeIdentity($this->makeUniqueEmail()), amount: 100);

        $request1 = $this->makeTestFundPhysicalCardRequest($voucher1, $type);
        $request2 = $this->makeTestFundPhysicalCardRequest($voucher2, $type);

        $this->assertSearchIds(['fund_id' => $fund1->id], [$request1->id]);
        $this->assertSearchIds(['fund_id' => $fund2->id], [$request2->id]);

        $request3 = $this->makeTestFundPhysicalCardRequest($voucher2, $type);
        $this->assertSearchIds(['fund_id' => $fund2->id], [$request2->id, $request3->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByDate(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $type = $this->makeTestPhysicalCardType($organization);

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund1, $this->makeIdentity($this->makeUniqueEmail()), amount: 100);
        $voucher2 = $this->makeTestVoucher($fund2, $this->makeIdentity($this->makeUniqueEmail()), amount: 100);

        $request1 = $this->makeTestFundPhysicalCardRequest($voucher1, $type);

        $request2 = $this->makeTestFundPhysicalCardRequest($voucher2, $type);
        $request2->created_at = Carbon::now()->subDays(7);
        $request2->save();

        $this->assertSearchIds(['date' => Carbon::now()->format('Y-m-d')], [$request1->id]);
        $this->assertSearchIds(['date' => Carbon::now()->subDays(7)->format('Y-m-d')], [$request2->id]);
    }

    /**
     * @param array $filters
     * @return PhysicalCardRequestSearch
     */
    private function makeSearch(array $filters): PhysicalCardRequestSearch
    {
        return new PhysicalCardRequestSearch($filters, PhysicalCardRequest::query());
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
}
