<?php

namespace Tests\Browser\Exports;

use App\Exports\ReimbursementsSponsorExport;
use App\Models\Implementation;
use App\Models\Reimbursement;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\ExportTrait;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\NavigatesFrontendDashboard;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestReimbursements;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class ReimbursementsExportTest extends DuskTestCase
{
    use ExportTrait;
    use MakesTestFunds;
    use AssertsSentEmails;
    use MakesTestVouchers;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use MakesTestReimbursements;
    use NavigatesFrontendDashboard;

    /**
     * @throws Throwable
     * @return void
     */
    public function testReimbursementsExport(): void
    {
        $implementation = Implementation::byKey('nijmegen');

        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);

        $fund = $this->makeTestFund($implementation->organization, [], [
            'allow_reimbursements' => true,
        ]);

        $voucher = $this->makeTestVoucher($fund, $this->makeIdentity($this->makeUniqueEmail()));
        $reimbursement = $this->makeReimbursement($voucher, true);

        $this->rollbackModels([], function () use ($implementation, $reimbursement) {
            $this->browse(function (Browser $browser) use ($implementation, $reimbursement) {
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $implementation->organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $implementation->organization->identity);
                $this->selectDashboardOrganization($browser, $implementation->organization);

                // Go to list, open export modal and assert all export fields in file
                $this->goToReimbursementsPage($browser);
                $this->searchTable($browser, '@tableReimbursement', $reimbursement->voucher->identity->email, $reimbursement->id);

                $fields = array_pluck(ReimbursementsSponsorExport::getExportFields(), 'name');

                foreach (static::FORMATS as $format) {
                    // assert all fields exported
                    $this->openFilterDropdown($browser);
                    $data = $this->fillExportModalAndDownloadFile($browser, $format);
                    $data && $this->assertFields($reimbursement, $data, $fields);

                    // assert specific fields exported
                    $this->openFilterDropdown($browser);
                    $data = $this->fillExportModalAndDownloadFile($browser, $format, ['id', 'code']);

                    $data && $this->assertFields($reimbursement, $data, [
                        ReimbursementsSponsorExport::trans('id'),
                        ReimbursementsSponsorExport::trans('code'),
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
     * @param Reimbursement $reimbursement
     * @param array $rows
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        Reimbursement $reimbursement,
        array $rows,
        array $fields
    ): void {
        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);
        $this->assertEquals('#' . $reimbursement->code, $rows[1][1]);
    }
}
