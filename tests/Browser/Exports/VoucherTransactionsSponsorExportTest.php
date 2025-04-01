<?php

namespace Tests\Browser\Exports;

use App\Exports\VoucherTransactionsSponsorExport;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use Tests\Browser\Traits\ExportTrait;
use Facebook\WebDriver\Exception\TimeOutException;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Throwable;

class VoucherTransactionsSponsorExportTest extends DuskTestCase
{
    use ExportTrait;
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherTransactionsSponsorExport(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fund = $this->makeTestFund($organization);
        $transaction = $this->prepareData($fund);

        $this->rollbackModels([], function () use ($implementation, $organization, $transaction) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $transaction) {
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                // Go to list, open export modal and assert all export fields in file
                $this->goToListPage($browser);
                $this->searchTransaction($browser, $transaction);

                $browser->waitFor('@showFilters');
                $browser->element('@showFilters')->click();

                $this->fillExportModal($browser);
                $csvData = $this->parseCsvFile();

                $fields = array_pluck(VoucherTransactionsSponsorExport::getExportFields(), 'name');
                $this->assertFields($transaction, $csvData, $fields);

                // Open export modal, select specific fields and assert it
                $browser->waitFor('@showFilters');
                $browser->element('@showFilters')->click();

                $this->fillExportModal($browser, ['id']);
                $csvData = $this->parseCsvFile();

                $this->assertFields($transaction, $csvData, [
                    VoucherTransactionsSponsorExport::trans('id'),
                ]);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund, $transaction) {
            $transaction && $transaction->delete();
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Fund $fund
     * @return VoucherTransaction
     */
    protected function prepareData(Fund $fund): VoucherTransaction
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

                $voucher->makeTransaction($params)->setPaid(null, now());
            });

        $transaction = $fund->vouchers()->first()->transactions->first();
        $this->assertNotNull($transaction);

        return $transaction;
    }

    /**
     * @param Browser $browser
     * @param VoucherTransaction $transaction
     * @return void
     * @throws TimeoutException
     */
    protected function searchTransaction(Browser $browser, VoucherTransaction $transaction): void
    {
        $browser->waitFor('@searchTransaction');
        $browser->type('@searchTransaction', $transaction->voucher->fund->name);

        $browser->waitFor("@transactionItem$transaction->id", 20);
        $browser->assertVisible("@transactionItem$transaction->id");

        $browser->waitUntil("document.querySelectorAll('#transactionsTable tbody tr').length === 1");
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
    }

    /**
     * @param VoucherTransaction $transaction
     * @param array $rows
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        VoucherTransaction $transaction,
        array $rows,
        array $fields
    ): void {
        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);

        $this->assertEquals($transaction->id, $rows[1][0]);
    }
}
