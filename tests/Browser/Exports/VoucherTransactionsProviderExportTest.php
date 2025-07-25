<?php

namespace Tests\Browser\Exports;

use App\Exports\VoucherTransactionsProviderExport;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\ExportTrait;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\NavigatesFrontendDashboard;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Throwable;

class VoucherTransactionsProviderExportTest extends DuskTestCase
{
    use ExportTrait;
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use NavigatesFrontendDashboard;

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherTransactionsProviderExport(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fund = $this->makeTestFund($organization);
        $transaction = $this->prepareData($fund);

        $providerOrganization = $transaction->product->organization;
        $this->assertNotNull($providerOrganization);

        $this->rollbackModels([], function () use ($implementation, $providerOrganization, $transaction) {
            $this->browse(function (Browser $browser) use ($implementation, $providerOrganization, $transaction) {
                $browser->visit($implementation->urlProviderDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $providerOrganization->identity);
                $this->assertIdentityAuthenticatedOnProviderDashboard($browser, $providerOrganization->identity);
                $this->selectDashboardOrganization($browser, $providerOrganization);

                // Go to list, open export modal and assert all export fields in file
                $this->goToTransactionsPage($browser);
                $this->searchTable($browser, '@tableTransaction', $transaction->voucher->fund->name, $transaction->id);

                $fields = array_pluck(VoucherTransactionsProviderExport::getExportFields(), 'name');

                foreach (static::FORMATS as $format) {
                    // assert all fields exported
                    $this->openFilterDropdown($browser);
                    $data = $this->fillExportModalAndDownloadFile($browser, $format);
                    $data && $this->assertFields($transaction, $data, $fields);

                    // assert specific fields exported
                    $this->openFilterDropdown($browser);
                    $data = $this->fillExportModalAndDownloadFile($browser, $format, ['id']);

                    $data && $this->assertFields($transaction, $data, [
                        VoucherTransactionsProviderExport::trans('id'),
                    ]);
                }

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
