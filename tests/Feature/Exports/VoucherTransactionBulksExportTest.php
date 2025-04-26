<?php

namespace Tests\Feature\Exports;

use App\Exports\VoucherTransactionBulksExport;
use App\Models\Organization;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Models\VoucherTransactionBulk;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\BaseExport;
use Tests\Traits\MakesTestBankConnections;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class VoucherTransactionBulksExportTest extends TestCase
{
    use BaseExport;
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesTestBankConnections;

    /**
     * @var string
     */
    protected string $apiExportUrl = '/api/v1/platform/organizations/%s/sponsor/transaction-bulks/export';

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherTransactionBulksExport(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $this->makeTestImplementation($organization);
        $bulk = $this->prepareData($organization);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        // Assert export without fields - must be all fields by default
        $response = $this->get(
            sprintf($this->apiExportUrl, $organization->id) . '?data_format=csv',
            $apiHeaders
        );

        $fields = array_pluck(VoucherTransactionBulksExport::getExportFields(), 'name');
        $this->assertFields($response, $bulk, $fields);

        // Assert with passed all fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => VoucherTransactionBulksExport::getExportFieldsRaw(),
        ]);

        $response = $this->get($url, $apiHeaders);
        $this->assertFields($response, $bulk, $fields);

        // Assert specific fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => ['id', 'quantity'],
        ]);

        $response = $this->get($url, $apiHeaders);

        $this->assertFields($response, $bulk, [
            VoucherTransactionBulksExport::trans('id'),
            VoucherTransactionBulksExport::trans('quantity'),
        ]);
    }

    /**
     * @param Organization $organization
     * @return VoucherTransactionBulk
     */
    protected function prepareData(Organization $organization): VoucherTransactionBulk
    {
        $fund = $this->makeTestFund($organization);

        $this
            ->makeProductVouchers($fund, 1, 1)
            ->each(function (Voucher $voucher) use ($fund) {
                $employee = $fund->organization->employees[0];
                $params = [
                    'amount' => $voucher->amount,
                    'product_id' => $voucher->product_id,
                    'employee_id' => $employee?->id,
                    'branch_id' => $employee?->office?->branch_id,
                    'branch_name' => $employee?->office?->branch_name,
                    'branch_number' => $employee?->office?->branch_number,
                    'target' => VoucherTransaction::TARGET_PROVIDER,
                    'organization_id' => $voucher->product->organization_id,
                ];

                $voucher->makeTransaction($params);
            });

        $this->makeBankConnection($organization);
        $list = VoucherTransactionBulk::buildBulksForOrganization($organization);

        $bulk = VoucherTransactionBulk::find($list[0] ?? null);
        $this->assertNotNull($bulk);

        return $bulk;
    }

    /**
     * @param TestResponse $response
     * @param VoucherTransactionBulk $bulk
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        TestResponse $response,
        VoucherTransactionBulk $bulk,
        array $fields,
    ): void {
        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);

        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);

        // Assert specific fields
        $this->assertEquals($bulk->id, $rows[1][0]);
        $this->assertEquals($bulk->voucher_transactions()->count(), $rows[1][1]);
    }
}
