<?php

namespace Tests\Browser\Exports;

use App\Exports\IdentityProfilesExport;
use App\Models\Identity;
use App\Models\Implementation;
use Facebook\WebDriver\Exception\TimeOutException;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\ExportTrait;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Throwable;

class IdentityProfilesExportTest extends DuskTestCase
{
    use ExportTrait;
    use MakesTestFunds;
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
        $fund->makeVoucher($identity);

        $this->rollbackModels([], function () use ($implementation, $identity) {
            $this->browse(function (Browser $browser) use ($implementation, $identity) {
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $implementation->organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $implementation->organization->identity);
                $this->selectDashboardOrganization($browser, $implementation->organization);

                // Go to list, open export modal and assert all export fields in file
                $this->goToListPage($browser);
                $this->searchIdentity($browser, $identity);

                $fields = array_pluck(IdentityProfilesExport::getExportFields($implementation->organization), 'name');

                foreach (static::FORMATS as $format) {
                    // assert all fields exported
                    $this->openFilterDropdown($browser);
                    $data = $this->fillExportModalAndDownloadFile($browser, $format);
                    $data && $this->assertFields($identity, $data, $fields);

                    // assert specific fields exported
                    $this->openFilterDropdown($browser);
                    $data = $this->fillExportModalAndDownloadFile($browser, $format, [
                        'id', 'given_name', 'family_name', 'email',
                    ]);

                    $data && $this->assertFields($identity, $data, [
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
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    protected function goToListPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupIdentities');
        $browser->element('@asideMenuGroupIdentities')->click();
        $browser->waitFor('@identitiesPage');
        $browser->element('@identitiesPage')->click();
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @throws TimeoutException
     * @return void
     */
    protected function searchIdentity(Browser $browser, Identity $identity): void
    {
        $browser->waitFor('@searchIdentities');
        $browser->type('@searchIdentities', $identity->email);

        $browser->waitFor("@identityRow$identity->id", 20);
        $browser->assertVisible("@identityRow$identity->id");

        $this->assertRowsCount($browser, 1, '@identitiesPageContent');
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
        $this->assertEquals($identity->email, $rows[1][3]);
    }
}
