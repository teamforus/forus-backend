<?php

namespace Tests\Browser\Exports;

use App\Exports\FundsExport;
use App\Models\Fund;
use App\Models\Implementation;
use Facebook\WebDriver\Exception\TimeoutException;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\ExportTrait;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Throwable;

class FundsExportTest extends DuskTestCase
{
    use ExportTrait;
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundsExport(): void
    {
        $implementation = Implementation::byKey('nijmegen');

        $fund = $this->makeTestFund($implementation->organization);
        $fund->makeVoucher($implementation->organization->identity);

        $this->rollbackModels([], function () use ($implementation, $fund) {
            $this->browse(function (Browser $browser) use ($implementation, $fund) {
                $organization = $implementation->organization;
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                $this->goToListPage($browser);

                $fields = array_pluck(FundsExport::getExportFields(), 'name');

                foreach (static::FORMATS as $format) {
                    // assert all fields exported
                    $csvData = $this->fillExportModalAndDownloadFile(
                        browser: $browser,
                        format: $format,
                        selector: '@exportFunds'
                    );

                    $this->assertFields($fund, $csvData, $fields);

                    // assert specific fields exported
                    $csvData = $this->fillExportModalAndDownloadFile($browser, $format, ['name'], '@exportFunds');
                    $this->assertFields($fund, $csvData, [FundsExport::trans('name')]);
                }

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Fund $fund
     * @param array $rows
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        Fund $fund,
        array $rows,
        array $fields
    ): void {
        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);

        $item = array_first($rows, fn ($row) => $row[0] === $fund->name);
        $this->assertEquals($fund->name, $item[0] ?? null);
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    private function goToListPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupReports');
        $browser->element('@asideMenuGroupReports')->click();
        $browser->waitFor('@financialDashboardOverviewPage');
        $browser->element('@financialDashboardOverviewPage')->click();
    }
}
