<?php

namespace Tests\Feature\Exports;

use App\Exports\IdentityProfilesExport;
use App\Models\Identity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\BaseExport;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class IdentityProfilesExportTest extends TestCase
{
    use BaseExport;
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * @var string
     */
    protected string $apiExportUrl = '/api/v1/platform/organizations/%s/sponsor/identities/export';

    /**
     * @throws Throwable
     * @return void
     */
    public function testIdentityProfilesExport(): void
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

        $fields = array_pluck(IdentityProfilesExport::getExportFields($organization), 'name');
        $this->assertFields($response, $identity, $fields);

        // Assert with passed all fields
        $url = sprintf($this->apiExportUrl, $organization->id, $fund->id) . '?' . http_build_query([
                'data_format' => 'csv',
                'fields' => array_pluck(IdentityProfilesExport::getExportFields($organization), 'key'),
            ]);

        $response = $this->get($url, $apiHeaders);
        $this->assertFields($response, $identity, $fields);

        // Assert specific fields
        $url = sprintf($this->apiExportUrl, $organization->id, $fund->id) . '?' . http_build_query([
                'data_format' => 'csv',
                'fields' => ['id', 'given_name', 'family_name', 'email'],
            ]);

        $response = $this->get($url, $apiHeaders);

        $this->assertFields($response, $identity, [
            IdentityProfilesExport::trans('id'),
            IdentityProfilesExport::trans('given_name'),
            IdentityProfilesExport::trans('family_name'),
            IdentityProfilesExport::trans('email'),
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
        $this->assertEquals($identity->email, $rows[1][3]);
    }
}
