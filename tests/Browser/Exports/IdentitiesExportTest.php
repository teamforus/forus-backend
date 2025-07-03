<?php

namespace Tests\Browser\Exports;

use App\Exports\FundIdentitiesExport;
use App\Models\Identity;
use App\Models\Implementation;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\ExportTrait;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class IdentitiesExportTest extends DuskTestCase
{
    use ExportTrait;
    use MakesTestFunds;
    use MakesTestVouchers;
    use HasFrontendActions;
    use RollbackModelsTrait;

    /**
     * @throws Throwable
     * @return void
     */
    public function testIdentitiesExport(): void
    {
        $implementation = Implementation::byKey('nijmegen');

        $fund = $this->makeTestFund($implementation->organization);
        $identity = $implementation->organization->identity;
        $this->makeTestVoucher($fund, $identity);

        $this->rollbackModels([], function () use ($implementation, $fund, $identity) {
            $this->browse(function (Browser $browser) use ($implementation, $fund, $identity) {
                $organization = $implementation->organization;
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                $browser->pause(500);

                $this->goToSponsorFundDetailsPageTab($browser, $implementation, $organization, $fund, 'identities');
                $this->searchTable($browser, '@tableIdentity', $identity->email, $identity->id);

                $fields = array_pluck(FundIdentitiesExport::getExportFields(), 'name');

                foreach (static::FORMATS as $format) {
                    // assert all fields exported
                    $this->openFilterDropdown($browser);
                    $data = $this->fillExportModalAndDownloadFile($browser, $format);
                    $data && $this->assertFields($identity, $data, $fields);

                    // assert specific fields exported
                    $this->openFilterDropdown($browser);
                    $data = $this->fillExportModalAndDownloadFile($browser, $format, ['id', 'email']);

                    $data && $this->assertFields($identity, $data, [
                        FundIdentitiesExport::trans('id'),
                        FundIdentitiesExport::trans('email'),
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
     * @param Identity $identity
     * @param array $rows
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        Identity $identity,
        array $rows,
        array $fields
    ): void {
        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);
        $this->assertEquals($identity->email, $rows[1][1]);
    }
}
