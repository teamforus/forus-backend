<?php

namespace Browser\Exports;

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
use Throwable;
use ZipArchive;

class VouchersExportTest extends DuskTestCase
{
    use ExportTrait;
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;

    /** @var array|string[]  */
    protected const array QR_CODE_FORMATS = ['null', 'pdf', 'png'];

    /**
     * @throws Throwable
     * @return void
     */
    public function testVouchersExport(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fund = $this->makeTestFund($organization, fundConfigsData: ['allow_voucher_records' => false]);
        $voucher = $fund->makeVoucher($this->makeIdentity($this->makeUniqueEmail()));

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

                        $data = $this->fillExportModalAndDownloadFile($browser, $format, $fieldsRaw, qrCodeFormat: $qrFormat);
                        $data && $this->assertFields($voucher, $data, $fields);

                        // assert specific fields exported
                        $this->openFilterDropdown($browser);
                        $data = $this->fillExportModalAndDownloadFile($browser, $format, ['number'], qrCodeFormat: $qrFormat);

                        $data && $this->assertFields($voucher, $data, [
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
     * @param string $format
     * @param string|null $qrCodeFormat
     * @return array|null
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
                    $this->assertGreaterThanOrEqual(1, count($files));
                }
            }
        } else {
            $this->fail("File $zipFilePath with format zip was not extracted.");
        }

        $data = Excel::toArray(new BrowserTestEntitiesImport(), $filePath, null, $fileFormat)[0];
        $this->assertNotEmpty($data, 'File is empty.');

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
     * @param string $format
     * @return string|null
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
     * @param Voucher $voucher
     * @param array $rows
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        Voucher $voucher,
        array $rows,
        array $fields
    ): void {
        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);

        $this->assertEquals($voucher->number, $rows[1][0]);
    }
}
