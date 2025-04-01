<?php

namespace Tests\Browser\Exports;

use App\Exports\FundRequestsExport;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Models\Implementation;
use Tests\Browser\Traits\ExportTrait;
use Facebook\WebDriver\Exception\TimeOutException;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Throwable;

class FundRequestsExportTest extends DuskTestCase
{
    use ExportTrait;
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use MakesTestFundRequests;

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestsExport(): void
    {
        $implementation = Implementation::byKey('nijmegen');

        $fund = $this->makeTestFund($implementation->organization);
        $fundRequest = $this->prepareData($fund);

        $this->rollbackModels([], function () use ($implementation, $fundRequest) {
            $this->browse(function (Browser $browser) use ($implementation, $fundRequest) {
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $implementation->organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $implementation->organization->identity);
                $this->selectDashboardOrganization($browser, $implementation->organization);

                // Go to list, open export modal and assert all export fields in file
                $this->goToListPage($browser);
                $this->searchFundRequest($browser, $fundRequest);

                $browser->waitFor('@showFilters');
                $browser->element('@showFilters')->click();

                $this->fillExportModal($browser);
                $csvData = $this->parseCsvFile();

                $fields = $this->getExportFields($fundRequest);
                $this->assertFields($fundRequest, $csvData, $fields);

                // Open export modal, select specific fields and assert it
                $browser->waitFor('@showFilters');
                $browser->element('@showFilters')->click();

                $this->fillExportModal($browser, ['bsn', 'fund_name']);
                $csvData = $this->parseCsvFile();

                $this->assertFields($fundRequest, $csvData, [
                    FundRequestsExport::trans('bsn'),
                    FundRequestsExport::trans('fund_name'),
                ]);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Fund $fund
     * @return FundRequest
     */
    protected function prepareData(Fund $fund): FundRequest
    {
        $records = [[
            'fund_criterion_id' => $fund->criteria[0]?->id,
            'value' => 5,
            'files' => [],
        ]];

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $this->makeFundRequest($identity, $fund, $records, false)->assertSuccessful();
        $fundRequest = $fund->fund_requests()->first();
        $this->assertNotNull($fundRequest);
        
        return $fundRequest;
    }

    /**
     * @param FundRequest $fundRequest
     * @return array
     */
    protected function getExportFields(FundRequest $fundRequest): array
    {
        $fields = array_pluck(FundRequestsExport::getExportFields(), 'name');
        $fields = array_filter($fields, fn ($field) => $field !== FundRequestsExport::trans('records'));

        $recordKeyList = FundRequestRecord::query()
            ->where('fund_request_id', $fundRequest->id)
            ->pluck('record_type_key')
            ->toArray();

        return [...$fields, ...$recordKeyList];
    }

    /**
     * @param Browser $browser
     * @return void
     * @throws TimeoutException
     */
    protected function goToListPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupFundRequests');
        $browser->element('@asideMenuGroupFundRequests')->click();
        $browser->waitFor('@fundRequestsPage');
        $browser->element('@fundRequestsPage')->click();
    }

    /**
     * @param Browser $browser
     * @param FundRequest $fundRequest
     * @return void
     * @throws TimeoutException
     */
    protected function searchFundRequest(Browser $browser, FundRequest $fundRequest): void
    {
        $browser->waitFor('@searchFundRequests');
        $browser->type('@searchFundRequests', $fundRequest->identity->email);

        $browser->waitFor("@fundRequestRow$fundRequest->id", 20);
        $browser->assertVisible("@fundRequestRow$fundRequest->id");

        $browser->waitUntil("document.querySelectorAll('#fundRequestsTable tbody tr').length === 1");
    }

    /**
     * @param FundRequest $fundRequest
     * @param array $rows
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        FundRequest $fundRequest,
        array $rows,
        array $fields
    ): void {
        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);
        $this->assertEquals($fundRequest->fund->name, $rows[1][1]);
    }
}
