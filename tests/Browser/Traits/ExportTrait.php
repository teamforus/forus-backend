<?php

namespace Tests\Browser\Traits;

use App\Imports\BrowserTestEntitiesImport;
use App\Models\Fund;
use Exception;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Illuminate\Support\Collection;
use Laravel\Dusk\Browser;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

trait ExportTrait
{
    public const array FORMATS = ['csv', 'xls'];

    /**
     * @param Browser $browser
     * @param string $format
     * @param array $fields
     * @param string $selector
     * @throws TimeoutException
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @return array
     */
    protected function fillExportModalAndDownloadFile(
        Browser $browser,
        string $format,
        array $fields = [],
        string $selector = '@export'
    ): array {
        $this->fillExportModal($browser, $fields, $selector, $format);

        return $this->parseCsvFile($format);
    }

    /**
     * @param string $format
     * @return array
     */
    protected function parseCsvFile(string $format): array
    {
        $excelFormat = match ($format) {
            'csv' => ExcelFormat::CSV,
            'xls' => ExcelFormat::XLS,
        };

        // Locate the latest CSV file
        $csvFile = $this->findFile($format);

        if (!$csvFile) {
            $this->fail("CSV file with format $format was not downloaded.");
        }

        $csvData = Excel::toArray(new BrowserTestEntitiesImport(), $csvFile, null, $excelFormat)[0];

        try {
            unlink($csvFile);
        } catch (Exception $e) {
        }

        $this->assertNotEmpty($csvData, 'CSV file is empty.');

        return $csvData;
    }

    /**
     * @param string $format
     * @param int $tries
     * @return string|null
     */
    protected function findFile(string $format, int $tries = 0): ?string
    {
        $downloadPath = storage_path('dusk-downloads');
        $files = glob("$downloadPath/*.$format");
        $csvFile = $files ? array_reduce($files, fn ($a, $b) => filectime($a) > filectime($b) ? $a : $b) : null;

        if (!$csvFile && $tries <= 1) {
            $tries++;
            // Wait for file download and try again
            sleep(1);

            return $this->findFile($format, $tries);
        }

        return $csvFile;
    }

    /**
     * @param Browser $browser
     * @param array $fields
     * @param string $selector
     * @param string $format
     * @throws TimeoutException
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @return void
     */
    protected function fillExportModal(
        Browser $browser,
        array $fields = [],
        string $selector = '@export',
        string $format = 'csv',
    ): void {
        $browser->waitFor($selector);
        $browser->element($selector)->click();
        $browser->waitFor('@modalExport');

        $browser->within('@modalExport', function (Browser $browser) use ($fields, $format) {
            $browser->waitFor("@toggle_data_format_$format");
            $browser->element("@toggle_data_format_$format")->click();

            if (count($fields)) {
                $browser->waitFor('@toggle_fields');
                $browser->click('@toggle_fields');

                foreach ($fields as $field) {
                    $browser->click("@option_$field");
                }
            }

            $browser->click('@submitBtn');
        });

        $this->assertAndCloseSuccessNotification($browser);
    }

    /**
     * @param Fund $fund
     * @param int $count
     * @param int $childrenCount
     * @return Collection
     */
    protected function makeProductVouchers(Fund $fund, int $count, int $childrenCount): Collection
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

    /**
     * @param Browser $browser
     * @param string $waitSelector
     * @throws TimeoutException
     * @return void
     */
    protected function openFilterDropdown(Browser $browser, string $waitSelector = '@export'): void
    {
        $browser->waitFor('@showFilters');
        $browser->element('@showFilters')->click();

        try {
            $browser->waitFor($waitSelector, 1);
        } catch (TimeoutException) {
            $browser->element('@showFilters')->click();
        }
    }
}
