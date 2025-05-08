<?php

namespace Tests\Feature\Exports;

use App\Exports\ProviderFinancesExport;
use App\Models\Organization;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\BaseExport;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class ProviderFinancesExportTest extends TestCase
{
    use BaseExport;
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * @var string
     */
    protected string $apiExportUrl = '/api/v1/platform/organizations/%s/sponsor/providers/finances-export';

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderFinancesExport(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $providerOrganization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $fund = $this->makeTestFund($organization);
        $this->makeTestFundProvider($providerOrganization, $fund);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        // Assert export without fields - must be all fields by default
        $response = $this->getJson(
            sprintf($this->apiExportUrl, $organization->id) . '?data_format=csv',
            $apiHeaders
        );

        $fields = array_pluck(ProviderFinancesExport::getExportFields(), 'name');
        $this->assertFields($response, $providerOrganization, $fields);

        // Assert with passed all fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => ProviderFinancesExport::getExportFieldsRaw(),
        ]);

        $response = $this->getJson($url, $apiHeaders);
        $this->assertFields($response, $providerOrganization, $fields);

        // Assert specific fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => ['provider'],
        ]);

        $response = $this->getJson($url, $apiHeaders);

        $this->assertFields($response, $providerOrganization, [
            ProviderFinancesExport::trans('provider'),
        ]);
    }

    /**
     * @param TestResponse $response
     * @param Organization $organization
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        TestResponse $response,
        Organization $organization,
        array $fields,
    ): void {
        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);

        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);

        // Assert specific field
        $this->assertEquals($organization->name, $rows[1][0]);
    }
}
