<?php

namespace Tests\Feature;

use App\Services\FileService\Models\File;
use App\Services\FileService\PdfPreviewUploadService;
use App\Services\MediaService\MediaService;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\TmpFile;
use App\Services\PdfToImgService\Exceptions\PdfToImgException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\MakesProductReservationPdfFiles;
use Tests\Traits\UsesLocalPoppler;
use Throwable;

class ProductReservationPdfUploadTest extends TestCase
{
    use DatabaseTransactions;
    use MakesProductReservationPdfFiles;
    use UsesLocalPoppler;

    /**
     * @return void
     */
    public function testProductReservationPdfUploadGeneratesPreviewPages(): void
    {
        $this->skipIfLocalPopplerUnavailable();
        $this->fakeProductReservationPdfStorage();
        $this->useLocalPdfToImgConverter();

        $identity = $this->makeIdentity();
        $pdf = $this->makePdfFixtureUpload();

        $response = $this
            ->apiUploadProductReservationCustomFieldFileRequest($identity, [
                'file' => $pdf,
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.type', 'product_reservation_custom_field')
            ->assertJsonPath('data.ext', 'pdf')
            ->assertJsonMissingPath('data.url')
            ->assertJsonPath('data.preview', null)
            ->assertJsonPath('data.uses_pdf_preview', true)
            ->assertJsonPath('data.has_pdf_preview_pages', true)
            ->assertJsonMissingPath('data.pdf_preview_pages');

        /** @var Media $pdf_preview_page */
        $file = File::findByUid($response->json('data.uid'));
        $pdf_preview_page = $file->pdf_preview_pages()->with('presets')->firstOrFail();

        $this->assertNotNull($file);
        $this->assertSame(['page-1'], $file->pdf_preview_pages()->pluck('original_name')->toArray());
        $this->assertSame(
            ['private'],
            $pdf_preview_page
                ->presets
                ->map(fn ($preset) => Storage::disk('public')->getVisibility($preset->path))
                ->unique()
                ->values()
                ->toArray(),
        );
    }

    /**
     * @return void
     */
    public function testProductReservationPdfUploadFailsWhenConverterIsDisabled(): void
    {
        $this->fakeProductReservationPdfStorage(false);
        $converter = $this->bindFakePdfToImgConverter($this->makePdfToImgResponse());

        $this
            ->apiUploadProductReservationCustomFieldFileRequest($this->makeIdentity(), [
                'file' => $this->makePdfFixtureUpload(),
            ])
            ->assertJsonValidationErrors(['file']);

        $this->assertSame([], $converter->getRequests());
    }

    /**
     * @return void
     */
    public function testProductReservationPdfUploadDetectionDoesNotTrustClientExtension(): void
    {
        $this->fakeProductReservationPdfStorage(false);

        $this
            ->apiUploadProductReservationCustomFieldFileRequest($this->makeIdentity(), [
                'file' => $this->makePdfFixtureUpload('reservation.txt'),
            ])
            ->assertJsonValidationErrors(['file']);
    }

    /**
     * @return void
     */
    public function testProductReservationPdfUploadRejectsPdfContentWithNonPdfClientExtension(): void
    {
        $this->fakeProductReservationPdfStorage();
        $converter = $this->bindFakePdfToImgConverter($this->makePdfToImgResponse());

        $response = $this
            ->apiUploadProductReservationCustomFieldFileRequest($this->makeIdentity(), [
                'file' => $this->makePdfFixtureUpload('reservation.txt'),
            ])
            ->assertJsonValidationErrors(['file']);

        $this->assertSame(trans('validation.file_pdf_preview.extension'), $response->json('errors.file.0'));
        $this->assertSame([], $converter->getRequests());
    }

    /**
     * @return void
     */
    public function testProductReservationPdfUploadRejectsClientPreview(): void
    {
        $this->fakeProductReservationPdfStorage();

        $this
            ->apiUploadProductReservationCustomFieldFileRequest($this->makeIdentity(), [
                'file' => $this->makePdfFixtureUpload(),
                'file_preview' => UploadedFile::fake()->image('preview.jpg', 800, 800),
            ])
            ->assertJsonValidationErrors(['file_preview']);
    }

    /**
     * @return void
     */
    public function testProductReservationPdfUploadFailsWhenConverterFails(): void
    {
        $this->fakeProductReservationPdfStorage();
        $counts = $this->makePdfUploadCountsSnapshot();
        $converter = $this->bindFakePdfToImgConverter($this->makePdfToImgResponse());

        $converter->setException(new PdfToImgException('Conversion failed.'));

        $this
            ->apiUploadProductReservationCustomFieldFileRequest($this->makeIdentity(), [
                'file' => $this->makePdfFixtureUpload(),
            ])
            ->assertJsonValidationErrors(['file']);

        $this->assertPdfUploadCountsUnchanged($counts);
    }

    /**
     * @return void
     */
    public function testProductReservationPdfUploadReturnsPageLimitValidationWhenConverterRejectsPageCount(): void
    {
        $this->fakeProductReservationPdfStorage();
        $counts = $this->makePdfUploadCountsSnapshot();
        $converter = $this->bindFakePdfToImgConverter($this->makePdfToImgResponse());

        $converter->setException(new PdfToImgException(
            'PDF page count 16 exceeds maxPages 15.',
            errorCode: PdfToImgException::ERROR_MAX_PAGES_EXCEEDED,
            errorParams: [
                'pageCount' => 16,
                'maxPages' => 15,
            ],
        ));

        $response = $this
            ->apiUploadProductReservationCustomFieldFileRequest($this->makeIdentity(), [
                'file' => $this->makePdfFixtureUpload(),
            ])
            ->assertJsonValidationErrors(['file']);

        $this->assertSame(
            trans('validation.file_pdf_preview.too_many_pages', ['max' => 15]),
            $response->json('errors.file.0'),
        );
        $this->assertPdfUploadCountsUnchanged($counts);
    }

    /**
     * @return void
     */
    public function testProductReservationPdfUploadLogsConverterResolutionFailures(): void
    {
        $this->fakeProductReservationPdfStorage();
        $counts = $this->makePdfUploadCountsSnapshot();

        Config::set('forus.pdf_to_img.default', 'broken');
        Config::set('forus.pdf_to_img.connections.broken', ['driver' => 'broken']);

        $this->expectPdfPreviewFailureLog('PDF preview upload failed.', [
            '"stage": "resolve_converter"',
            '"type": "product_reservation_custom_field"',
            'Unsupported PDF to image converter driver [broken].',
        ]);

        $this
            ->apiUploadProductReservationCustomFieldFileRequest($this->makeIdentity(), [
                'file' => $this->makePdfFixtureUpload(),
            ])
            ->assertJsonValidationErrors(['file']);

        $this->assertPdfUploadCountsUnchanged($counts);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationPdfUploadCleansCreatedPreviewPagesWhenPreviewCreationFails(): void
    {
        $this->fakeProductReservationPdfStorage();
        $identity = $this->makeIdentity();
        $counts = $this->makePdfUploadCountsSnapshot();

        $this->bindFakePdfToImgConverter($this->makePdfToImgResponse(2));
        $this->bindMediaServiceThatFailsOnSecondUpload();

        try {
            resolve(PdfPreviewUploadService::class)->store(
                $this->makePdfFixtureUpload(),
                'product_reservation_custom_field',
                $identity->address,
            );

            $this->fail('The preview creation failure should be rethrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('Preview page upload failed.', $e->getMessage());
        }

        $this->assertPdfUploadCountsUnchanged($counts);
    }

    /**
     * @return void
     */
    public function testProductReservationPdfUploadFailsWhenConversionIsPartial(): void
    {
        $this->fakeProductReservationPdfStorage();
        $counts = $this->makePdfUploadCountsSnapshot();

        $this->bindFakePdfToImgConverter($this->makePdfToImgResponse(2, 1));

        $this->expectPdfPreviewFailureLog('PDF preview upload validation failed.', [
            '"stage": "validate_partial_generation"',
            '"page_count": 2',
            '"rendered_count": 1',
            '"page_items": 1',
            'PDF preview conversion returned incomplete page set.',
        ]);

        $this
            ->apiUploadProductReservationCustomFieldFileRequest($this->makeIdentity(), [
                'file' => $this->makePdfFixtureUpload(),
            ])
            ->assertJsonValidationErrors(['file']);

        $this->assertPdfUploadCountsUnchanged($counts);
    }

    /**
     * @return void
     */
    public function testProductReservationImageUploadStillWorks(): void
    {
        $this->fakeProductReservationPdfStorage(false);

        $this
            ->apiUploadProductReservationCustomFieldFileRequest($this->makeIdentity(), [
                'file' => UploadedFile::fake()->image('reservation.png'),
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.ext', 'png')
            ->assertJsonPath('data.preview', null)
            ->assertJsonPath('data.uses_pdf_preview', false)
            ->assertJsonPath('data.has_pdf_preview_pages', false)
            ->assertJsonMissingPath('data.pdf_preview_pages')
            ->assertJsonMissingPath('data.url');
    }

    /**
     * @return void
     */
    public function testReimbursementPdfUploadWithClientPreviewStillWorks(): void
    {
        $this->fakeProductReservationPdfStorage(false);

        $response = $this
            ->apiUploadFileRequest($this->makeIdentity(), [
                'type' => 'reimbursement_proof',
                'file' => $this->makePdfFixtureUpload('reimbursement.pdf'),
                'file_preview' => UploadedFile::fake()->image('preview.jpg', 800, 800),
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.ext', 'pdf')
            ->assertJsonPath('data.preview.type', 'reimbursement_file_preview')
            ->assertJsonPath('data.uses_pdf_preview', false)
            ->assertJsonPath('data.has_pdf_preview_pages', false)
            ->assertJsonMissingPath('data.pdf_preview_pages')
            ->assertJsonMissingPath('data.url');

        $file = File::findByUid($response->json('data.uid'));

        $this->assertSame(
            ['public'],
            $file
                ->preview
                ->presets
                ->map(fn ($preset) => Storage::disk('public')->getVisibility($preset->path))
                ->unique()
                ->values()
                ->toArray(),
        );
    }

    /**
     * @return array{files: int, media: int, preview_pages: int}
     */
    protected function makePdfUploadCountsSnapshot(): array
    {
        return [
            'files' => File::count(),
            'media' => Media::count(),
            'preview_pages' => Media::query()->where('type', 'file_pdf_preview_page')->count(),
        ];
    }

    /**
     * @param array{files: int, media: int, preview_pages: int} $counts
     * @return void
     */
    protected function assertPdfUploadCountsUnchanged(array $counts): void
    {
        $this->assertSame($counts['files'], File::count());
        $this->assertSame($counts['media'], Media::count());
        $this->assertSame($counts['preview_pages'], Media::query()->where('type', 'file_pdf_preview_page')->count());
    }

    /**
     * @return void
     */
    protected function bindMediaServiceThatFailsOnSecondUpload(): void
    {
        $this->app->instance('media', new class () extends MediaService {
            protected int $uploads = 0;

            /**
             * @param string|TmpFile $filePath
             * @param string $fileName
             * @param string $type
             * @param array|string|null $syncPresets
             * @throws RuntimeException
             * @return Media
             */
            public function uploadSingle(
                string|TmpFile $filePath,
                string $fileName,
                string $type,
                array|string|null $syncPresets = null,
            ): Media {
                $this->uploads++;

                if ($this->uploads === 2) {
                    throw new RuntimeException('Preview page upload failed.');
                }

                return parent::uploadSingle($filePath, $fileName, $type, $syncPresets);
            }
        });
    }

    /**
     * @param string $message
     * @param string[] $needles
     * @return void
     */
    protected function expectPdfPreviewFailureLog(string $message, array $needles): void
    {
        $logger = Mockery::mock();

        $logger
            ->shouldReceive('error')
            ->once()
            ->with(Mockery::on(function (string $loggedMessage) use ($message, $needles) {
                $missingNeedles = array_filter(
                    $needles,
                    fn (string $needle) => !str_contains($loggedMessage, $needle),
                );

                return str_contains($loggedMessage, $message) && empty($missingNeedles);
            }));

        Log::shouldReceive('channel')->once()->with('pdf_to_img')->andReturn($logger);
    }
}
