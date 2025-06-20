<?php

namespace Tests\Feature\Exports;

use App\Exports\ReimbursementsSponsorExport;
use App\Models\Reimbursement;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\BaseExport;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestReimbursements;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class ReimbursementsExportTest extends TestCase
{
    use BaseExport;
    use MakesTestFunds;
    use MakesTestVouchers;
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesTestReimbursements;

    /**
     * @var string
     */
    protected string $apiExportUrl = '/api/v1/platform/organizations/%s/reimbursements/export';

    /**
     * @throws Throwable
     * @return void
     */
    public function testReimbursementsExport(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);

        $fund = $this->makeTestFund($organization, [], [
            'allow_reimbursements' => true,
        ]);

        $voucher = $this->makeTestVoucher($fund, $this->makeIdentity($this->makeUniqueEmail()));
        $reimbursement = $this->makeReimbursement($voucher, true);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        // Assert export without fields - must be all fields by default
        $response = $this->getJson(
            sprintf($this->apiExportUrl, $organization->id) . '?data_format=csv',
            $apiHeaders
        );

        $fields = array_pluck(ReimbursementsSponsorExport::getExportFields(), 'name');
        $this->assertReimbursementsFields($response, $reimbursement, $fields);

        // Assert with passed all fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => ReimbursementsSponsorExport::getExportFieldsRaw(),
        ]);

        $response = $this->getJson($url, $apiHeaders);
        $this->assertReimbursementsFields($response, $reimbursement, $fields);

        // Assert specific fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => ['id', 'code'],
        ]);

        $response = $this->getJson($url, $apiHeaders);

        $this->assertReimbursementsFields($response, $reimbursement, [
            ReimbursementsSponsorExport::trans('id'),
            ReimbursementsSponsorExport::trans('code'),
        ]);
    }

    /**
     * @param TestResponse $response
     * @param Reimbursement $reimbursement
     * @param array $fields
     * @return void
     */
    protected function assertReimbursementsFields(
        TestResponse $response,
        Reimbursement $reimbursement,
        array $fields,
    ): void {
        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);

        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);

        // Assert specific field
        $this->assertEquals('#' . $reimbursement->code, $rows[1][1]);
    }
}
