<?php

namespace Tests\Browser\Exports;

use App\Exports\VoucherTransactionBulksExport;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Models\VoucherTransactionBulk;
use Tests\Browser\Traits\ExportTrait;
use Facebook\WebDriver\Exception\TimeOutException;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestBankConnections;
use Tests\Traits\MakesTestFunds;
use Throwable;

class VoucherTransactionBulksExportTest extends DuskTestCase
{
    use ExportTrait;
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use MakesTestBankConnections;

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherTransactionBulksExport(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fund = $this->makeTestFund($organization);
        $bulk = $this->prepareData($fund);

        $this->rollbackModels([], function () use ($implementation, $organization, $bulk) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $bulk) {
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                // Go to list, open export modal and assert all export fields in file
                $this->goToListPage($browser);

                $browser->waitFor('@showFilters');
                $browser->element('@showFilters')->click();

                $this->fillExportModal($browser);
                $csvData = $this->parseCsvFile();

                $fields = array_pluck(VoucherTransactionBulksExport::getExportFields(), 'name');
                $this->assertFields($bulk, $csvData, $fields);

                // Open export modal, select specific fields and assert it
                $browser->waitFor('@showFilters');
                $browser->element('@showFilters')->click();

                $this->fillExportModal($browser, ['id', 'quantity']);
                $csvData = $this->parseCsvFile();

                $this->assertFields($bulk, $csvData, [
                    VoucherTransactionBulksExport::trans('id'),
                    VoucherTransactionBulksExport::trans('quantity'),
                ]);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund, $bulk) {
            if ($bulk) {
                $bulk->voucher_transactions()->update(['voucher_transaction_bulk_id' => null]);
                $bulk->voucher_transactions()->delete();
                $bulk->delete();
            }

            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Fund $fund
     * @return VoucherTransactionBulk
     */
    protected function prepareData(Fund $fund): VoucherTransactionBulk
    {
        $this
            ->makeProductVouchers($fund, 1, 1)
            ->each(function (Voucher $voucher) use ($fund) {
                $employee = $fund->organization->employees[0];
                $params = [
                    'amount' => $voucher->amount,
                    'product_id' => $voucher->product_id,
                    'employee_id' => $employee?->id,
                    'branch_id' => $employee?->office?->branch_id,
                    'branch_name' => $employee?->office?->branch_name,
                    'branch_number' => $employee?->office?->branch_number,
                    'target' => VoucherTransaction::TARGET_PROVIDER,
                    'organization_id' => $voucher->product->organization_id,
                ];

                $voucher->makeTransaction($params);
            });

        $this->makeBankConnection($fund->organization);
        $list = VoucherTransactionBulk::buildBulksForOrganization($fund->organization);

        $bulk = VoucherTransactionBulk::find($list[0] ?? null);
        $this->assertNotNull($bulk);

        return $bulk;
    }

    /**
     * @param Browser $browser
     * @return void
     * @throws TimeoutException
     */
    protected function goToListPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupFinancial');
        $browser->element('@asideMenuGroupFinancial')->click();
        $browser->waitFor('@transactionsPage');
        $browser->element('@transactionsPage')->click();
        $browser->waitFor('@transaction_view_bulks');
        $browser->element('@transaction_view_bulks')->click();
    }

    /**
     * @param VoucherTransactionBulk $bulk
     * @param array $rows
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        VoucherTransactionBulk $bulk,
        array $rows,
        array $fields
    ): void {
        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);

        $this->assertEquals($bulk->id, $rows[1][0]);
        $this->assertEquals($bulk->voucher_transactions()->count(), $rows[1][1]);
    }
}
