<?php

namespace Tests\Browser\Traits;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
use Facebook\WebDriver\Exception\TimeoutException;
use Laravel\Dusk\Browser;

trait NavigatesFrontendDashboard
{
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
     * @param string|null $tab
     * @param bool|null $skipPageNavigation
     * @throws TimeoutException
     * @return void
     */
    protected function goToProviderFundsPage(
        Browser $browser,
        ?string $tab = null,
        ?bool $skipPageNavigation = false,
    ): void {
        if (!$skipPageNavigation) {
            $browser->waitFor('@asideMenuGroupSales');
            $browser->element('@asideMenuGroupSales')->click();
            $browser->waitFor('@fundsPage');
            $browser->element('@fundsPage')->click();
        }

        if ($tab === 'funds_available') {
            $browser->waitFor('@fundsAvailableTab');
            $browser->element('@fundsAvailableTab')->click();
            $browser->waitFor('@tableFundsAvailableContent');
        }
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    protected function goToSponsorProvidersPage(Browser $browser): void
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
    protected function goToSponsorFinancialDashboardPage(Browser $browser): void
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
    protected function goToEmployeesPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupOrganization');
        $browser->element('@asideMenuGroupOrganization')->click();
        $browser->waitFor('@employeesPage');
        $browser->element('@employeesPage')->click();
    }

    /**
     * @param Browser $browser
     * @param bool $validator
     * @throws TimeoutException
     * @return void
     */
    protected function goToFundRequestsPage(Browser $browser, bool $validator = false): void
    {
        $browser->waitFor('@asideMenuGroupFundRequests');
        $browser->element('@asideMenuGroupFundRequests')->click();

        if (!$validator) {
            $browser->waitFor('@tablePrevalidationContent');
        }

        $browser->waitFor('@fundRequestsPage');
        $browser->element('@fundRequestsPage')->click();
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    protected function goSponsorFinancialOverviewPage(Browser $browser): void
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
    protected function goToPrevalidationsPage(Browser $browser, Fund $fund): void
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
    protected function goToReservationsPage(Browser $browser): void
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
    protected function goToReimbursementsPage(Browser $browser): void
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
    protected function goToTransactionsPage(Browser $browser, bool $bulks = false): void
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

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    protected function goToVouchersPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupVouchers');
        $browser->element('@asideMenuGroupVouchers')->click();
        $browser->waitFor('@vouchersPage');
        $browser->element('@vouchersPage')->click();
        $browser->waitFor('@vouchersTitle');
    }
}
