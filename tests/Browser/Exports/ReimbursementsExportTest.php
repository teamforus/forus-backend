<?php

namespace Tests\Browser\Exports;

use App\Exports\ReimbursementsSponsorExport;
use App\Models\Implementation;
use App\Models\Reimbursement;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Facebook\WebDriver\Exception\TimeOutException;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\ExportTrait;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesReimbursements;
use Tests\Traits\MakesTestFunds;
use Throwable;

class ReimbursementsExportTest extends DuskTestCase
{
    use ExportTrait;
    use MakesTestFunds;
    use AssertsSentEmails;
    use HasFrontendActions;
    use MakesReimbursements;
    use RollbackModelsTrait;

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

        $voucher = $fund->makeVoucher($this->makeIdentity($this->makeUniqueEmail()));
        $reimbursement = $this->makeTestReimbursement($voucher, true);

        $this->rollbackModels([], function () use ($implementation, $reimbursement) {
            $this->browse(function (Browser $browser) use ($implementation, $reimbursement) {
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $implementation->organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $implementation->organization->identity);
                $this->selectDashboardOrganization($browser, $implementation->organization);

                // Go to list, open export modal and assert all export fields in file
                $this->goToListPage($browser);
                $this->searchReimbursement($browser, $reimbursement);

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
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    protected function goToListPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupVouchers');
        $browser->element('@asideMenuGroupVouchers')->click();
        $browser->waitFor('@reimbursementsPage');
        $browser->element('@reimbursementsPage')->click();
    }

    /**
     * @param Browser $browser
     * @param Reimbursement $reimbursement
     * @throws TimeoutException
     * @return void
     */
    protected function searchReimbursement(Browser $browser, Reimbursement $reimbursement): void
    {
        $browser->waitFor('@searchReimbursement');
        $browser->type('@searchReimbursement', $reimbursement->voucher->identity->email);

        $browser->waitFor("@reimbursement$reimbursement->id", 20);
        $browser->assertVisible("@reimbursement$reimbursement->id");

        $this->assertRowsCount($browser, 1, '@reimbursementsPageContent');
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
