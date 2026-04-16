<?php

namespace Tests\Traits;

use App\Models\Fund;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;

trait BaseExport
{
    use MakesTestVouchers;
    use MakesTestFundProviders;

    /**
     * @param TestResponse $response
     * @return array
     */
    protected function assertCsvExportResponse(TestResponse $response): array
    {
        $response->assertStatus(200);
        $response->assertDownload();

        return $this->getCsvData($response);
    }

    /**
     * @param TestResponse $response
     * @return array
     */
    public function getCsvData(TestResponse $response): array
    {
        // Extract the CSV content
        ob_start();
        $response->sendContent();
        $csvContent = ob_get_clean();

        // Convert CSV to an array
        return array_map('str_getcsv', explode("\n", trim($csvContent)));
    }

    /**
     * @param array $rows
     * @param array $fields
     * @return void
     */
    protected function assertExportHeaders(array $rows, array $fields): void
    {
        $this->assertEquals($fields, $rows[0]);
    }

    /**
     * @param array $rows
     * @param mixed $expected
     * @param int $column
     * @param int $row
     * @return void
     */
    protected function assertExportCell(array $rows, mixed $expected, int $column, int $row = 1): void
    {
        $this->assertEquals($expected, $rows[$row][$column]);
    }

    /**
     * @param Fund $fund
     * @param int $count
     * @param int $childrenCount
     * @return Collection
     */
    private function makeProductVouchers(Fund $fund, int $count, int $childrenCount): Collection
    {
        $vouchers = collect();
        $products = $this->makeTestProviderWithProducts($count, 5);

        for ($i = 1; $i <= $count; $i++) {
            $product = $products[$i - 1];
            $this->addProductToFund($fund, $product, false);

            $voucher = $this->makeTestProductVoucher($fund, $this->makeIdentity(), [], $product->id);
            $voucher->appendRecord('children_nth', $childrenCount);
            $vouchers->push($voucher);
        }

        return $vouchers;
    }
}
