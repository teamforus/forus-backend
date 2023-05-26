<?php

namespace Tests\Browser\Traits;

use App\Models\Identity;
use App\Models\Organization;
use Facebook\WebDriver\Exception\TimeoutException;
use Laravel\Dusk\Browser;
use Tests\Traits\MakesTestIdentities;

trait HasFrontendActions
{
    use MakesTestIdentities;

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @return void
     */
    protected function loginIdentity(Browser $browser, Identity $identity): void
    {
        $browser->script("localStorage.clear();");
        $browser->refresh();
        $proxy = $this->makeIdentityProxy($identity);
        $browser->script("localStorage.active_account = '$proxy->access_token';");
        $browser->refresh();
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @return void
     * @throws TimeoutException
     */
    protected function assertIdentityAuthenticatedOnWebshop(Browser $browser, Identity $identity): void
    {
        $this->assertIdentityAuthenticatedFrontend($browser, $identity, 'webshop');
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @return void
     * @throws TimeoutException
     */
    protected function assertIdentityAuthenticatedOnSponsorDashboard(
        Browser $browser,
        Identity $identity
    ): void {
        $this->assertIdentityAuthenticatedFrontend($browser, $identity, 'sponsor');
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @return void
     * @throws TimeOutException
     */
    protected function assertIdentityAuthenticatedOnProviderDashboard(Browser $browser, Identity $identity): void
    {
        $this->assertIdentityAuthenticatedFrontend($browser, $identity, 'provider');
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @param string $frontend
     * @param int $tries
     * @return Browser
     * @throws TimeoutException
     */
    protected function assertIdentityAuthenticatedFrontend(
        Browser $browser,
        Identity $identity,
        string $frontend,
        int $tries = 3,
    ): Browser {
        try {
            $browser->waitFor(match ($frontend) {
                'webshop' => $identity->email ? '@identityEmail' : '@userVouchers',
                'sponsor' => '@fundsTitle',
                'provider' => '@providerOverview',
                'validator' => '@fundRequestsPageContent',
            }, 10);
        } catch (TimeOutException $exception) {
            $browser->refresh();
            $tries--;

            if ($tries >= 0) {
                return $this->assertIdentityAuthenticatedFrontend($browser, $identity, $frontend, $tries);
            }

            throw $exception;
        }

        if ($identity->email) {
            $browser->assertSeeIn('@identityEmail', $identity->email);
        }

        return $browser;
    }

    /**
     * @param Browser $browser
     * @return void
     * @throws TimeOutException
     */
    private function logout(Browser $browser): void
    {
        $browser->waitFor('@userProfile');
        $browser->element('@userProfile')->click();

        $browser->waitFor('@btnUserLogout');
        $browser->element('@btnUserLogout')->click();

        $browser->waitUntilMissing('@userProfile');
    }

    /**
     * @param Browser $browser
     * @param Organization $organization
     * @return void
     * @throws TimeOutException
     */
    private function selectDashboardOrganization(
        Browser $browser,
        Organization $organization,
    ): void {
        $browser->waitFor('@headerOrganizationSwitcher');
        $browser->press('@headerOrganizationSwitcher');
        $browser->waitFor("@headerOrganizationItem$organization->id");
        $browser->press("@headerOrganizationItem$organization->id");
        $browser->pause(2000);
    }
}