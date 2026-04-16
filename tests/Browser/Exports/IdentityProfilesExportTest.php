<?php

namespace Tests\Browser\Exports;

use App\Exports\IdentityProfilesExport;
use App\Models\Identity;
use App\Models\Implementation;
use Illuminate\Support\Arr;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\ExportTrait;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class IdentityProfilesExportTest extends DuskTestCase
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
    public function testIdentityProfilesExport(): void
    {
        $implementation = Implementation::byKey('nijmegen');

        $fund = $this->makeTestFund($implementation->organization);
        $identity = $implementation->organization->identity;
        $this->makeTestVoucher($fund, $identity);

        $this->rollbackModels([], function () use ($implementation, $identity) {
            $this->browse(function (Browser $browser) use ($implementation, $identity) {
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $implementation->organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $implementation->organization->identity);
                $this->selectDashboardOrganization($browser, $implementation->organization);

                // Go to list, open export modal and assert all export fields in file
                $this->goSponsorProfilesPage($browser);
                $this->searchTable($browser, '@tableProfiles', $identity->email, $identity->id);

                $fields = Arr::pluck(IdentityProfilesExport::getExportFields($implementation->organization), 'name');

                foreach (static::FORMATS as $format) {
                    // assert all fields exported
                    $this->openFilterDropdown($browser);
                    $data = $this->fillExportModalAndDownloadFile($browser, $format);
                    $data && $this->assertExportedData($identity, $data, $fields);

                    // assert specific fields exported
                    $this->openFilterDropdown($browser);
                    $data = $this->fillExportModalAndDownloadFile($browser, $format, [
                        'id', 'given_name', 'family_name', 'email',
                    ]);

                    $data && $this->assertExportedData($identity, $data, [
                        IdentityProfilesExport::trans('id'),
                        IdentityProfilesExport::trans('given_name'),
                        IdentityProfilesExport::trans('family_name'),
                        IdentityProfilesExport::trans('email'),
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
    protected function assertExportedData(
        Identity $identity,
        array $rows,
        array $fields
    ): void {
        $this->assertExportHeaders($rows, $fields);
        $this->assertExportCell($rows, $identity->email, 3);
    }
}
