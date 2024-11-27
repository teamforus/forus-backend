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
     * @return void
     * @throws Throwable
     */
    public function testFundTopUpsAreNotLimitedByDate(): void
    {
        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));
        $fund->top_ups()->forceDelete();
        $fund->getOrCreateTopUp()->transactions()->create([ 'amount' => 20000 ]);

        $this->travelTo('2020-01-01');
        $this->assertEquals(20000, $fund->refresh()->budget_left);

        $this->travelTo('2030-01-01');
        $this->assertEquals(20000, $fund->refresh()->budget_left);
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testFundTotalBudgetIsNotLimitedByDate(): void
    {
        $this->travelTo('2020-01-01');

        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));
        $fund->top_ups()->forceDelete();
        $fund->getOrCreateTopUp()->transactions()->create([ 'amount' => 20000 ]);

        $response = $this->getJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds/$fund->id?stats=all",
            $this->makeApiHeaders($fund->organization->identity),
        );

        $response->assertSuccessful();
        $this->assertEquals(20000, $response->json('data.budget.total'));

        $this->travelTo('2030-01-01');

        $response = $this->getJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds/$fund->id?stats=all",
            $this->makeApiHeaders($fund->organization->identity),
        );

        $response->assertSuccessful();
        $this->assertEquals(20000, $response->json('data.budget.total'));
    }
}