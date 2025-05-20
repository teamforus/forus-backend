<?php

namespace Tests\Feature\Exports;

use App\Exports\VoucherExport;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\BaseExport;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class VouchersExportTest extends TestCase
{
    use BaseExport;
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * @var string
     */
    protected string $apiExportUrl = '/api/v1/platform/organizations/%s/sponsor/vouchers/export';

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherTransactionsSponsorExport(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity, ['bsn_enabled' => true]);
        $fund = $this->makeTestFund($organization, fundConfigsData: ['allow_voucher_records' => false]);
        $voucher = $fund->makeVoucher($this->makeIdentity($this->makeUniqueEmail()));

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        // Assert export without fields - must be all fields by default
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'source' => 'all',
            'type' => 'all',
        ]);

        $response = $this->getJson($url, $apiHeaders);

        $fields = array_pluck(
            array_filter(VoucherExport::getExportFields(), fn ($field) => !($field['is_record_field'] ?? false)),
            'name'
        );

        $this->assertFields($response, $voucher, $fields);

        // Assert with passed all fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'source' => 'all',
            'type' => 'all',
            'fields' => VoucherExport::getExportFieldsRaw(),
        ]);

        $response = $this->getJson($url, $apiHeaders);
        $this->assertFields($response, $voucher, $fields);

        // Assert specific fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'source' => 'all',
            'type' => 'all',
            'fields' => ['number'],
        ]);

        $response = $this->getJson($url, $apiHeaders);

        $this->assertFields($response, $voucher, [
            VoucherExport::trans('number'),
        ]);
    }

    /**
     * @param TestResponse $response
     * @param Voucher $voucher
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        TestResponse $response,
        Voucher $voucher,
        array $fields,
    ): void {
        $response->assertStatus(200);
        $rows = array_map('str_getcsv', explode("\n", trim(base64_decode($response->json('files')['csv']))));

        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);

        // Assert specific fields
        $this->assertEquals($voucher->number, $rows[1][0]);
    }
}
