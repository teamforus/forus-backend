<?php

namespace Tests\Browser\Exports;

use App\Exports\FundProvidersExport;
use App\Models\FundProvider;
use App\Models\Implementation;
use App\Models\Organization;
use Facebook\WebDriver\Exception\TimeOutException;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\ExportTrait;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Throwable;

class FundProvidersExportTest extends DuskTestCase
{
    use ExportTrait;
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundProvidersExport(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $providerOrganization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $fund = $this->makeTestFund($organization);
        $fundProvider = $this->makeTestFundProvider($providerOrganization, $fund);

        $this->rollbackModels([], function () use ($implementation, $organization, $fundProvider) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $fundProvider) {
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                // Go to list, open export modal and assert all export fields in file
                $this->goToListPage($browser);
                $this->searchProvider($browser, $fundProvider->organization);
                $this->openFilterDropdown($browser);

                $this->fillExportModal($browser);
                $csvData = $this->parseCsvFile();

                $fields = array_pluck(FundProvidersExport::getExportFields(), 'name');
                $this->assertFields($fundProvider, $csvData, $fields);

                // Open export modal, select specific fields and assert it
                $this->openFilterDropdown($browser);

                $this->fillExportModal($browser, ['fund', 'implementation', 'fund_type', 'provider']);
                $csvData = $this->parseCsvFile();

                $this->assertFields($fundProvider, $csvData, [
                    FundProvidersExport::trans('fund'),
                    FundProvidersExport::trans('implementation'),
                    FundProvidersExport::trans('fund_type'),
                    FundProvidersExport::trans('provider'),
                ]);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param FundProvider $fundProvider
     * @param array $rows
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        FundProvider $fundProvider,
        array $rows,
        array $fields
    ): void {
        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);
        $this->assertEquals($fundProvider->fund->name, $rows[1][0]);
        $this->assertEquals($fundProvider->organization->name, $rows[1][3]);
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    private function goToListPage(Browser $browser): void
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
     * @param Organization $provider
     * @throws TimeoutException
     * @return void
     */
    private function searchProvider(Browser $browser, Organization $provider): void
    {
        $browser->waitFor('@searchProviders');
        $browser->typeSlowly('@searchProviders', $provider->name);

        $browser->waitFor("@providerRow$provider->id", 20);
        $browser->assertVisible("@providerRow$provider->id");

        $browser->waitUntil("document.querySelectorAll('#providersTable tbody tr').length === 1");
    }
}
