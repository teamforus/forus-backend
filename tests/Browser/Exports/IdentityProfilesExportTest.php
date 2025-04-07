<?php

namespace Tests\Browser\Exports;

use App\Exports\IdentityProfilesExport;
use App\Models\Identity;
use App\Models\Implementation;
use Tests\Browser\Traits\ExportTrait;
use Facebook\WebDriver\Exception\TimeOutException;
use Laravel\Dusk\Browser;
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
                $this->openFilterDropdown($browser);

                $this->fillExportModal($browser);
                $csvData = $this->parseCsvFile();

                $fields = array_pluck(IdentityProfilesExport::getExportFields($implementation->organization), 'name');
                $this->assertFields($identity, $csvData, $fields);

                // Open export modal, select specific fields and assert it
                $this->openFilterDropdown($browser);

                $this->fillExportModal($browser, ['id', 'given_name', 'family_name', 'email']);
                $csvData = $this->parseCsvFile();

                $this->assertFields($identity, $csvData, [
                    IdentityProfilesExport::trans('id'),
                    IdentityProfilesExport::trans('given_name'),
                    IdentityProfilesExport::trans('family_name'),
                    IdentityProfilesExport::trans('email'),
                ]);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Browser $browser
     * @return void
     * @throws TimeoutException
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
     * @return void
     * @throws TimeoutException
     */
    protected function searchIdentity(Browser $browser, Identity $identity): void
    {
        $browser->waitFor('@searchIdentities');
        $browser->type('@searchIdentities', $identity->email);

        $browser->waitFor("@identityRow$identity->id", 20);
        $browser->assertVisible("@identityRow$identity->id");

        $browser->waitUntil("document.querySelectorAll('#identitiesTable tbody tr').length === 1");
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
