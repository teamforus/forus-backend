<?php

namespace Tests\Feature;

use App\Models\ReservationField;
use App\Services\FileService\Models\File;
use App\Services\MediaService\Models\Media;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\MakesProductReservationPdfFiles;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Throwable;

class ProductReservationCustomFieldValuesTest extends TestCase
{
    use MakesTestFunds;
    use MakesProductReservations;
    use MakesProductReservationPdfFiles;
    use DatabaseTransactions;

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderCanSaveOptionalFileCustomFieldWithoutFilesSelected(): void
    {
        ['provider' => $provider, 'reservation' => $reservation] = $this->makeReservationContext();

        $field = $this->makeReservationFileField($provider, 'provider optional file field');

        $this->apiUpdateProductReservationFieldByProvider($provider, $reservation, $field, [
            'value' => [],
        ]);

        $fieldValue = $reservation->custom_fields()->where('reservation_field_id', $field->id)->first();

        $this->assertNotNull($fieldValue);
        $this->assertNull($fieldValue->value);
        $this->assertSame([], $fieldValue->files()->pluck('uid')->toArray());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderCanClearExistingFileCustomField(): void
    {
        ['provider' => $provider, 'reservation' => $reservation] = $this->makeReservationContext();

        $field = $this->makeReservationFileField($provider, 'provider file field');

        $uploadedFile = $this->apiUploadProductReservationCustomFieldFile($provider->identity, [
            'file' => UploadedFile::fake()->image('existing-file.png'),
        ]);

        $this->apiUpdateProductReservationFieldByProvider($provider, $reservation, $field, [
            'value' => [$uploadedFile->uid],
        ]);

        $this->apiUpdateProductReservationFieldByProvider($provider, $reservation, $field, [
            'value' => [],
        ]);

        $fieldValue = $reservation->custom_fields()->firstWhere('reservation_field_id', $field->id);

        $this->assertNotNull($fieldValue);
        $this->assertNull($fieldValue->value);
        $this->assertSame([], $fieldValue->files()->pluck('uid')->toArray());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderCanReplaceExistingFileCustomFieldWithMultipleFiles(): void
    {
        ['provider' => $provider, 'reservation' => $reservation] = $this->makeReservationContext();

        $field = $this->makeReservationFileField($provider, 'provider multi file field');

        $uploadedFile = $this->apiUploadProductReservationCustomFieldFile($provider->identity, [
            'file' => UploadedFile::fake()->image('uploaded-file.png'),
        ]);

        $this->apiUpdateProductReservationFieldByProvider($provider, $reservation, $field, [
            'value' => [$uploadedFile->uid],
        ]);

        $firstFile = $this->apiUploadProductReservationCustomFieldFile($provider->identity, [
            'file' => UploadedFile::fake()->image('replacement-first.png'),
        ]);

        $secondFile = $this->apiUploadProductReservationCustomFieldFile($provider->identity, [
            'file' => UploadedFile::fake()->image('replacement-second.png'),
        ]);

        $this->apiUpdateProductReservationFieldByProvider($provider, $reservation, $field, [
            'value' => [$firstFile->uid, $secondFile->uid],
        ]);

        $fieldValue = $reservation->custom_fields()->firstWhere('reservation_field_id', $field->id);

        $this->assertNotNull($fieldValue);
        $this->assertNull($fieldValue->value);
        $this->assertSame([$firstFile->uid, $secondFile->uid], $fieldValue->files()->pluck('uid')->toArray());
        $this->assertSame(
            ['replacement-first.png', 'replacement-second.png'],
            $fieldValue->files()->pluck('original_name')->toArray(),
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderCanResubmitAttachedProviderFileCustomFieldValue(): void
    {
        ['provider' => $provider, 'reservation' => $reservation] = $this->makeReservationContext();

        $field = $this->makeReservationFileField($provider, 'provider resubmitted file field');

        $uploadedFile = $this->apiUploadProductReservationCustomFieldFile($provider->identity, [
            'file' => UploadedFile::fake()->image('resubmitted-file.png'),
        ]);

        $this->apiUpdateProductReservationFieldByProvider($provider, $reservation, $field, [
            'value' => [$uploadedFile->uid],
        ]);

        $this->apiUpdateProductReservationFieldByProvider($provider, $reservation, $field, [
            'value' => [$uploadedFile->uid],
        ]);

        $fieldValue = $reservation->custom_fields()->firstWhere('reservation_field_id', $field->id);

        $this->assertNotNull($fieldValue);
        $this->assertSame([$uploadedFile->uid], $fieldValue->files()->pluck('uid')->toArray());
        $this->assertSame(['resubmitted-file.png'], $fieldValue->files()->pluck('original_name')->toArray());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderRequiresValueForRequiredTextCustomFieldUpdate(): void
    {
        $this->assertProviderRequiresValueForRequiredCustomFieldUpdate(ReservationField::TYPE_TEXT);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderRequiresValueForRequiredBooleanCustomFieldUpdate(): void
    {
        $this->assertProviderRequiresValueForRequiredCustomFieldUpdate(ReservationField::TYPE_BOOLEAN);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterReservationRejectsInvalidBooleanCustomFieldValue(): void
    {
        ['provider' => $provider, 'product' => $product, 'voucher' => $voucher] = $this->makeReservationContext(false);

        $field = $provider->reservation_fields()->create([
            'label' => 'requester boolean field',
            'type' => ReservationField::TYPE_BOOLEAN,
            'description' => 'requester boolean field description',
            'required' => true,
            'fillable_by' => ReservationField::FILLABLE_BY_REQUESTER,
            'order' => 1,
        ]);

        $this->makeReservationStoreRequest($voucher, $product, [
            'custom_fields' => [
                $field->id => 'invalid',
            ],
        ])->assertJsonValidationErrors(["custom_fields.$field->id"]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterReservationRejectsUnsupportedCustomFieldType(): void
    {
        ['provider' => $provider, 'product' => $product, 'voucher' => $voucher] = $this->makeReservationContext(false);

        $field = $provider->reservation_fields()->create([
            'label' => 'unsupported requester field',
            'type' => 'unsupported_type',
            'description' => 'unsupported requester field description',
            'required' => true,
            'fillable_by' => ReservationField::FILLABLE_BY_REQUESTER,
            'order' => 1,
        ]);

        $this->makeReservationStoreRequest($voucher, $product, [
            'custom_fields' => [
                $field->id => 'unsupported value',
            ],
        ])->assertJsonValidationErrors(["custom_fields.$field->id"]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderSearchFindsReservationByAttachedCustomFieldFileName(): void
    {
        ['fund' => $fund, 'provider' => $provider, 'product' => $product, 'reservation' => $reservation] =
            $this->makeReservationContext();

        $secondVoucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $secondReservation = $this->makeReservation($secondVoucher, $product);

        $field = $this->makeReservationFileField($provider, 'provider searchable file field');

        $this->apiUpdateProductReservationFieldByProvider($provider, $reservation, $field, [
            'value' => [
                $this->apiUploadProductReservationCustomFieldFile($provider->identity, [
                    'file' => UploadedFile::fake()->image('search-first-file.png'),
                ])->uid,
                $this->apiUploadProductReservationCustomFieldFile($provider->identity, [
                    'file' => UploadedFile::fake()->image('search-second-file.png'),
                ])->uid,
            ],
        ]);

        $response = $this->apiGetProductReservationsByProviderRequest($provider, [
            'q' => 'search-second-file.png',
        ]);

        $response->assertSuccessful();

        $this->assertSame([$reservation->id], array_column($response->json('data'), 'id'));
        $this->assertNotSame($reservation->id, $secondReservation->id);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderReservationResponseIncludesPdfPreviewState(): void
    {
        Storage::fake('public');

        ['provider' => $provider, 'reservation' => $reservation] = $this->makeReservationContext();

        $field = $this->makeReservationFileField($provider, 'provider pdf file field');
        $file = $this->makeProductReservationCustomFieldFile($provider->identity, 'reservation.pdf');

        $firstPage = $this->makePdfPreviewPage('page-1.jpg');
        $secondPage = $this->makePdfPreviewPage('page-2.jpg');

        $file->syncMedia([$firstPage->uid, $secondPage->uid], 'file_pdf_preview_page');

        $fieldValue = $reservation->custom_fields()->create([
            'reservation_field_id' => $field->id,
            'value' => null,
        ]);

        $fieldValue->appendFilesByUid($file->uid);

        $this
            ->apiGetProductReservationsByProviderRequest($provider)
            ->assertSuccessful()
            ->assertJsonPath('data.0.custom_fields.0.files.0.uid', $file->uid)
            ->assertJsonMissingPath('data.0.custom_fields.0.files.0.url')
            ->assertJsonPath('data.0.custom_fields.0.files.0.preview', null)
            ->assertJsonPath('data.0.custom_fields.0.files.0.uses_pdf_preview', true)
            ->assertJsonPath('data.0.custom_fields.0.files.0.has_pdf_preview_pages', true)
            ->assertJsonMissingPath('data.0.custom_fields.0.files.0.pdf_preview_pages');
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReplacingPdfFileRemovesOldPreviewPages(): void
    {
        Storage::fake('public');

        ['provider' => $provider, 'reservation' => $reservation] = $this->makeReservationContext();

        $field = $this->makeReservationFileField($provider, 'provider pdf replacement field');
        $oldFile = $this->makeProductReservationPdfFile($provider->identity, 'old-reservation.pdf', 2);
        $newFile = $this->makeProductReservationPdfFile($provider->identity, 'new-reservation.pdf', 2);
        $oldPageIds = $oldFile->pdf_preview_pages()->pluck('id')->toArray();

        $this->apiUpdateProductReservationFieldByProvider($provider, $reservation, $field, [
            'value' => [$oldFile->uid],
        ]);

        $this->apiUpdateProductReservationFieldByProvider($provider, $reservation, $field, [
            'value' => [$newFile->uid],
        ]);

        $fieldValue = $reservation->custom_fields()->firstWhere('reservation_field_id', $field->id);

        $this->assertTrue(File::withTrashed()->findOrFail($oldFile->id)->trashed());
        Storage::disk(Config::get('file.filesystem_driver', 'local'))->assertMissing(ltrim($oldFile->path, '/'));
        $this->assertSame(0, Media::query()->whereIn('id', $oldPageIds)->count());
        $this->assertSame([$newFile->uid], $fieldValue->files()->pluck('uid')->toArray());
        $this->assertSame(2, $newFile->pdf_preview_pages()->count());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testClearingPdfFileRemovesPreviewPages(): void
    {
        Storage::fake('public');

        ['provider' => $provider, 'reservation' => $reservation] = $this->makeReservationContext();

        $field = $this->makeReservationFileField($provider, 'provider pdf clear field');
        $file = $this->makeProductReservationPdfFile($provider->identity, 'clear-reservation.pdf', 2);
        $pageIds = $file->pdf_preview_pages()->pluck('id')->toArray();

        $this->apiUpdateProductReservationFieldByProvider($provider, $reservation, $field, [
            'value' => [$file->uid],
        ]);

        $this->apiUpdateProductReservationFieldByProvider($provider, $reservation, $field, [
            'value' => [],
        ]);

        $fieldValue = $reservation->custom_fields()->firstWhere('reservation_field_id', $field->id);

        $this->assertTrue(File::withTrashed()->findOrFail($file->id)->trashed());
        Storage::disk(Config::get('file.filesystem_driver', 'local'))->assertMissing(ltrim($file->path, '/'));
        $this->assertSame(0, Media::query()->whereIn('id', $pageIds)->count());
        $this->assertSame([], $fieldValue->files()->pluck('uid')->toArray());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFailedPdfAttachmentKeepsPreviewPages(): void
    {
        Storage::fake('public');

        ['provider' => $provider, 'reservation' => $reservation] = $this->makeReservationContext();

        $field = $this->makeReservationFileField($provider, 'provider pdf failed attachment field');
        $file = $this->makeProductReservationPdfFile($this->makeIdentity(), 'other-identity-reservation.pdf', 2);
        $pageIds = $file->pdf_preview_pages()->pluck('id')->toArray();

        $this
            ->apiUpdateProductReservationFieldByProviderRequest($provider, $reservation, $field, [
                'value' => [$file->uid],
            ])
            ->assertJsonValidationErrors(['value.0']);

        $this->assertFalse(File::findOrFail($file->id)->trashed());
        $this->assertSame(2, Media::query()->whereIn('id', $pageIds)->count());
        $this->assertNull($reservation->custom_fields()->firstWhere('reservation_field_id', $field->id));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAttachingPdfFileDoesNotRunConverterAgain(): void
    {
        $this->fakeProductReservationPdfStorage();
        $converter = $this->bindFakePdfToImgConverter($this->makePdfToImgResponse(2));

        ['provider' => $provider, 'reservation' => $reservation] = $this->makeReservationContext();

        $field = $this->makeReservationFileField($provider, 'provider pdf attachment field');

        $file = $this->apiUploadProductReservationCustomFieldFile($provider->identity, [
            'file' => $this->makePdfFixtureUpload(),
        ]);

        $this->assertCount(1, $converter->getRequests());

        $this->apiUpdateProductReservationFieldByProvider($provider, $reservation, $field, [
            'value' => [$file->uid],
        ]);

        $this->assertCount(1, $converter->getRequests());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPdfFilesWithPreviewPagesRespectFileLimit(): void
    {
        Storage::fake('public');

        ['provider' => $provider, 'reservation' => $reservation] = $this->makeReservationContext();

        $fileUids = array_map(
            fn (int $i) => $this->makeProductReservationPdfFile($provider->identity, "reservation-$i.pdf", 3)->uid,
            range(1, 6),
        );

        $field = $this->makeReservationFileField($provider, 'provider pdf limit field');
        $firstFiveFileUids = array_slice($fileUids, 0, 5);

        $this->apiUpdateProductReservationFieldByProvider($provider, $reservation, $field, [
            'value' => $firstFiveFileUids,
        ]);

        $fieldValue = $reservation->custom_fields()->firstWhere('reservation_field_id', $field->id);

        $this->assertSame($firstFiveFileUids, $fieldValue->files()->pluck('uid')->toArray());
        $this->assertSame(15, $fieldValue->files()->with('pdf_preview_pages')->get()->sum(
            fn (File $file) => $file->pdf_preview_pages->count(),
        ));

        $this
            ->apiUpdateProductReservationFieldByProviderRequest($provider, $reservation, $field, [
                'value' => $fileUids,
            ])
            ->assertJsonValidationErrors(['value']);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderCannotDestroyRequesterCustomFieldFile(): void
    {
        ['provider' => $provider, 'reservation' => $reservation] = $this->makeReservationContext();

        $field = $this->makeReservationFileField(
            $provider,
            'requester protected file field',
            ReservationField::FILLABLE_BY_REQUESTER,
        );

        $uploadedFile = $this->apiUploadProductReservationCustomFieldFile($provider->identity, [
            'file' => UploadedFile::fake()->image('requester-protected-file.png'),
        ]);

        $fieldValue = $reservation->custom_fields()->create([
            'reservation_field_id' => $field->id,
            'value' => null,
        ]);

        $fieldValue->appendFilesByUid($uploadedFile->uid);
        $uploadedFile = $uploadedFile->refresh();

        $this->assertFalse(Gate::forUser($provider->identity)->allows('destroy', $uploadedFile));
    }

    /**
     * @param string $type
     * @throws Throwable
     * @return void
     */
    protected function assertProviderRequiresValueForRequiredCustomFieldUpdate(string $type): void
    {
        ['provider' => $provider, 'reservation' => $reservation] = $this->makeReservationContext();

        $field = $provider->reservation_fields()->create([
            'label' => "required provider $type field",
            'type' => $type,
            'description' => "required provider $type field description",
            'required' => true,
            'fillable_by' => ReservationField::FILLABLE_BY_PROVIDER,
            'order' => 1,
        ]);

        $this->apiUpdateProductReservationFieldByProviderRequest($provider, $reservation, $field, [
            'value' => null,
        ])->assertJsonValidationErrors(['value']);
    }
}
