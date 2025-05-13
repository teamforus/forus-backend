<?php

namespace Tests\Browser\Traits;

use App\Exports\FundsExport;
use App\Exports\FundsExportDetailed;
use App\Models\Fund;
use App\Models\Implementation;
use Facebook\WebDriver\Exception\TimeoutException;
use Laravel\Dusk\Browser;
use Tests\Traits\MakesTestFunds;
use Throwable;

trait ExportsFundsStatisticsTrait
{
    use ExportTrait;
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;

    /**
     * @param bool $budget
     * @throws Throwable
     * @return void
     */
    protected function doTestExportFundFinancialStatistics(bool $budget): void
    {
        $implementation = Implementation::byKey('nijmegen');

        $fund = $this->makeTestFund($implementation->organization);
        $fund->makeVoucher($implementation->organization->identity);

        $this->rollbackModels([], function () use ($implementation, $fund, $budget) {
            $this->browse(function (Browser $browser) use ($implementation, $fund, $budget) {
                $organization = $implementation->organization;
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                $this->goSponsorFinancialOverviewPage($browser);
                $this->exportTable($browser, $fund, $budget);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @param bool $budget
     * @throws TimeoutException
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @return void
     */
    protected function exportTable(Browser $browser, Fund $fund, bool $budget): void
    {
        if (!$budget) {
            $fields = array_pluck(FundsExport::getExportFields(), 'name');

            foreach (static::FORMATS as $format) {
                // assert all fields exported
                $data = $this->fillExportModalAndDownloadFile(
                    browser: $browser,
                    format: $format,
                    selector: '@exportFunds'
                );

                $data && $this->assertFields($fund, $data, $fields);

                // assert specific fields exported
                $data = $this->fillExportModalAndDownloadFile($browser, $format, ['name'], '@exportFunds');
                $data && $this->assertFields($fund, $data, [FundsExport::trans('name')]);
            }

            return;
        }

        $fields = array_pluck(FundsExportDetailed::getExportFields(), 'name');

        $fields = array_values(array_filter(
            $fields,
            fn (string $field) => $field !== FundsExportDetailed::trans('budget_children_count')
        ));

        foreach (static::FORMATS as $format) {
            // assert all fields exported
            $data = $this->fillExportModalAndDownloadFile(
                browser: $browser,
                format: $format,
                selector: '@exportFundsDetailed'
            );

            $data && $this->assertFields($fund, $data, $fields);

            // assert specific fields exported
            $data = $this->fillExportModalAndDownloadFile($browser, $format, ['name'], '@exportFundsDetailed');
            $data && $this->assertFields($fund, $data, [FundsExportDetailed::trans('name')]);
        }
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
}
