<?php

namespace Tests\Feature\Exports;

use App\Exports\FundRequestsExport;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
     * @var string
     */
    protected string $apiExportUrl = '/api/v1/platform/organizations/%s/fund-requests/export';

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

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        // Assert export without fields - must be all fields by default
        $response = $this->get(
            sprintf($this->apiExportUrl, $organization->id) . '?data_format=csv',
            $apiHeaders
        );

        // Filter headers except records header and add all record keys
        $fields = $this->getExportFields($fundRequest);
        $this->assertFields($response, $fundRequest, $fields);

        // Assert with passed all fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => FundRequestsExport::getExportFieldsRaw(),
        ]);

        $response = $this->get($url, $apiHeaders);
        $this->assertFields($response, $fundRequest, $fields);

        // Assert specific fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => ['bsn', 'fund_name'],
        ]);

        $response = $this->get($url, $apiHeaders);

        $this->assertFields($response, $fundRequest, [
            FundRequestsExport::trans('bsn'),
            FundRequestsExport::trans('fund_name'),
        ]);
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
     * @param TestResponse $response
     * @param FundRequest $fundRequest
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        TestResponse $response,
        FundRequest $fundRequest,
        array $fields,
    ): void {
        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);

        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);

        // Assert values
        $this->assertEquals($fundRequest->fund->name, $rows[1][1]);
    }
}
