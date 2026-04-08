<?php

namespace Tests\Feature\Exports;

use App\Exports\PrevalidationsExport;
use App\Models\Prevalidation;
use App\Models\PrevalidationRecord;
use App\Models\RecordType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
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
        $this->assertExportedData($response, $prevalidation, $fields);

        // Assert with passed all fields
        $url = "/api/v1/platform/organizations/$organization->id/prevalidations/export?" . http_build_query([
            'data_format' => 'csv',
            'fields' => PrevalidationsExport::getExportFieldsRaw(),
        ]);

        $response = $this->getJson($url, $apiHeaders);
        $this->assertExportedData($response, $prevalidation, $fields);

        // Assert specific fields
        $url = "/api/v1/platform/organizations/$organization->id/prevalidations/export?" . http_build_query([
            'data_format' => 'csv',
            'fields' => ['code'],
        ]);

        $response = $this->getJson($url, $apiHeaders);

        $this->assertExportedData($response, $prevalidation, [
            PrevalidationsExport::trans('code'),
        ]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPrevalidationsExportFallsBackToRecordKeyWhenVisibleLabelIsEmpty(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($organization);

        $this->addTestCriteriaToFund($fund);
        $prevalidation = $this->makePrevalidationForTestCriteria($organization, $fund);

        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/prevalidations/export?data_format=csv",
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        );

        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);
        $blankNamedRecords = $prevalidation->prevalidation_records->filter(function (PrevalidationRecord $record) {
            return !str_contains($record->record_type->key, '_eligible') &&
                ($record->record_type->name === null || $record->record_type->name === '');
        })->values();

        $this->assertGreaterThan(1, $blankNamedRecords->count());

        $blankNamedRecords->each(function (PrevalidationRecord $record) use ($rows) {
            $index = array_search($record->record_type->key, $rows[0], true);

            $this->assertNotFalse($index);
            $this->assertEquals($record->value, $rows[1][$index]);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPrevalidationsExportFallsBackToRecordKeyWhenVisibleLabelIsWhitespaceOnly(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($organization);

        $this->addTestCriteriaToFund($fund);
        $prevalidation = $this->makePrevalidationForTestCriteria($organization, $fund);

        $recordType = RecordType::create([
            'key' => 'whitespace_label_' . token_generator()->generate(12),
            'type' => RecordType::TYPE_STRING,
            'control_type' => RecordType::CONTROL_TYPE_TEXT,
            'organization_id' => $organization->id,
        ]);

        $recordType->translations()->forceCreate([
            'locale' => app()->getLocale(),
            'name' => '   ',
        ]);

        $prevalidation->prevalidation_records()->create([
            'record_type_id' => $recordType->id,
            'value' => 'whitespace label value',
        ]);

        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/prevalidations/export?data_format=csv",
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        );

        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);
        $index = array_search($recordType->key, $rows[0], true);

        $this->assertNotFalse($index);
        $this->assertEquals('whitespace label value', $rows[1][$index]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPrevalidationsExportKeepsColumnsWithBuiltInFieldKey(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($organization);

        $this->addTestCriteriaToFund($fund);
        $prevalidation = $this->makePrevalidationForTestCriteria($organization, $fund);

        $recordTypeKey = RecordType::where('key', 'code')->doesntExist() ? 'code' : 'used';
        $recordLabel = "custom $recordTypeKey";
        $recordValue = "custom $recordTypeKey value";

        $recordType = RecordType::create([
            'key' => $recordTypeKey,
            'type' => RecordType::TYPE_STRING,
            'control_type' => RecordType::CONTROL_TYPE_TEXT,
            'organization_id' => $organization->id,
        ]);

        $recordType->translations()->forceCreate([
            'locale' => app()->getLocale(),
            'name' => $recordLabel,
        ]);

        $prevalidation->prevalidation_records()->create([
            'record_type_id' => $recordType->id,
            'value' => $recordValue,
        ]);

        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/prevalidations/export?data_format=csv",
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        );

        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);
        $builtInIndex = array_search(PrevalidationsExport::trans($recordTypeKey), $rows[0], true);

        $this->assertNotFalse($builtInIndex);
        $this->assertEquals(
            $recordTypeKey === 'code'
                ? $prevalidation->uid
                : trans('export.prevalidations.used_' . ($prevalidation->state === Prevalidation::STATE_USED ? 'yes' : 'no')),
            $rows[1][$builtInIndex],
        );
        $this->assertContains($recordValue, $rows[1]);
    }

    /**
     * @param Prevalidation $prevalidation
     * @return array
     */
    protected function getExportFields(Prevalidation $prevalidation): array
    {
        $fields = Arr::pluck(PrevalidationsExport::getExportFields(), 'name');
        $fields = array_filter($fields, fn ($field) => $field !== PrevalidationsExport::trans('records'));

        $recordLabels = $prevalidation->prevalidation_records->filter(function (PrevalidationRecord $record) {
            return !str_contains($record->record_type->key, '_eligible');
        })->map(function (PrevalidationRecord $record) {
            return trim((string) $record->record_type->name) !== ''
                ? $record->record_type->name
                : $record->record_type->key;
        })->values()->all();

        return [...$fields, ...$recordLabels];
    }

    /**
     * @param TestResponse $response
     * @param Prevalidation $prevalidation
     * @param array $fields
     * @return void
     */
    protected function assertExportedData(
        TestResponse $response,
        Prevalidation $prevalidation,
        array $fields,
    ): void {
        $rows = $this->assertCsvExportResponse($response);

        $this->assertExportHeaders($rows, $fields);
        $this->assertExportCell($rows, $prevalidation->uid, 0);
    }
}
