<?php

namespace Tests\Browser\Exports;

use App\Exports\FundIdentitiesExport;
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

class IdentitiesExportTest extends DuskTestCase
{
    use ExportTrait;
    use MakesTestFunds;
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
        $fund->makeVoucher($identity);

        $this->rollbackModels([], function () use ($implementation, $fund, $identity) {
            $this->browse(function (Browser $browser) use ($implementation, $fund, $identity) {
                $organization = $implementation->organization;
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                $browser->visit($implementation->urlSponsorDashboard("/organisaties/$organization->id/fondsen/$fund->id"));

                $this->goToIdentitiesTab($browser);
                $this->searchIdentity($browser, $identity);

                $browser->waitFor('@showFilters');
                $browser->element('@showFilters')->click();

                $this->fillExportModal($browser);
                $csvData = $this->parseCsvFile();

                $fields = array_pluck(FundIdentitiesExport::getExportFields(), 'name');
                $this->assertFields($identity, $csvData, $fields);

                // Open export modal, select specific fields and assert it
                $browser->waitFor('@showFilters');
                $browser->element('@showFilters')->click();

                $this->fillExportModal($browser, ['id', 'email']);
                $csvData = $this->parseCsvFile();

                $this->assertFields($identity, $csvData, [
                    FundIdentitiesExport::trans('id'),
                    FundIdentitiesExport::trans('email'),
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
    protected function goToIdentitiesTab(Browser $browser): void
    {
        $browser->waitFor('@identities_tab');
        $browser->element('@identities_tab')->click();
        $browser->waitFor('@identitiesTable');
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
        $this->assertEquals($identity->email, $rows[1][1]);
    }
}
