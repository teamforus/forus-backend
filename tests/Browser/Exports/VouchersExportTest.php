<?php

namespace Tests\Browser\Exports;

use App\Exports\VoucherExport;
use App\Imports\BrowserTestEntitiesImport;
use App\Models\Implementation;
use App\Models\Voucher;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Laravel\Dusk\Browser;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Browser\Traits\ExportTrait;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestVouchers;
use Throwable;
use ZipArchive;

class VouchersExportTest extends DuskTestCase
{
    use ExportTrait;
    use MakesTestFunds;
    use MakesTestVouchers;
    use HasFrontendActions;
    use RollbackModelsTrait;

    protected const array QR_CODE_FORMATS = ['null', 'pdf', 'png'];

    /**
     * Tests the export functionality for vouchers.
     *
     * This method sets up a test environment, creates necessary entities,
     * and performs actions to trigger the voucher export. It then asserts that
     * the exported data contains the expected fields.
     *
     * @throws Throwable
     * @return void
     */
    public function testVouchersExport(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fund = $this->makeTestFund($organization, fundConfigsData: ['allow_voucher_records' => false]);
        $voucher = $this->makeTestVoucher($fund, $this->makeIdentity($this->makeUniqueEmail()));

        $this->rollbackModels([], function () use ($implementation, $organization, $voucher) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $voucher) {
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                // Go to list, open export modal and assert all export fields in file
                $this->goToVouchersPage($browser);
                $this->searchTable($browser, '@tableVoucher', $voucher->identity->email, $voucher->id);

                $fieldsRaw = VoucherExport::getExportFieldsRaw();
                $fields = array_pluck(
                    array_filter(VoucherExport::getExportFields(), fn ($field) => !($field['is_record_field'] ?? false)),
                    'name'
                );

                $formats = static::FORMATS;
                $formats[] = 'all';

                foreach ($formats as $format) {
                    foreach (static::QR_CODE_FORMATS as $qrFormat) {
                        // assert all fields exported
                        $this->openFilterDropdown($browser);

                        $data = $this->fillExportModalAndDownloadFile(
                            $browser,
                            format: $format,
                            fields: $fieldsRaw,
                            fillCallback: fn (Browser $browser) => $browser->click("@toggle_qr_format_$qrFormat"),
                        );

                        $data && $this->assertExportedDataHasRequestedFields($voucher, $data, $fields);

                        // assert specific fields exported
                        $this->openFilterDropdown($browser);

                        $data = $this->fillExportModalAndDownloadFile(
                            $browser,
                            format: $format,
                            fields: ['number'],
                            fillCallback: fn (Browser $browser) => $browser->click("@toggle_qr_format_$qrFormat"),
                        );

                        $data && $this->assertExportedDataHasRequestedFields($voucher, $data, [
                            VoucherExport::trans('number'),
                        ]);
                    }
                }

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * Parses the exported file and returns its contents.
     *
     * @param string $format The format of the exported file ('csv', 'xls', or 'all').
     * @param string|null $qrCodeFormat The format of the QR code file ('pdf' or 'png'), if applicable.
     * @return array|null An array containing the parsed data from the exported file, or null if the file is not found or an error occurs.
     */
    protected function parseExportedFile(string $format, ?string $qrCodeFormat = null): ?array
    {
        if (Config::get('tests.dusk_github_action')) {
            return null;
        }

        $fileFormat = match ($format) {
            'csv' => ExcelFormat::CSV,
            'xls' => ExcelFormat::XLS,
            'all' => 'all',
            default => throw new InvalidArgumentException("Unsupported format: $format"),
        };

        $zipFilePath = $this->findExportedFile('zip', 5000);
        $pdfPath = null;
        $imageDirectory = null;

        if (!$zipFilePath || !Storage::exists($zipFilePath)) {
            $this->fail("File $zipFilePath with format $format was not downloaded.");
        }

        $zip = new ZipArchive();
        $filePath = null;

        if ($zip->open(Storage::path($zipFilePath)) === true) {
            $zip->extractTo(Storage::path('dusk-downloads'));
            $zip->close();

            if ($fileFormat === 'all') {
                // if export all - just check if all exported files exist
                foreach (static::FORMATS as $format) {
                    $filePath = $this->findExportedFileAndAssertExists($format);
                    $fileFormat = match ($format) {
                        'csv' => ExcelFormat::CSV,
                        'xls' => ExcelFormat::XLS,
                    };
                }
            } else {
                $filePath = $this->findExportedFileAndAssertExists($format);
            }

            $data = Excel::toArray(new BrowserTestEntitiesImport(), $filePath, null, $fileFormat)[0];
            $this->assertNotEmpty($data, 'File is empty.');

            // assert QR code files
            if ($qrCodeFormat && $qrCodeFormat !== 'null') {
                if ($qrCodeFormat === 'pdf') {
                    $pdfPath = $this->findExportedFileAndAssertExists('pdf');
                }

                if ($qrCodeFormat === 'png') {
                    $directories = Storage::directories('dusk-downloads');

                    $imageDirectory = collect($directories)
                        ->filter(fn ($file) => str_ends_with($file, 'QR_codes_images'))
                        ->sortByDesc(fn ($file) => Storage::lastModified($file))
                        ->first();

                    $this->assertNotNull($imageDirectory);

                    $files = Storage::files($imageDirectory);
                    $this->assertSame(count($data) - 1, count($files));
                }
            }
        } else {
            $this->fail("File $zipFilePath with format zip was not extracted.");
        }

        try {
            Storage::delete($filePath);
            Storage::delete($zipFilePath);
            $pdfPath && Storage::delete($pdfPath);
            $imageDirectory && Storage::deleteDirectory($imageDirectory);
        } catch (Throwable) {
            $this->fail("Failed to delete file: [$filePath]");
        }

        return $data;
    }

    /**
     * Finds the exported file for a given format and asserts its existence.
     *
     * @param string $format The format of the exported file to find.
     * @return ?string The path to the exported file if it exists, null otherwise.
     */
    protected function findExportedFileAndAssertExists(string $format): ?string
    {
        $path = $this->findExportedFile($format, 5000);

        if (!$path || !Storage::exists($path)) {
            $this->fail("File $path with format $format was not downloaded.");
        }

        return $path;
    }

    /**
     * Asserts that the exported data contains the requested fields for a given voucher.
     *
     * @param Voucher $voucher The voucher model to be checked.
     * @param array $rows An array of rows containing the exported data.
     * @param array $fields An array of expected field names in the first row (header).
     */
    protected function assertExportedDataHasRequestedFields(Voucher $voucher, array $rows, array $fields): void
    {
        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);
        $this->assertEquals($voucher->number, $rows[1][0]);
    }
}
