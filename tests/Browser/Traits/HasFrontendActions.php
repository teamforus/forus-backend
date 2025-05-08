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
     * @param string $selector
     * @param int $count
     * @param string $operator
     * @throws TimeoutException
     * @return Browser
     */
    public function waitForNumber(
        Browser $browser,
        string $selector,
        int $count,
        string $operator
    ): Browser {
        return $browser->waitUsing(null, 100, function () use ($browser, $selector, $count, $operator) {
            return match ($operator) {
                '=' => (int) $browser->text($selector) === $count,
                '>=' => (int) $browser->text($selector) >= $count,
                '<=' => (int) $browser->text($selector) <= $count,
                '>' => (int) $browser->text($selector) > $count,
                '<' => (int) $browser->text($selector) < $count,
            };
        });
    }

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
     * @throws TimeOutException
     * @return void
     */
    protected function assertIdentityAuthenticatedOnValidatorDashboard(Browser $browser, Identity $identity): void
    {
        $this->assertIdentityAuthenticatedFrontend($browser, $identity, 'validator');
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
     * @param int $count
     * @param string $selector
     * @param string $operator
     * @return void
     */
    protected function assertRowsCount(Browser $browser, int $count, string $selector, string $operator = '='): void
    {
        $browser->within($selector, function (Browser $browser) use ($count, $operator) {
            if ($count === 0) {
                $browser->waitFor('@emptyCard');
            } else {
                $this->waitForNumber($browser, '@paginatorTotal', $count, $operator);

                match ($operator) {
                    '=' => $this->assertCount($count, $browser->elements('tbody>tr')),
                    '>=' => $this->assertGreaterThanOrEqual($count, $browser->elements('tbody>tr')),
                    '<=' => $this->assertLessThanOrEqual($count, $browser->elements('tbody>tr')),
                    '>' => $this->assertGreaterThan($count, $browser->elements('tbody>tr')),
                    '<' => $this->assertLessThan($count, $browser->elements('tbody>tr')),
                };
            }
        });
    }

    /**
     * @param Browser $browser
     * @param string $title
     * @param string $selector
     * @return RemoteWebElement|null
     */
    protected function findOptionElement(Browser $browser, string $selector, string $title): ?RemoteWebElement
    {
        $option = null;

        $browser->elsewhereWhenAvailable($selector . 'Options', function (Browser $browser) use (&$option, $title) {
            $xpath = WebDriverBy::xpath(".//*[contains(@class, 'select-control-option')]");
            $options = $browser->driver->findElements($xpath);
            $option = Arr::first($options, fn (RemoteWebElement $element) => trim($element->getText()) === $title);
        });

        $this->assertNotNull($option);

        return $option;
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @return void
     */
    protected function assertAndCloseSuccessNotification(Browser $browser): void
    {
        $browser->waitFor('@successNotification');
        $browser->click('@successNotification @notificationCloseBtn');
        $browser->waitUntilMissing('@successNotification');
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
}
