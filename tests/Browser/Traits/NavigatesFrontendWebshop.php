<?php

namespace Tests\Browser\Traits;

use Facebook\WebDriver\Exception\TimeoutException;
use Laravel\Dusk\Browser;

trait NavigatesFrontendWebshop
{
    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    protected function goToIdentityFundRequests(Browser $browser): void
    {
        $browser->waitFor('@userProfile');
        $browser->element('@userProfile')->click();
        $browser->waitFor('@btnFundRequests');
        $browser->element('@btnFundRequests')->click();
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    protected function goToIdentityReimbursements(Browser $browser): void
    {
        $browser->waitFor('@userProfile');
        $browser->element('@userProfile')->click();
        $browser->waitFor('@btnReimbursements');
        $browser->element('@btnReimbursements')->click();
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    protected function goToIdentityPayouts(Browser $browser): void
    {
        $browser->waitFor('@userProfile');
        $browser->element('@userProfile')->click();
        $browser->waitFor('@btnPayouts');
        $browser->element('@btnPayouts')->click();
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    protected function goToIdentityReservations(Browser $browser): void
    {
        $browser->waitFor('@userProfile');
        $browser->element('@userProfile')->click();
        $browser->waitFor('@btnReservations');
        $browser->element('@btnReservations')->click();
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    protected function goToIdentityVouchers(Browser $browser): void
    {
        $browser->waitFor('@userVouchers');
        $browser->element('@userVouchers')->click();
    }
}
