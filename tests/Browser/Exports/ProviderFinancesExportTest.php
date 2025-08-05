<?php

namespace Tests\Browser\Exports;

use App\Exports\ProviderFinancesExport;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
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

class ProviderFinancesExportTest extends DuskTestCase
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
    public function testProviderFinancesExport(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fund = $this->makeTestFund($organization);
        $providerOrganization = $this->prepareData($fund);

        $this->rollbackModels([], function () use ($implementation, $organization, $providerOrganization) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $providerOrganization) {
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                // Go to list, open export modal and assert all export fields in file
                $this->goToSponsorFinancialDashboardPage($browser);

                $fields = array_pluck(ProviderFinancesExport::getExportFields(), 'name');

                foreach (static::FORMATS as $format) {
                    // assert all fields exported
                    $data = $this->fillExportModalAndDownloadFile($browser, $format);
                    $data && $this->assertFields($providerOrganization, $data, $fields);

                    // assert specific fields exported
                    $data = $this->fillExportModalAndDownloadFile($browser, $format, ['provider']);

                    $data && $this->assertFields($providerOrganization, $data, [
                        ProviderFinancesExport::trans('provider'),
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
     * @param Fund $fund
     * @return Organization
     */
    protected function prepareData(Fund $fund): Organization
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

        $providerOrganization = $fund->vouchers()->first()->product->organization;
        $this->assertNotNull($providerOrganization);

        return $providerOrganization;
    }

    /**
     * @param Organization $organization
     * @param array $rows
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        Organization $organization,
        array $rows,
        array $fields
    ): void {
        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);

        $item = array_first($rows, fn ($row) => $row[0] === $organization->name);
        $this->assertEquals($organization->name, $item[0] ?? null);
    }
}
