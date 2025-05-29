<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\TestsReservations;
use Throwable;

class BackofficeTest extends DuskTestCase
{
    use WithFaker;
    use MakesTestFunds;
    use TestsReservations;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use MakesTestFundRequests;

    /**
     * @throws Throwable
     * @return void
     */
    public function testAuthRedirectWithOneFundWithoutVoucher()
    {

    }
}
