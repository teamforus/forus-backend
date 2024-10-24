<?php

namespace Tests\Feature;

use App\Models\FundPeriod;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;

class FundPeriodTest extends TestCase
{
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    public function testFundGetsExtended(): void
    {
        // assert fund is created and active
        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));
        $this->assertTrue($fund->isActive());

        // assert fund is closed and expired on when end_date reached
        $this->travelTo($fund->end_date->clone()->addDay()->addMinute());
        $this->artisan('forus.funds:update-state');
        $this->assertTrue($fund->refresh()->isClosed());
        $this->assertTrue($fund->refresh()->isExpired());

        // assert fund is not extended if not suitable pending periods are available
        $this->artisan('forus.funds:extend-periods');
        $this->assertTrue($fund->refresh()->isClosed());
        $this->assertTrue($fund->refresh()->isExpired());

        $period = FundPeriod::forceCreate([
            'fund_id' => $fund->id,
            'start_date' => $fund->end_date->clone()->addDay(),
            'end_date' => $fund->end_date->clone()->addDay()->addYear(),
            'state' => FundPeriod::STATE_PENDING,
        ]);

        // assert fund is extended once suitable pending period exists
        $this->artisan('forus.funds:extend-periods');
        $this->assertFalse($fund->refresh()->isClosed());
        $this->assertFalse($fund->refresh()->isExpired());
        $this->assertTrue($period->refresh()->isActive());

        // assert fund expires when the new end date is reached
        $this->travelTo($fund->end_date->clone()->addDay()->addMinute());
        $this->artisan('forus.funds:update-state');
        $this->artisan('forus.funds:extend-periods');
        $this->assertTrue($fund->refresh()->isClosed());
        $this->assertTrue($fund->refresh()->isExpired());

        // assert period ended
        $this->artisan('forus.funds:extend-periods');
        $this->assertTrue($period->refresh()->hasEnded());
    }
}
