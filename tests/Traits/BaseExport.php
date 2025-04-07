<?php

namespace Tests\Traits;

use App\Models\Fund;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;

trait BaseExport
{
    use MakesTestFundProviders;

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
     * @param Fund $fund
     * @param int $count
     * @param int $childrenCount
     * @return Collection
     */
    private function makeProductVouchers(Fund $fund, int $count, int $childrenCount): Collection
    {
        $vouchers = collect();
        $products = $this->makeProductsFundFund($count, 5);

        for ($i = 1; $i <= $count; $i++) {
            $product = $products[$i - 1];
            $this->addProductFundToFund($fund, $product, false);

            $voucher = $fund->makeProductVoucher($this->makeIdentity(), [], $product->id);
            $voucher->appendRecord('children_nth', $childrenCount);
            $vouchers->push($voucher);
        }

        return $vouchers;
    }
}
