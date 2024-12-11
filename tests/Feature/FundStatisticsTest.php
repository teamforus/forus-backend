<?php

namespace Tests\Feature;

use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesApplication;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class FundStatisticsTest extends TestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use CreatesApplication;
    use MakesTestIdentities;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * Tests that top-ups created in 2020 are still valid in 2030
     *
     * @return void
     * @throws Throwable
     */
    public function testFundTopUpsAreNotLimitedByDate(): void
    {
        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));
        $fund->top_ups()->forceDelete();
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => 20000]);

        $this->travelTo('2020-01-01');
        $this->assertEquals(20000, $fund->refresh()->budget_left);

        $this->travelTo('2030-01-01');
        $this->assertEquals(20000, $fund->refresh()->budget_left);
    }

    /**
     * Tests that budget left is calculated correctly across different years
     * @return void
     * @throws Throwable
     */
    public function testFundTotalBudgetIsNotLimitedByDate(): void
    {
        $this->travelTo('2020-01-01');

        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));
        $fund->top_ups()->forceDelete();
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => 20000]);

        $fund
            ->makeVoucher($this->makeIdentity())
            ->makeTransactionBySponsor($fund->organization->employees[0], ['amount' => 1000]);

        $response = $this->getJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds/$fund->id?stats=min",
            $this->makeApiHeaders($fund->organization->identity),
        );

        $response->assertSuccessful();
        $this->assertEquals(19000, $response->json('data.budget.left'));

        $this->travelTo('2030-01-01');

        $fund
            ->makeVoucher($this->makeIdentity())
            ->makeTransactionBySponsor($fund->organization->employees[0], ['amount' => 2000]);

        $response = $this->getJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds/$fund->id?stats=all",
            $this->makeApiHeaders($fund->organization->identity),
        );

        $response->assertSuccessful();
        $this->assertEquals(17000, $response->json('data.budget.left'));
    }

    /**
     * Test fund stats year filter
     * @return void
     * @throws Throwable
     */
    public function testFundStatsYearFilter(): void
    {
        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));
        $fund->top_ups()->forceDelete();
        $this->travelTo('2020-01-01');
        $voucher = $fund->makeVoucher($this->makeIdentity());

        $this->travelTo('2021-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => 10000]);
        $voucher->makeTransactionBySponsor($fund->organization->employees[0], ['amount' => 1000]);

        $this->travelTo('2022-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => 20000]);
        $voucher->makeTransactionBySponsor($fund->organization->employees[0], ['amount' => 2000]);

        $this->travelTo('2023-01-01');
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => 40000]);
        $voucher->makeTransactionBySponsor($fund->organization->employees[0], ['amount' => 4000]);

        $this->getJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds?stats=all&year=2021",
            $this->makeApiHeaders($fund->organization->identity),
        )->assertJsonPath('data.0.budget.left', currency_format(9000));

        $this->getJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds?stats=all&year=2022",
            $this->makeApiHeaders($fund->organization->identity),
        )->assertJsonPath('data.0.budget.left', currency_format(18000));

        $this->getJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds?stats=all&year=2023",
            $this->makeApiHeaders($fund->organization->identity),
        )->assertJsonPath('data.0.budget.left', currency_format(36000));
    }
}