<?php

namespace Tests\Browser\Exports;

use App\Exports\FundsExportDetailed;
use App\Models\Fund;
use App\Models\Implementation;
use Facebook\WebDriver\Exception\TimeoutException;
use Tests\Browser\Traits\ExportTrait;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Throwable;

class FundDetailedExportTest extends DuskTestCase
{
    use ExportTrait;
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundDetailedExport(): void
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

                $this->fillExportModal($browser, [], '@exportFundsDetailed');
                $csvData = $this->parseCsvFile();

                $fields = array_pluck(FundsExportDetailed::getExportFields(), 'name');
                $fields = array_values(array_filter(
                    $fields,
                    fn (string $field) => $field !== FundsExportDetailed::trans('budget_children_count')
                ));

                $this->assertFields($fund, $csvData, $fields);

                // Open export modal, select specific fields and assert it
                $this->fillExportModal($browser, ['name'], '@exportFundsDetailed');
                $csvData = $this->parseCsvFile();

                $this->assertFields($fund, $csvData, [
                    FundsExportDetailed::trans('name'),
                ]);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Browser $browser
     * @return void
     * @throws TimeoutException
     */
    private function goToListPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupReports');
        $browser->element('@asideMenuGroupReports')->click();
        $browser->waitFor('@financialDashboardOverviewPage');
        $browser->element('@financialDashboardOverviewPage')->click();
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

        $item = array_first($rows, fn($row) => $row[0] === $fund->name);
        $this->assertEquals($fund->name, $item[0] ?? null);
    }
}
