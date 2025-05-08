<?php

namespace Tests\Browser\Exports;

use App\Exports\FundProvidersExport;
use App\Models\FundProvider;
use App\Models\Implementation;
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
                $this->goToSponsorProvidersPage($browser);
                $this->searchTable($browser, '@tableProvider', $fundProvider->organization->name, $fundProvider->organization->id);

                $fields = array_pluck(FundProvidersExport::getExportFields(), 'name');

                foreach (static::FORMATS as $format) {
                    // assert all fields exported
                    $this->openFilterDropdown($browser);
                    $data = $this->fillExportModalAndDownloadFile($browser, $format);
                    $data && $this->assertFields($fundProvider, $data, $fields);

                    // assert specific fields exported
                    $this->openFilterDropdown($browser);

                    $data = $this->fillExportModalAndDownloadFile($browser, $format, [
                        'fund', 'implementation', 'fund_type', 'provider',
                    ]);

                    $data && $this->assertFields($fundProvider, $data, [
                        FundProvidersExport::trans('fund'),
                        FundProvidersExport::trans('implementation'),
                        FundProvidersExport::trans('fund_type'),
                        FundProvidersExport::trans('provider'),
                    ]);
                }

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
}
