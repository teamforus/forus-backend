<?php

namespace Tests\Feature\Exports;

use App\Exports\FundProvidersExport;
use App\Models\FundProvider;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\BaseExport;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class FundProvidersExportTest extends TestCase
{
    use BaseExport;
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * @var string
     */
    protected string $apiExportUrl = '/api/v1/platform/organizations/%s/sponsor/providers/export';

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundProvidersExport(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $providerOrganization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $fund = $this->makeTestFund($organization);
        $fundProvider = $this->makeTestFundProvider($providerOrganization, $fund);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        // Assert export without fields - must be all fields by default
        $response = $this->get(
            sprintf($this->apiExportUrl, $organization->id, $fund->id) . '?data_format=csv',
            $apiHeaders
        );

        $fields = array_pluck(FundProvidersExport::getExportFields(), 'name');
        $this->assertFields($response, $fundProvider, $fields);

        // Assert with passed all fields
        $url = sprintf($this->apiExportUrl, $organization->id, $fund->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => FundProvidersExport::getExportFieldsRaw(),
        ]);

        $response = $this->get($url, $apiHeaders);
        $this->assertFields($response, $fundProvider, $fields);

        // Assert specific fields
        $url = sprintf($this->apiExportUrl, $organization->id, $fund->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => ['fund', 'implementation', 'fund_type', 'provider'],
        ]);

        $response = $this->get($url, $apiHeaders);

        $this->assertFields($response, $fundProvider, [
            FundProvidersExport::trans('fund'),
            FundProvidersExport::trans('implementation'),
            FundProvidersExport::trans('fund_type'),
            FundProvidersExport::trans('provider'),
        ]);
    }

    /**
     * @param TestResponse $response
     * @param FundProvider $fundProvider
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        TestResponse $response,
        FundProvider $fundProvider,
        array $fields,
    ): void {
        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);

        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);

        // Assert values
        $this->assertEquals($fundProvider->fund->name, $rows[1][0]);
        $this->assertEquals($fundProvider->organization->name, $rows[1][3]);
    }
}
