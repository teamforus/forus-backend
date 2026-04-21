<?php

namespace Tests\Unit\Searches;

use App\Models\Organization;
use App\Models\VoucherTransactionBulk;
use App\Searches\VoucherTransactionBulksSearch;
use App\Traits\DoesTesting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Traits\MakesTestBankConnections;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;
use Tests\Traits\MakesVoucherTransaction;
use Throwable;

class VoucherTransactionBulksSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestVouchers;
    use MakesTestOrganizations;
    use MakesVoucherTransaction;
    use MakesTestBankConnections;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new VoucherTransactionBulksSearch([], VoucherTransactionBulk::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByState(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->makeTestImplementation($organization);
        $this->makeBankConnection($organization);

        // create first fund and transactions for first bulk
        $fund1 = $this->makeTestFund($organization);
        $this->makeTransactions($fund1, 1);

        $bulkIds = VoucherTransactionBulk::buildBulksForOrganization($organization);
        $this->assertCount(1, $bulkIds);
        $bulk1 = VoucherTransactionBulk::whereIn('id', $bulkIds)->first();

        // create second fund and transactions for second bulk
        $fund2 = $this->makeTestFund($organization);
        $this->makeTransactions($fund2, 1);

        $bulkIds = VoucherTransactionBulk::buildBulksForOrganization($organization);
        $this->assertCount(1, $bulkIds);
        $bulk2 = VoucherTransactionBulk::whereIn('id', $bulkIds)->first();

        // assert both bulks are visible with state draft
        $this->assertSearchIds([
            'state' => VoucherTransactionBulk::STATE_DRAFT,
        ], [$bulk1->id, $bulk2->id], $organization);

        // accept first bulk set accepted and filtered by state
        // and second bulk still can be filtered by state 'draft'
        DB::beginTransaction();
        $bulk1->setAcceptedBNG(null, false);

        $this->assertSearchIds([
            'state' => VoucherTransactionBulk::STATE_ACCEPTED,
        ], [$bulk1->id], $organization);

        $this->assertSearchIds([
            'state' => VoucherTransactionBulk::STATE_DRAFT,
        ], [$bulk2->id], $organization);
        DB::rollBack();

        // reject first bulk and accept filter by rejected state working
        DB::beginTransaction();
        $bulk1->setRejected();

        $this->assertSearchIds([
            'state' => VoucherTransactionBulk::STATE_REJECTED,
        ], [$bulk1->id], $organization);
        DB::rollBack();

        // set state 'error' and accept filter is working
        DB::beginTransaction();
        $bulk1->update(['state' => VoucherTransactionBulk::STATE_ERROR]);

        $this->assertSearchIds([
            'state' => VoucherTransactionBulk::STATE_ERROR,
        ], [$bulk1->id], $organization);
        DB::rollBack();

        // set state 'pending' and accept filter is working
        DB::beginTransaction();
        $bulk1->update(['state' => VoucherTransactionBulk::STATE_PENDING]);

        $this->assertSearchIds([
            'state' => VoucherTransactionBulk::STATE_PENDING,
        ], [$bulk1->id], $organization);
        DB::rollBack();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByCreatedAt(): void
    {
        $now = Carbon::now();
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->makeTestImplementation($organization);
        $this->makeBankConnection($organization);

        // create first fund and transactions for first bulk
        $fund1 = $this->makeTestFund($organization);
        $this->makeTransactions($fund1, 1);

        $bulkIds = VoucherTransactionBulk::buildBulksForOrganization($organization);
        $this->assertCount(1, $bulkIds);
        $bulk1 = VoucherTransactionBulk::whereIn('id', $bulkIds)->first();

        // create second fund and transactions for second bulk
        Carbon::setTestNow($now->clone()->addDays(7));
        $fund2 = $this->makeTestFund($organization);
        $this->makeTransactions($fund2, 1);

        $bulkIds = VoucherTransactionBulk::buildBulksForOrganization($organization);
        $this->assertCount(1, $bulkIds);
        $bulk2 = VoucherTransactionBulk::whereIn('id', $bulkIds)->first();

        $this->assertSearchIds([
            'from' => $now->clone()->subDays()->format('Y-m-d'),
            'to' => $now->clone()->addDays(5)->format('Y-m-d'),
        ], [$bulk1->id], $organization);

        $this->assertSearchIds([
            'from' => $now->clone()->addDays(5)->format('Y-m-d'),
            'to' => $now->clone()->addDays(8)->format('Y-m-d'),
        ], [$bulk2->id], $organization);

        $this->assertSearchIds([
            'from' => $now->clone()->addDays(5)->format('Y-m-d'),
        ], [$bulk2->id], $organization);

        $this->assertSearchIds([
            'to' => $now->clone()->addDays(5)->format('Y-m-d'),
        ], [$bulk1->id], $organization);

        $this->assertSearchIds([
            'from' => $now->clone()->subDays(2)->format('Y-m-d'),
            'to' => $now->clone()->addDays(8)->format('Y-m-d'),
        ], [$bulk1->id, $bulk2->id], $organization);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByQuantity(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->makeTestImplementation($organization);
        $this->makeBankConnection($organization);

        // create first fund and transactions for first bulk
        $fund1 = $this->makeTestFund($organization);
        $this->makeTransactions($fund1, 1);

        $bulkIds = VoucherTransactionBulk::buildBulksForOrganization($organization);
        $this->assertCount(1, $bulkIds);
        $bulk1 = VoucherTransactionBulk::whereIn('id', $bulkIds)->first();

        // create second fund and transactions for second bulk
        $fund2 = $this->makeTestFund($organization);
        $this->makeTransactions($fund2, 2);

        $bulkIds = VoucherTransactionBulk::buildBulksForOrganization($organization);
        $this->assertCount(1, $bulkIds);
        $bulk2 = VoucherTransactionBulk::whereIn('id', $bulkIds)->first();

        $this->assertSearchIds([
            'quantity_min' => 0,
            'quantity_max' => 1,
        ], [$bulk1->id], $organization);

        $this->assertSearchIds([
            'quantity_min' => 2,
            'quantity_max' => 3,
        ], [$bulk2->id], $organization);

        $this->assertSearchIds([
            'quantity_min' => 2,
        ], [$bulk2->id], $organization);

        $this->assertSearchIds([
            'quantity_max' => 1,
        ], [$bulk1->id], $organization);

        $this->assertSearchIds([
            'quantity_min' => 1,
            'quantity_max' => 2,
        ], [$bulk1->id, $bulk2->id], $organization);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByAmount(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->makeTestImplementation($organization);
        $this->makeBankConnection($organization);

        // create first fund and transactions for first bulk with total amount 10
        $fund1 = $this->makeTestFund($organization);
        $this->makeTransactions($fund1, 1, 10);

        $bulkIds = VoucherTransactionBulk::buildBulksForOrganization($organization);
        $this->assertCount(1, $bulkIds);
        $bulk1 = VoucherTransactionBulk::whereIn('id', $bulkIds)->first();

        // create second fund and transactions for second bulk with total amount 30
        $fund2 = $this->makeTestFund($organization);
        $this->makeTransactions($fund2, 2, 15);

        $bulkIds = VoucherTransactionBulk::buildBulksForOrganization($organization);
        $this->assertCount(1, $bulkIds);
        $bulk2 = VoucherTransactionBulk::whereIn('id', $bulkIds)->first();

        $this->assertSearchIds([
            'amount_min' => 0,
            'amount_max' => 10,
        ], [$bulk1->id], $organization);

        $this->assertSearchIds([
            'amount_min' => 20,
            'amount_max' => 35,
        ], [$bulk2->id], $organization);

        $this->assertSearchIds([
            'amount_min' => 25,
        ], [$bulk2->id], $organization);

        $this->assertSearchIds([
            'amount_max' => 15,
        ], [$bulk1->id], $organization);

        $this->assertSearchIds([
            'amount_min' => 10,
            'amount_max' => 30,
        ], [$bulk1->id, $bulk2->id], $organization);
    }

    /**
     * @param array $filters
     * @param Organization $organization
     * @return VoucherTransactionBulksSearch
     */
    private function makeSearch(array $filters, Organization $organization): VoucherTransactionBulksSearch
    {
        $query = VoucherTransactionBulk::whereHas('bank_connection', fn (Builder $q) => $q->where([
            'bank_connections.organization_id' => $organization->id,
        ]));

        return new VoucherTransactionBulksSearch($filters, $query);
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
