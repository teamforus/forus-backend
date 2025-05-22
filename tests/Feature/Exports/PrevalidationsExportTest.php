<?php

namespace Tests\Feature\Exports;

use App\Exports\PrevalidationsExport;
use App\Models\Prevalidation;
use App\Models\PrevalidationRecord;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\BaseExport;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class PrevalidationsExportTest extends TestCase
{
    use BaseExport;
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * @throws Throwable
     * @return void
     */
    public function testPrevalidationsExport(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($organization);

        $this->addTestCriteriaToFund($fund);
        $prevalidation = $this->makePrevalidationForTestCriteria($organization, $fund);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        // Assert export without fields - must be all fields by default
        $response = $this->getJson("/api/v1/platform/organizations/$organization->id/prevalidations/export?data_format=csv", $apiHeaders);
        $fields = $this->getExportFields($prevalidation);
        $this->assertFields($response, $prevalidation, $fields);

        // Assert with passed all fields
        $url = "/api/v1/platform/organizations/$organization->id/prevalidations/export?" . http_build_query([
            'data_format' => 'csv',
            'fields' => PrevalidationsExport::getExportFieldsRaw(),
        ]);

        $response = $this->getJson($url, $apiHeaders);
        $this->assertFields($response, $prevalidation, $fields);

        // Assert specific fields
        $url = "/api/v1/platform/organizations/$organization->id/prevalidations/export?" . http_build_query([
            'data_format' => 'csv',
            'fields' => ['code'],
        ]);

        $response = $this->getJson($url, $apiHeaders);

        $this->assertFields($response, $prevalidation, [
            PrevalidationsExport::trans('code'),
        ]);
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
     * @param TestResponse $response
     * @param Prevalidation $prevalidation
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        TestResponse $response,
        Prevalidation $prevalidation,
        array $fields,
    ): void {
        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);

        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);

        // Assert specific fields
        $this->assertEquals($prevalidation->uid, $rows[1][0]);
    }
}
