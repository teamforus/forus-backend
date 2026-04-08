<?php

namespace Tests\Feature\Exports;

use App\Exports\FundRequestsExport;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\BaseExport;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class FundRequestsExportTest extends TestCase
{
    use BaseExport;
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesTestFundRequests;
    use MakesTestOrganizations;

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestsExport(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($organization);

        $records = [[
            'fund_criterion_id' => $fund->criteria[0]?->id,
            'value' => 5,
            'files' => [],
        ]];

        $this->makeFundRequest($identity, $fund, $records, false)->assertSuccessful();
        $fundRequest = $fund->fund_requests()->first();
        $this->assertNotNull($fundRequest);

        $apiExportUrl = "/api/v1/platform/organizations/$organization->id/fund-requests/export";
        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        // Assert export without fields - must be all fields by default
        $response = $this->getJson("$apiExportUrl?data_format=csv", $apiHeaders);

        // Filter headers except records header and add all record keys
        $fields = $this->getExportFields($fundRequest);
        $this->assertExportedData($response, $fundRequest, $fields);

        // Assert with passed all fields
        $url = $apiExportUrl . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => FundRequestsExport::getExportFieldsRaw(),
        ]);

        $response = $this->getJson($url, $apiHeaders);
        $this->assertExportedData($response, $fundRequest, $fields);

        // Assert specific fields
        $url = $apiExportUrl . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => ['bsn', 'fund_name'],
        ]);

        $response = $this->getJson($url, $apiHeaders);

        $this->assertExportedData($response, $fundRequest, [
            FundRequestsExport::trans('bsn'),
            FundRequestsExport::trans('fund_name'),
        ]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestsExportKeepsColumnsWithBuiltInFieldKey(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($organization);

        $records = [[
            'fund_criterion_id' => $fund->criteria[0]?->id,
            'value' => 5,
            'files' => [],
        ]];

        $this->makeFundRequest($identity, $fund, $records, false)->assertSuccessful();
        $fundRequest = $fund->fund_requests()->first();
        $this->assertNotNull($fundRequest);

        $recordValue = 'custom fund name record';
        $fundRequest->records()->create([
            'fund_request_id' => $fundRequest->id,
            'record_type_key' => 'fund_name',
            'value' => $recordValue,
            'source' => FundRequestRecord::SOURCE_FORM,
        ]);

        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/export?data_format=csv",
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        );

        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);
        $builtInIndex = array_search(FundRequestsExport::trans('fund_name'), $rows[0], true);
        $recordIndex = array_search('fund_name', $rows[0], true);

        $this->assertNotFalse($builtInIndex);
        $this->assertNotFalse($recordIndex);
        $this->assertNotEquals($builtInIndex, $recordIndex);
        $this->assertEquals($fund->name, $rows[1][$builtInIndex]);
        $this->assertEquals($recordValue, $rows[1][$recordIndex]);
    }

    /**
     * @param FundRequest $fundRequest
     * @return array
     */
    protected function getExportFields(FundRequest $fundRequest): array
    {
        $fields = Arr::pluck(FundRequestsExport::getExportFields(), 'name');
        $fields = array_filter($fields, fn ($field) => $field !== FundRequestsExport::trans('records'));

        $recordKeyList = FundRequestRecord::query()
            ->where('fund_request_id', $fundRequest->id)
            ->pluck('record_type_key')
            ->toArray();

        return [...$fields, ...$recordKeyList];
    }

    /**
     * @param TestResponse $response
     * @param FundRequest $fundRequest
     * @param array $fields
     * @return void
     */
    protected function assertExportedData(
        TestResponse $response,
        FundRequest $fundRequest,
        array $fields,
    ): void {
        $rows = $this->assertCsvExportResponse($response);

        $this->assertExportHeaders($rows, $fields);
        $this->assertExportCell($rows, $fundRequest->fund->name, 1);
    }
}
