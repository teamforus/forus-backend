<?php

namespace Tests\Feature\Exports;

use App\Exports\FundsDetailedExport;
use App\Models\Fund;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\BaseExport;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class FundDetailedExportTest extends TestCase
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
    public function testFundDetailedExport(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($organization);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        // Assert export without fields - must be all fields by default
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
                'year' => now()->year,
                'detailed' => true,
                'data_format' => 'csv',
            ]);

        $response = $this->get($url, $apiHeaders);
        $fields = $this->getExportFields();
        $this->assertFields($response, $fund, $fields);

        // Assert with passed all fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
                'year' => now()->year,
                'detailed' => true,
                'data_format' => 'csv',
                'fields' => FundsDetailedExport::getExportFieldsRaw(),
            ]);

        $response = $this->get($url, $apiHeaders);
        $this->assertFields($response, $fund, $fields);

        // Assert specific fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
                'year' => now()->year,
                'detailed' => true,
                'data_format' => 'csv',
                'fields' => ['name'],
            ]);

        $response = $this->get($url, $apiHeaders);

        $this->assertFields($response, $fund, [
            FundsDetailedExport::trans('name'),
        ]);
    }

    /**
     * @return array
     */
    protected function getExportFields(): array
    {
        return array_values(array_filter(
            array_pluck(FundsDetailedExport::getExportFields(), 'name'),
            fn (string $field) => $field !== FundsDetailedExport::trans('budget_children_count')
        ));
    }

    /**
     * @param TestResponse $response
     * @param Fund $fund
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        TestResponse $response,
        Fund $fund,
        array $fields,
    ): void {
        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);

        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);

        // Assert values
        $this->assertEquals($fund->name, $rows[1][0]);
    }
}
