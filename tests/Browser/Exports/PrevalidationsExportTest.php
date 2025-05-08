<?php

namespace Tests\Browser\Exports;

use App\Exports\PrevalidationsExport;
use App\Models\Implementation;
use App\Models\Prevalidation;
use App\Models\PrevalidationRecord;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\ExportTrait;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Throwable;

class PrevalidationsExportTest extends DuskTestCase
{
    use ExportTrait;
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;

    /**
     * @throws Throwable
     * @return void
     */
    public function testPrevalidationsExport(): void
    {
        $implementation = Implementation::byKey('nijmegen');

        $fund = $this->makeTestFund($implementation->organization);

        $this->addTestCriteriaToFund($fund);
        $prevalidation = $this->makePrevalidationForTestCriteria($implementation->organization, $fund);

        $this->rollbackModels([], function () use ($implementation, $prevalidation, $fund) {
            $this->browse(function (Browser $browser) use ($implementation, $prevalidation, $fund) {
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $implementation->organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $implementation->organization->identity);
                $this->selectDashboardOrganization($browser, $implementation->organization);

                // Go to list, open export modal and assert all export fields in file
                $this->goToPrevalidationsPage($browser, $fund);
                $this->searchTable($browser, '@tablePrevalidation', $prevalidation->uid, $prevalidation->id);

                $fields = $this->getExportFields($prevalidation);

                foreach (static::FORMATS as $format) {
                    // assert all fields exported
                    $this->openFilterDropdown($browser);
                    $data = $this->fillExportModalAndDownloadFile($browser, $format);
                    $data && $this->assertFields($prevalidation, $data, $fields);

                    // assert specific fields exported
                    $this->openFilterDropdown($browser);
                    $data = $this->fillExportModalAndDownloadFile($browser, $format, ['code']);
                    $data && $this->assertFields($prevalidation, $data, [PrevalidationsExport::trans('code')]);
                }

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Prevalidation $prevalidation
     * @return array
     */
    protected function getExportFields(Prevalidation $prevalidation): array
    {
        $fields = array_pluck(PrevalidationsExport::getExportFields(), 'name');
        $fields = array_filter($fields, fn ($field) => $field !== PrevalidationsExport::trans('records'));

        $records = $prevalidation->prevalidation_records->filter(function (PrevalidationRecord $record) {
            return !str_contains($record->record_type->key, '_eligible');
        })->pluck('value', 'record_type.name')->toArray();

        return [...$fields, ...array_keys($records)];
    }

    /**
     * @param Prevalidation $prevalidation
     * @param array $rows
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        Prevalidation $prevalidation,
        array $rows,
        array $fields
    ): void {
        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);
        $this->assertEquals($prevalidation->uid, $rows[1][0]);
    }
}
