<?php

namespace Tests\Browser\Traits;

use App\Models\Fund;
use Facebook\WebDriver\Exception\TimeoutException;
use Illuminate\Support\Collection;
use Laravel\Dusk\Browser;

trait ExportTrait
{
    /**
     * @return array
     */
    public function parseCsvFile(): array
    {
        // Wait for file download
        sleep(2);

        // Locate the latest CSV file
        $downloadPath = storage_path('dusk-downloads');
        $files = glob("$downloadPath/*.csv");
        $csvFile = $files ? array_reduce($files, fn($a, $b) => filectime($a) > filectime($b) ? $a : $b) : null;

        if (!$csvFile) {
            $this->fail('CSV file was not downloaded.');
        }

        // Read CSV file
        $csvData = [];
        if (($handle = fopen($csvFile, "r")) !== false) {
            while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                $csvData[] = $row;
            }
            fclose($handle);
        }

        unlink($csvFile);

        $this->assertNotEmpty($csvData, 'CSV file is empty.');

        return $csvData;
    }

    /**
     * @param Browser $browser
     * @param array $fields
     * @param string $selector
     * @return void
     * @throws TimeoutException
     */
    public function fillExportModal(
        Browser $browser,
        array $fields = [],
        string $selector = '@export'
    ): void {
        $browser->waitFor($selector);
        $browser->element($selector)->click();
        $browser->waitFor('@modalExport');

        $browser->within('@modalExport', function (Browser $browser) use ($fields) {
            if (count($fields)) {
                $browser->waitFor('@toggle_fields');
                $browser->click('@toggle_fields');

                foreach ($fields as $field) {
                    $browser->click("@option_$field");
                }
            }

            $browser->click('@submitBtn');
        });

        $browser->waitFor('@successNotification');
        $browser->waitUntilMissing('@successNotification');
    }

    /**
     * @param Fund $fund
     * @param int $count
     * @param int $childrenCount
     * @return Collection
     */
    public function makeProductVouchers(Fund $fund, int $count, int $childrenCount): Collection
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
