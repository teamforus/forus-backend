<?php

namespace Tests\Feature\Exports;

use App\Exports\FundIdentitiesExport;
use App\Models\Identity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\BaseExport;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class IdentitiesExportTest extends TestCase
{
    use BaseExport;
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * @var string
     */
    protected string $apiExportUrl = '/api/v1/platform/organizations/%s/funds/%s/identities/export';

    /**
     * @throws Throwable
     * @return void
     */
    public function testIdentitiesExport(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($organization);
        $fund->makeVoucher($identity);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        // Assert export without fields - must be all fields by default
        $response = $this->get(
            sprintf($this->apiExportUrl, $organization->id, $fund->id) . '?data_format=csv',
            $apiHeaders
        );

        $fields = array_pluck(FundIdentitiesExport::getExportFields(), 'name');
        $this->assertFields($response, $identity, $fields);

        // Assert with passed all fields
        $url = sprintf($this->apiExportUrl, $organization->id, $fund->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => FundIdentitiesExport::getExportFieldsRaw(),
        ]);

        $response = $this->get($url, $apiHeaders);
        $this->assertFields($response, $identity, $fields);

        // Assert specific fields
        $url = sprintf($this->apiExportUrl, $organization->id, $fund->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => ['id', 'email'],
        ]);

        $response = $this->get($url, $apiHeaders);

        $this->assertFields($response, $identity, [
            FundIdentitiesExport::trans('id'),
            FundIdentitiesExport::trans('email'),
        ]);
    }

    /**
     * @param TestResponse $response
     * @param Identity $identity
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        TestResponse $response,
        Identity $identity,
        array $fields,
    ): void {
        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);

        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);

        // Assert specific fields
        $this->assertEquals($identity->email, $rows[1][1]);
    }
}
