<?php

namespace Tests\Feature\Exports;

use App\Exports\VoucherExport;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\BaseExport;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class VouchersExportTest extends TestCase
{
    use BaseExport;
    use MakesTestFunds;
    use MakesTestVouchers;
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
        $voucher = $this->makeTestVoucher($fund, $this->makeIdentity($this->makeUniqueEmail()));

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        // Assert export without fields - must be all fields by default
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'source' => 'all',
            'type' => 'all',
        ]);

        $response = $this->getJson($url, $apiHeaders);

        $fields = Arr::pluck(
            array_filter(VoucherExport::getExportFields(), fn ($field) => !($field['is_record_field'] ?? false)),
            'name'
        );

        $this->assertExportedData($response, $voucher, $fields);

        // Assert with passed all fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'source' => 'all',
            'type' => 'all',
            'fields' => VoucherExport::getExportFieldsRaw(),
        ]);

        $response = $this->getJson($url, $apiHeaders);
        $this->assertExportedData($response, $voucher, $fields);

        // Assert specific fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'source' => 'all',
            'type' => 'all',
            'fields' => ['number'],
        ]);

        $response = $this->getJson($url, $apiHeaders);

        $this->assertExportedData($response, $voucher, [
            VoucherExport::trans('number'),
        ]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherExportKeepsCanonicalFieldOrderWhenSelectedFieldsAreReordered(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity, ['bsn_enabled' => true]);
        $fund = $this->makeTestFund($organization, fundConfigsData: ['allow_voucher_records' => false]);
        $voucher = $this->makeTestVoucher($fund, $this->makeIdentity($this->makeUniqueEmail()));

        $response = $this->getJson(
            sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
                'data_format' => 'csv',
                'source' => 'all',
                'type' => 'all',
                'fields' => ['fund_name', 'number', 'amount'],
            ]),
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        );

        $response->assertStatus(200);

        $rows = array_map('str_getcsv', explode("\n", trim(base64_decode($response->json('files')['csv']))));

        $this->assertEquals([
            VoucherExport::trans('number'),
            VoucherExport::trans('amount'),
            VoucherExport::trans('fund_name'),
        ], $rows[0]);
        $this->assertEquals($voucher->number, $rows[1][0]);
    }

    /**
     * @param TestResponse $response
     * @param Voucher $voucher
     * @param array $fields
     * @return void
     */
    protected function assertExportedData(
        TestResponse $response,
        Voucher $voucher,
        array $fields,
    ): void {
        $response->assertStatus(200);
        $rows = array_map('str_getcsv', explode("\n", trim(base64_decode($response->json('files')['csv']))));

        $this->assertExportHeaders($rows, $fields);
        $this->assertExportCell($rows, $voucher->number, 0);
    }
}
