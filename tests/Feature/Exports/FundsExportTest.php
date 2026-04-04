<?php

namespace Tests\Feature\Exports;

use App\Exports\FundsExport;
use App\Models\Fund;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\BaseExport;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class FundsExportTest extends TestCase
{
    use BaseExport;
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * @var string
     */
    protected string $apiExportUrl = '/api/v1/platform/organizations/%s/sponsor/finances-overview-export';

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundExport(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($organization);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        // Assert export without fields - must be all fields by default
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            'year' => now()->year,
            'data_format' => 'csv',
        ]);

        $response = $this->getJson($url, $apiHeaders);
        $fields = Arr::pluck(FundsExport::getExportFields(), 'name');
        $this->assertExportedData($response, $fund, $fields);

        // Assert with passed all fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            'year' => now()->year,
            'data_format' => 'csv',
            'fields' => FundsExport::getExportFieldsRaw(),
        ]);

        $response = $this->getJson($url, $apiHeaders);
        $this->assertExportedData($response, $fund, $fields);

        // Assert specific fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            'year' => now()->year,
            'data_format' => 'csv',
            'fields' => ['name'],
        ]);

        $response = $this->getJson($url, $apiHeaders);

        $this->assertExportedData($response, $fund, [
            FundsExport::trans('name'),
        ]);
    }

    /**
     * @param TestResponse $response
     * @param Fund $fund
     * @param array $fields
     * @return void
     */
    protected function assertExportedData(
        TestResponse $response,
        Fund $fund,
        array $fields,
    ): void {
        $rows = $this->assertCsvExportResponse($response);

        $this->assertExportHeaders($rows, $fields);
        $this->assertExportCell($rows, $fund->name, 0);
    }
}
