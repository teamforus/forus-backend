<?php

namespace Tests\Feature\Exports;

use App\Exports\VoucherTransactionsProviderExport;
use App\Models\Organization;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\BaseExport;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class VoucherTransactionsProviderExportTest extends TestCase
{
    use BaseExport;
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * @var string
     */
    protected string $apiExportUrl = '/api/v1/platform/organizations/%s/provider/transactions/export';

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherTransactionsProviderExport(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);

        $transaction = $this->prepareData($organization);

        $providerOrganization = $transaction->product->organization;
        $this->assertNotNull($providerOrganization);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($providerOrganization->identity));

        // Assert export without fields - must be all fields by default
        $response = $this->getJson(
            sprintf($this->apiExportUrl, $providerOrganization->id) . '?data_format=csv',
            $apiHeaders
        );

        $fields = array_pluck(VoucherTransactionsProviderExport::getExportFields(), 'name');
        $this->assertFields($response, $transaction, $fields);

        // Assert with passed all fields
        $url = sprintf($this->apiExportUrl, $providerOrganization->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => VoucherTransactionsProviderExport::getExportFieldsRaw(),
        ]);

        $response = $this->getJson($url, $apiHeaders);
        $this->assertFields($response, $transaction, $fields);

        // Assert specific fields
        $url = sprintf($this->apiExportUrl, $providerOrganization->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => ['id'],
        ]);

        $response = $this->getJson($url, $apiHeaders);

        $this->assertFields($response, $transaction, [
            VoucherTransactionsProviderExport::trans('id'),
        ]);
    }

    /**
     * @param Organization $organization
     * @return VoucherTransaction
     */
    protected function prepareData(Organization $organization): VoucherTransaction
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

                $voucher->makeTransaction($params)->setPaid(null, now());
            });

        $transaction = $fund->vouchers()->first()->transactions->first();
        $this->assertNotNull($transaction);

        return $transaction;
    }

    /**
     * @param TestResponse $response
     * @param VoucherTransaction $transaction
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        TestResponse $response,
        VoucherTransaction $transaction,
        array $fields,
    ): void {
        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);

        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);

        // Assert specific fields
        $this->assertEquals($transaction->id, $rows[1][0]);
    }
}
