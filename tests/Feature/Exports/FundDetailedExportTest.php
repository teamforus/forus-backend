<?php

namespace Tests\Feature\Exports;

use App\Exports\FundsExportDetailed;
use App\Models\Fund;
use App\Models\FundFormula;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
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

        $response = $this->getJson($url, $apiHeaders);
        $fields = $this->getExportFields($fund);
        $this->assertExportedData($response, $fund, $fields);

        // Assert with passed all fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            'year' => now()->year,
            'detailed' => true,
            'data_format' => 'csv',
            'fields' => FundsExportDetailed::getExportFieldsRaw($fund->organization->hasPayoutFunds()),
        ]);

        $response = $this->getJson($url, $apiHeaders);
        $this->assertExportedData($response, $fund, $fields);

        // Assert specific fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            'year' => now()->year,
            'detailed' => true,
            'data_format' => 'csv',
            'fields' => ['name'],
        ]);

        $response = $this->getJson($url, $apiHeaders);

        $this->assertExportedData($response, $fund, [
            FundsExportDetailed::trans('name'),
        ]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testExportsUnconditionalBudgetAmountPerVoucher(): void
    {
        $this->travelTo('2026-11-13 12:00:00');
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $fund = $this->makeTestFund($organization);
        $fund->fund_formulas()->delete();

        foreach ([20, 30] as $amount) {
            $fund->fund_formulas()->create(['type' => FundFormula::TYPE_FIXED, 'amount' => $amount]);
        }

        $response = $this->apiExportOrganizationFinancesRequest($organization, [
            'year' => 2026,
            'detailed' => true,
            'data_format' => 'csv',
            'fields' => ['name', 'budget_amount_per_voucher'],
        ], $organization->identity);

        $rows = $this->assertCsvExportResponse($response);

        $this->assertCount(2, $rows);
        $this->assertExportCell($rows, $fund->name, 0);
        $this->assertExportCell($rows, '50.00 €', 1);
    }

    /**
     * @param Fund $fund
     * @return array
     */
    protected function getExportFields(Fund $fund): array
    {
        $hasPayoutFunds = $fund->organization->hasPayoutFunds();

        return array_values(array_filter(
            Arr::pluck(FundsExportDetailed::getExportFields($hasPayoutFunds), 'name'),
            fn (string $field) => $field !== FundsExportDetailed::trans('budget_children_count')
        ));
    }

    /**
     * @param TestResponse $response
     * @param Fund $fund
     * @param array $fields
     * @return void
     */
    protected function assertExportedData(
        TestResponse $response,
        Fund $fund,
        array $fields,
    ): void {
        $rows = $this->assertCsvExportResponse($response);

        $this->assertExportHeaders($rows, $fields);
        $this->assertExportCell($rows, $fund->name, 0);
    }
}
