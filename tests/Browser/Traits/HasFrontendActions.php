<?php

namespace Tests\Browser\Traits;

use App\Models\Identity;
use App\Models\Organization;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Support\Arr;
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
        $browser->script('localStorage.clear();');
        $browser->refresh();
        $proxy = $this->makeIdentityProxy($identity);
        $browser->script("localStorage.active_account = '$proxy->access_token';");
        $browser->refresh();
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @throws TimeoutException
     * @return void
     */
    protected function assertIdentityAuthenticatedOnWebshop(Browser $browser, Identity $identity): void
    {
        $this->assertIdentityAuthenticatedFrontend($browser, $identity, 'webshop');
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @throws TimeoutException
     * @return void
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
     * @throws TimeOutException
     * @return void
     */
    protected function assertIdentityAuthenticatedOnProviderDashboard(Browser $browser, Identity $identity): void
    {
        $this->assertIdentityAuthenticatedFrontend($browser, $identity, 'provider');
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @param string $frontend
     * @throws TimeOutException
     * @return void
     */
    protected function assertIdentityAuthenticatedFrontend(
        Browser $browser,
        Identity $identity,
        string $frontend,
    ): void {
        $browser->waitFor(match ($frontend) {
            'webshop' => $identity->email ? '@identityEmail' : '@userVouchers',
            'sponsor' => '@fundsTitle',
            'provider' => '@providerOverview',
            'validator' => '@fundRequestsPageContent',
        }, 10);

        if ($identity->email) {
            $browser->assertSeeIn('@identityEmail', $identity->email);
        }
    }

    /**
     * @param Browser $browser
     * @throws TimeOutException
     * @return void
     */
    private function logout(Browser $browser): void
    {
        $browser->pause(100);
        $browser->waitFor('@userProfile');
        $browser->scrollIntoView('@userProfile');
        $browser->element('@userProfile')->click();

        $browser->waitFor('@btnUserLogout')->waitFor('@btnUserLogout');
        $browser->element('@btnUserLogout')->click();

        $browser->waitUntilMissing('@userProfile');
    }

    /**
     * @param Browser $browser
     * @param Organization $organization
     * @throws TimeOutException
     * @return void
     */
    private function selectDashboardOrganization(
        Browser $browser,
        Organization $organization,
    ): void {
        $browser->waitFor('@headerOrganizationSwitcher');
        $browser->press('@headerOrganizationSwitcher');
        $browser->waitFor("@headerOrganizationItem$organization->id");
        $browser->press("@headerOrganizationItem$organization->id");
    }

    /**
     * @param Browser $browser
     * @param int $fundId
     * @throws TimeOutException
     * @return void
     */
    private function switchToFund(Browser $browser, int $fundId): void
    {
        $browser->waitFor('@selectControlFunds');
        $browser->element('@selectControlFunds')->click();

        $browser->waitFor("@selectControlFundItem$fundId");
        $browser->element("@selectControlFundItem$fundId")->click();
    }

    /**
     * @param Browser $browser
     * @param string $title
     * @param string $selector
     * @return RemoteWebElement|null
     */
    protected function findOptionElement(Browser $browser, string $title, string $selector = '@selectControlOptions'): ?RemoteWebElement
    {
        $option = null;

        $browser->elsewhereWhenAvailable($selector, function (Browser $browser) use (&$option, $title) {
            $xpath = WebDriverBy::xpath(".//*[contains(@class, 'select-control-option')]");
            $options = $browser->driver->findElements($xpath);
            $option = Arr::first($options, fn (RemoteWebElement $element) => trim($element->getText()) === $title);
        });

        $this->assertNotNull($option);

        return $option;
    }
}
