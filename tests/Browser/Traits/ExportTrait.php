<?php

namespace Tests\Browser\Traits;

use App\Imports\BrowserTestEntitiesImport;
use App\Models\Fund;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Laravel\Dusk\Browser;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Traits\MakesTestVouchers;
use Throwable;

trait ExportTrait
{
    use MakesTestVouchers;

    public const array FORMATS = ['csv', 'xls'];

    /**
     * @param Browser $browser
     * @param string $format
     * @param array $fields
     * @param string $selector
     * @param callable|null $fillCallback
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return array|null
     */
    protected function fillExportModalAndDownloadFile(
        Browser $browser,
        string $format,
        array $fields = [],
        string $selector = '@export',
        ?callable $fillCallback = null,
    ): ?array {
        $this->fillExportModal($browser, $fields, $selector, $format, $fillCallback);

        $browser->assertMissing('@dangerNotification');

        return $this->parseExportedFile($format);
    }

    /**
     * @param string $format
     * @return array|null
     */
    protected function parseExportedFile(string $format): ?array
    {
        $fileFormat = match ($format) {
            'csv' => ExcelFormat::CSV,
            'xls' => ExcelFormat::XLS,
            default => throw new InvalidArgumentException("Unsupported format: $format"),
        };

        $filePath = $this->findExportedFile($format, 5000);
        $isGithubAction = Config::get('tests.dusk_github_action');

        $data = null;

        if ($format !== 'xls' || !$isGithubAction) {
            if (!$filePath || !Storage::exists($filePath)) {
                $this->fail("File $filePath with format $format was not downloaded.");
            }

            $data = Excel::toArray(new BrowserTestEntitiesImport(), $filePath, null, $fileFormat)[0];
            $this->assertNotEmpty($data, 'File is empty.');

            try {
                Storage::delete($filePath);
            } catch (Throwable) {
                if (!$isGithubAction) {
                    $this->fail("Failed to delete file: [$filePath]");
                }
            }
        }

        return $data;
    }

    /**
     * @param string $format
     * @param int $timeout
     * @return string|null
     */
    protected function findExportedFile(string $format, int $timeout = 2000): ?string
    {
        $timeout = (int) (round($timeout / 100) * 100);
        $deadline = microtime(true) + ($timeout / 1000);

        do {
            $files = Storage::files('dusk-downloads');

            $latestFile = collect($files)
                ->filter(fn ($file) => str_ends_with($file, ".$format"))
                ->sortByDesc(fn ($file) => Storage::lastModified($file))
                ->first();

            if ($latestFile !== null) {
                return $latestFile;
            }

            usleep(100_000);
        } while (microtime(true) < $deadline);

        return null;
    }

    /**
     * @param Browser $browser
     * @param array $fields
     * @param string $selector
     * @param string $format
     * @param callable|null $fillCallback
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function fillExportModal(
        Browser $browser,
        array $fields = [],
        string $selector = '@export',
        string $format = 'csv',
        callable $fillCallback = null,
    ): void {
        $browser->waitFor($selector);
        $browser->element($selector)->click();
        $browser->waitFor('@modalExport');

        $browser->within('@modalExport', function (Browser $browser) use ($fields, $format, $fillCallback) {
            $browser->waitFor("@toggle_data_format_$format");
            $browser->element("@toggle_data_format_$format")->click();

            if (count($fields)) {
                $browser->waitFor('@toggle_fields');
                $browser->click('@toggle_fields');

                foreach ($fields as $field) {
                    $browser->click("@option_$field");
                }
            }

            if ($fillCallback) {
                $browser = $fillCallback($browser);
            }

            $browser->click('@submitBtn');
        });

        $browser->waitUntilMissing('@modalExport');
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

            $voucher = $this->makeTestProductVoucher($fund, $this->makeIdentity(), [], $product->id);
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
        // hide filters if was opened
        if ($browser->element('@hideFilters')) {
            $browser->element('@hideFilters')->click();
        }

        $browser->waitFor('@showFilters');
        $browser->element('@showFilters')->click();

        try {
            $browser->waitFor($waitSelector, 1);
        } catch (TimeoutException) {
            $browser->element('@showFilters')->click();
        }
    }
}
