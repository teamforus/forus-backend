<?php

namespace Tests\Browser\Traits;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Support\Arr;
use InvalidArgumentException;
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
     * @param string|null $message
     * @throws TimeoutException
     * @return Browser
     */
    public function waitForNumber(
        Browser $browser,
        string $selector,
        int $count,
        string $operator,
        string $message = null,
    ): Browser {
        return $browser->waitUsing(null, 100, function () use ($browser, $selector, $count, $operator) {
            return match ($operator) {
                '=' => (int) $browser->text($selector) === $count,
                '>=' => (int) $browser->text($selector) >= $count,
                '<=' => (int) $browser->text($selector) <= $count,
                '>' => (int) $browser->text($selector) > $count,
                '<' => (int) $browser->text($selector) < $count,
            };
        }, $message);
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    protected function goSponsorProfilesPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupIdentities');
        $browser->element('@asideMenuGroupIdentities')->click();
        $browser->waitFor('@identitiesPage');
        $browser->element('@identitiesPage')->click();
    }

    /**
     * @param Browser $browser
     * @param Implementation $implementation
     * @param Organization $organization
     * @param Fund $fund
     * @param string|null $tab
     * @throws TimeoutException
     * @return void
     */
    protected function goToSponsorFundDetailsPageTab(
        Browser $browser,
        Implementation $implementation,
        Organization $organization,
        Fund $fund,
        string $tab = null
    ): void {
        $browser->visit($implementation->urlSponsorDashboard("/organisaties/$organization->id/fondsen/$fund->id"));

        if ($tab === 'identities') {
            $browser->waitFor('@identities_tab');
            $browser->element('@identities_tab')->click();
            $browser->waitFor('@tableIdentityContent');
        }
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @return void
     */
    private function loginIdentity(Browser $browser, Identity $identity): void
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
    private function assertIdentityAuthenticatedOnWebshop(Browser $browser, Identity $identity): void
    {
        $this->assertIdentityAuthenticatedFrontend($browser, $identity, 'webshop');
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @throws TimeoutException
     * @return void
     */
    private function assertIdentityAuthenticatedOnSponsorDashboard(
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
    private function assertIdentityAuthenticatedOnProviderDashboard(Browser $browser, Identity $identity): void
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
    private function assertIdentityAuthenticatedFrontend(
        Browser $browser,
        Identity $identity,
        string $frontend,
    ): void {
        $browser->waitFor(match ($frontend) {
            'webshop' => $identity->email ? '@identityEmail' : '@userVouchers',
            'sponsor' => '@fundsTitle',
            'provider' => '@providerOverview',
            'validator' => '@tableFundRequestContent',
        }, 10);

        if ($identity->email) {
            $browser->assertSeeIn('@identityEmail', $identity->email);
        }
    }

    /**
     * @param Browser $browser
     * @param string $selector
     * @param string $title
     * @return RemoteWebElement|null
     */
    private function findOptionElement(Browser $browser, string $selector, string $title): ?RemoteWebElement
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
     * @param int $count
     * @param string $selector
     * @param string $operator
     * @return void
     */
    private function assertRowsCount(Browser $browser, int $count, string $selector, string $operator = '='): void
    {
        $browser->within($selector, function (Browser $browser) use ($count, $operator, $selector) {
            if ($count === 0 && $operator === '=') {
                $browser->waitUntilMissing('@paginatorTotal');
            } else {
                $this->waitForNumber(
                    $browser,
                    '@paginatorTotal',
                    $count,
                    $operator,
                    "Timed out waiting for paginator total to be $operator $count (selector \"$selector\").",
                );
            }

            $rows = $browser->elements('tbody>tr');
            $rowCount = count($rows);

            $message = "Assertion failed for \"$selector\": expected rows $operator $count, got $rowCount.";

            match ($operator) {
                '=' => $this->assertCount($count, $rows, $message),
                '>=' => $this->assertGreaterThanOrEqual($count, $rowCount, $message),
                '<=' => $this->assertLessThanOrEqual($count, $rowCount, $message),
                '>' => $this->assertGreaterThan($count, $rowCount, $message),
                '<' => $this->assertLessThan($count, $rowCount, $message),
                default => throw new InvalidArgumentException("Invalid operator \"$operator\""),
            };
        });
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @return void
     */
    private function assertAndCloseSuccessNotification(Browser $browser): void
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

        // close all filters if not closed before logout - filters can be over logout btn
        if ($browser->element('@hideFilters')) {
            $browser->element('@hideFilters')->click();
            $browser->waitFor('@showFilters');
        }

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
     * @param string $selector
     * @param string $value
     * @param string|null $id
     * @param int $expected
     * @return void
     * @throws TimeoutException
     */
    private function searchTable(
        Browser $browser,
        string $selector,
        string $value,
        ?string $id,
        int $expected = 1,
    ): void {
        $browser->waitFor($selector . 'Search');
        $browser->typeSlowly($selector . 'Search', $value, 1);

        if ($id !== null) {
            $browser->waitFor($selector . "Row$id");
            $browser->assertVisible($selector . "Row$id");
        }

        $this->assertRowsCount($browser, $expected, $selector . 'Content');
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    private function goToSponsorProvidersPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupProviders');
        $browser->element('@asideMenuGroupProviders')->click();
        $browser->waitFor('@providersPage');
        $browser->element('@providersPage')->click();
        $browser->waitFor('@provider_tab_active');
        $browser->element('@provider_tab_active')->click();
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    private function goToSponsorFinancialDashboardPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupReports');
        $browser->element('@asideMenuGroupReports')->click();
        $browser->waitFor('@financialDashboardPage');
        $browser->element('@financialDashboardPage')->click();
    }

    /**
     * @param Browser $browser
     * @throws TimeOutException
     * @return void
     */
    private function goToEmployeesPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupOrganization');
        $browser->element('@asideMenuGroupOrganization')->click();
        $browser->waitFor('@employeesPage');
        $browser->element('@employeesPage')->click();
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    private function goToFundRequestsPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupFundRequests');
        $browser->element('@asideMenuGroupFundRequests')->click();
        $browser->waitFor('@fundRequestsPage');
        $browser->element('@fundRequestsPage')->click();
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    private function goSponsorFinancialOverviewPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupReports');
        $browser->element('@asideMenuGroupReports')->click();
        $browser->waitFor('@financialDashboardOverviewPage');
        $browser->element('@financialDashboardOverviewPage')->click();
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @throws TimeoutException
     * @return void
     */
    private function goToPrevalidationsPage(Browser $browser, Fund $fund): void
    {
        $browser->waitFor('@asideMenuGroupFundRequests');
        $browser->element('@asideMenuGroupFundRequests')->click();
        $browser->waitFor('@csvValidationPage');
        $browser->element('@csvValidationPage')->click();

        $browser->waitFor('@prevalidationSelectFund');
        $browser->within('@prevalidationSelectFund', function (Browser $browser) use ($fund) {
            $browser->element('@selectControlFunds')->click();

            $browser->waitFor("@selectControlFundItem$fund->id");
            $browser->element("@selectControlFundItem$fund->id")->click();
        });
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    private function goToReservationsPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupSales');
        $browser->element('@asideMenuGroupSales')->click();
        $browser->waitFor('@reservationsPage');
        $browser->element('@reservationsPage')->click();
        $browser->waitFor('@reservationsTitle');
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    private function goToReimbursementsPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupVouchers');
        $browser->element('@asideMenuGroupVouchers')->click();
        $browser->waitFor('@reimbursementsPage');
        $browser->element('@reimbursementsPage')->click();
    }

    /**
     * @param Browser $browser
     * @param bool $bulks
     * @throws TimeoutException
     * @return void
     */
    private function goToTransactionsPage(Browser $browser, bool $bulks = false): void
    {
        $browser->waitFor('@asideMenuGroupFinancial');
        $browser->element('@asideMenuGroupFinancial')->click();
        $browser->waitFor('@transactionsPage');
        $browser->element('@transactionsPage')->click();

        if ($bulks) {
            $browser->waitFor('@transaction_view_bulks');
            $browser->element('@transaction_view_bulks')->click();
        }
    }
}
