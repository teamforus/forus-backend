<?php

namespace Tests\Feature;

use App\Models\Identity;
use App\Models\ReservationField;
use App\Services\FileService\Models\File;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Random\RandomException;
use Tests\TestCase;
use Tests\Traits\MakesProductReservationPdfFiles;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Throwable;
use ZipArchive;

class ProductReservationPdfArchiveDownloadTest extends TestCase
{
    use MakesProductReservations;
    use MakesProductReservationPdfFiles;
    use MakesTestFunds;
    use DatabaseTransactions;

    /**
     * @throws Throwable
     * @return void
     */
    public function testUploaderCanPreviewUnattachedProductReservationPdfArchive(): void
    {
        $this->fakeProductReservationPdfStorage();

        $identity = $this->makeIdentity();
        $file = $this->makeProductReservationCustomFieldFile($identity, 'reservation.pdf', 'pdf-content');
        $pages = $this->attachPdfPreviewPages($file, ['first-page', 'second-page'], true);

        $entries = $this->readZipEntries(
            $this->apiDownloadFileArchiveRequest($identity, $file),
            "file-pdf-$file->uid.zip",
        );

        $this->assertSame('pdf-content', $entries["original-$file->uid.pdf"]);
        $this->assertSame($pages[0]->getContent('original'), $entries['pages/1.jpg']);
        $this->assertSame($pages[1]->getContent('original'), $entries['pages/2.jpg']);
    }

    /**
     * @throws RandomException
     * @throws Throwable
     * @return void
     */
    public function testProviderCanDownloadAttachedProductReservationPdfArchive(): void
    {
        $this->fakeProductReservationPdfStorage();

        ['provider' => $provider, 'reservation' => $reservation] = $this->makeReservationContext();

        $field = $this->makeReservationFileField($provider, 'provider pdf archive field');

        $file = $this->makeProductReservationCustomFieldFile(
            $this->makeIdentity(),
            'attached-reservation.pdf',
            'attached-pdf',
        );
        $pages = $this->attachPdfPreviewPages($file, ['first-page'], true);

        $fieldValue = $reservation->custom_fields()->create([
            'reservation_field_id' => $field->id,
            'value' => null,
        ]);

        $fieldValue->appendFilesByUid($file->uid);

        $entries = $this->readZipEntries(
            $this->apiDownloadFileArchiveRequest($provider->identity, $file),
            "file-pdf-$file->uid.zip",
        );

        $this->assertSame('attached-pdf', $entries["original-$file->uid.pdf"]);
        $this->assertStringContainsString('PDF-bestanden kunnen actieve inhoud', $entries['warning.txt']);
        $this->assertSame($pages[0]->getContent('original'), $entries['pages/1.jpg']);
    }

    /**
     * @throws RandomException
     * @throws Throwable
     * @return void
     */
    public function testProviderCanDownloadAttachedProductReservationPdfPreviewArchive(): void
    {
        $this->fakeProductReservationPdfStorage();

        ['provider' => $provider, 'reservation' => $reservation] = $this->makeReservationContext();

        $field = $this->makeReservationFileField($provider, 'provider pdf preview archive field');
        $file = $this->makeProductReservationCustomFieldFile(
            $this->makeIdentity(),
            'attached-reservation.pdf',
            'attached-pdf',
        );
        $pages = $this->attachPdfPreviewPages($file, ['first-page', 'second-page'], true);

        $fieldValue = $reservation->custom_fields()->create([
            'reservation_field_id' => $field->id,
            'value' => null,
        ]);

        $fieldValue->appendFilesByUid($file->uid);

        $entries = $this->readZipEntries(
            $this->apiDownloadFilePreviewArchiveRequest($provider->identity, $file),
            "file-pdf-preview-$file->uid.zip",
        );

        $this->assertArrayNotHasKey("original-$file->uid.pdf", $entries);
        $this->assertArrayNotHasKey('warning.txt', $entries);
        $this->assertSame($pages[0]->getContent('original'), $entries['pages/1.jpg']);
        $this->assertSame($pages[1]->getContent('original'), $entries['pages/2.jpg']);
    }

    /**
     * @throws RandomException
     * @throws Throwable
     * @return void
     */
    public function testRequesterCanDownloadAttachedProductReservationPdfPreviewArchive(): void
    {
        $this->fakeProductReservationPdfStorage();

        [
            'provider' => $provider,
            'reservation' => $reservation,
            'voucher' => $voucher,
        ] = $this->makeReservationContext();

        $field = $this->makeReservationFileField(
            $provider,
            'requester pdf preview archive field',
            ReservationField::FILLABLE_BY_REQUESTER,
        );

        $file = $this->makeProductReservationCustomFieldFile(
            $voucher->identity,
            'attached-requester-reservation.pdf',
            'attached-pdf',
        );

        $pages = $this->attachPdfPreviewPages($file, ['first-page'], true);

        $fieldValue = $reservation->custom_fields()->create([
            'reservation_field_id' => $field->id,
            'value' => null,
        ]);

        $fieldValue->appendFilesByUid($file->uid);

        $entries = $this->readZipEntries(
            $this->apiDownloadFilePreviewArchiveRequest($voucher->identity, $file),
            "file-pdf-preview-$file->uid.zip",
        );

        $this->assertArrayNotHasKey("original-$file->uid.pdf", $entries);
        $this->assertArrayNotHasKey('warning.txt', $entries);
        $this->assertSame($pages[0]->getContent('original'), $entries['pages/1.jpg']);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUnrelatedProviderCannotDownloadAttachedProductReservationPdfArchive(): void
    {
        $this->fakeProductReservationPdfStorage();

        [
            'provider' => $provider,
            'reservation' => $reservation,
            'voucher' => $voucher,
        ] = $this->makeReservationContext();

        $otherProvider = $this->makeTestProviderOrganization($this->makeIdentity());

        $field = $this->makeReservationFileField(
            $provider,
            'requester pdf archive field',
            ReservationField::FILLABLE_BY_REQUESTER,
        );

        $file = $this->makeProductReservationCustomFieldFile(
            $voucher->identity,
            'attached-requester-reservation.pdf',
            'attached-pdf',
        );
        $this->attachPdfPreviewPages($file, ['first-page']);

        $fieldValue = $reservation->custom_fields()->create([
            'reservation_field_id' => $field->id,
            'value' => null,
        ]);

        $fieldValue->appendFilesByUid($file->uid);

        $this->apiDownloadFileArchiveRequest($otherProvider->identity, $file)->assertForbidden();
        $this->apiDownloadFilePreviewArchiveRequest($otherProvider->identity, $file)->assertForbidden();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterCannotDownloadAttachedProductReservationPdfArchive(): void
    {
        $this->fakeProductReservationPdfStorage();

        [
            'provider' => $provider,
            'reservation' => $reservation,
            'voucher' => $voucher,
        ] = $this->makeReservationContext();

        $field = $this->makeReservationFileField(
            $provider,
            'requester pdf archive field',
            ReservationField::FILLABLE_BY_REQUESTER,
        );

        $file = $this->makeProductReservationCustomFieldFile(
            $voucher->identity,
            'attached-requester-reservation.pdf',
            'attached-pdf',
        );
        $this->attachPdfPreviewPages($file, ['first-page']);

        $fieldValue = $reservation->custom_fields()->create([
            'reservation_field_id' => $field->id,
            'value' => null,
        ]);

        $fieldValue->appendFilesByUid($file->uid);

        $this->apiDownloadFileArchiveRequest($voucher->identity, $file)->assertForbidden();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUnrelatedIdentityCannotDownloadProductReservationPdfArchive(): void
    {
        $this->fakeProductReservationPdfStorage();

        $file = $this->makeProductReservationCustomFieldFile($this->makeIdentity(), 'reservation.pdf', 'pdf-content');
        $this->attachPdfPreviewPages($file, ['first-page']);

        $this->apiDownloadFileArchiveRequest($this->makeIdentity(), $file)->assertForbidden();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testArchiveUsesPreviewPageOrder(): void
    {
        $this->fakeProductReservationPdfStorage();

        $identity = $this->makeIdentity();
        $file = $this->makeProductReservationCustomFieldFile($identity, 'reservation.pdf', 'pdf-content');
        $firstPage = $this->makePdfPreviewPage('first-page', true);
        $secondPage = $this->makePdfPreviewPage('second-page', true);
        $providerIdentity = $this->attachProductReservationFileToProviderReservation($file);

        $file->syncMedia([$secondPage->uid, $firstPage->uid], 'file_pdf_preview_page');

        $entries = $this->readZipEntries(
            $this->apiDownloadFileArchiveRequest($providerIdentity, $file),
            "file-pdf-$file->uid.zip",
        );

        $this->assertSame($secondPage->getContent('original'), $entries['pages/1.jpg']);
        $this->assertSame($firstPage->getContent('original'), $entries['pages/2.jpg']);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRawDownloadRejectsProductReservationPdf(): void
    {
        $this->fakeProductReservationPdfStorage();

        $identity = $this->makeIdentity();
        $file = $this->makeProductReservationCustomFieldFile($identity, 'reservation.pdf', 'pdf-content');

        $this->apiDownloadFileRequest($identity, $file)->assertForbidden();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRawDownloadStillWorksForNonPdfProductReservationFile(): void
    {
        $this->fakeProductReservationPdfStorage();

        $identity = $this->makeIdentity();
        $file = $this->makeProductReservationCustomFieldFile($identity, 'reservation.png', 'image-content');

        $response = $this->apiDownloadFileRequest($identity, $file)->assertSuccessful();

        ob_start();
        $response->sendContent();

        $this->assertSame('image-content', ob_get_clean());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderCanDownloadAttachedProductReservationImageFile(): void
    {
        $this->fakeProductReservationPdfStorage();

        [
            'provider' => $provider,
            'reservation' => $reservation,
            'voucher' => $voucher,
        ] = $this->makeReservationContext();

        $field = $this->makeReservationFileField(
            $provider,
            'requester image field',
            ReservationField::FILLABLE_BY_REQUESTER,
        );
        $file = $this->makeProductReservationCustomFieldFile(
            $voucher->identity,
            'attached-requester-reservation.png',
            'image-content',
        );

        $fieldValue = $reservation->custom_fields()->create([
            'reservation_field_id' => $field->id,
            'value' => null,
        ]);

        $fieldValue->appendFilesByUid($file->uid);

        $response = $this->apiDownloadFileRequest($provider->identity, $file)->assertSuccessful();

        ob_start();
        $response->sendContent();

        $this->assertSame('image-content', ob_get_clean());
    }

    /**
     * @throws RandomException
     * @return void
     */
    public function testArchiveRejectsNonProductReservationPdf(): void
    {
        $this->fakeProductReservationPdfStorage();

        $identity = $this->makeIdentity();
        $file = $this->makeStoredFile($identity, 'reimbursement.pdf', 'reimbursement_proof', 'pdf-content');

        $this->apiDownloadFileArchiveRequest($identity, $file)->assertForbidden();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testArchiveReturnsNotFoundWhenOriginalPdfStorageObjectIsMissing(): void
    {
        $this->fakeProductReservationPdfStorage();

        $identity = $this->makeIdentity();
        $file = $this->makeProductReservationCustomFieldFileWithoutStorageObject($identity, 'reservation.pdf');
        $this->attachPdfPreviewPages($file, ['first-page']);
        $providerIdentity = $this->attachProductReservationFileToProviderReservation($file);

        $this->apiDownloadFileArchiveRequest($providerIdentity, $file)->assertNotFound();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPreviewArchiveDoesNotRequireOriginalPdfStorageObject(): void
    {
        $this->fakeProductReservationPdfStorage();

        $identity = $this->makeIdentity();
        $file = $this->makeProductReservationCustomFieldFileWithoutStorageObject($identity, 'reservation.pdf');
        $pages = $this->attachPdfPreviewPages($file, ['first-page'], true);
        $providerIdentity = $this->attachProductReservationFileToProviderReservation($file);

        $entries = $this->readZipEntries(
            $this->apiDownloadFilePreviewArchiveRequest($providerIdentity, $file),
            "file-pdf-preview-$file->uid.zip",
        );

        $this->assertArrayNotHasKey("original-$file->uid.pdf", $entries);
        $this->assertArrayNotHasKey('warning.txt', $entries);
        $this->assertSame($pages[0]->getContent('original'), $entries['pages/1.jpg']);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPreviewArchiveReturnsNotFoundWhenPreviewPagesAreMissing(): void
    {
        $this->fakeProductReservationPdfStorage();

        $identity = $this->makeIdentity();
        $file = $this->makeProductReservationCustomFieldFile($identity, 'reservation.pdf', 'pdf-content');
        $providerIdentity = $this->attachProductReservationFileToProviderReservation($file);

        $this->apiDownloadFilePreviewArchiveRequest($providerIdentity, $file)->assertNotFound();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testArchiveWithoutPreviewPagesContainsOriginalPdfAndWarning(): void
    {
        $this->fakeProductReservationPdfStorage();

        $identity = $this->makeIdentity();
        $file = $this->makeProductReservationCustomFieldFile($identity, 'reservation.pdf', 'pdf-content');
        $providerIdentity = $this->attachProductReservationFileToProviderReservation($file);

        $entries = $this->readZipEntries(
            $this->apiDownloadFileArchiveRequest($providerIdentity, $file),
            "file-pdf-$file->uid.zip",
        );

        $this->assertSame('pdf-content', $entries["original-$file->uid.pdf"]);
        $this->assertStringContainsString('geen voorbeeldafbeeldingen beschikbaar', $entries['warning.txt']);
        $this->assertArrayNotHasKey('pages/1.jpg', $entries);
    }

    /**
     * @param File $file
     * @return Identity
     */
    protected function attachProductReservationFileToProviderReservation(File $file): Identity
    {
        ['provider' => $provider, 'reservation' => $reservation] = $this->makeReservationContext();
        $field = $this->makeReservationFileField($provider, 'provider pdf archive field');

        $fieldValue = $reservation->custom_fields()->create([
            'reservation_field_id' => $field->id,
            'value' => null,
        ]);

        $fieldValue->appendFilesByUid($file->uid);

        return $provider->identity;
    }

    /**
     * @param Identity $identity
     * @param string $name
     * @param string $type
     * @param string|null $content
     * @throws RandomException
     * @return File
     */
    protected function makeStoredFile(Identity $identity, string $name, string $type, ?string $content): File
    {
        $uid = File::makeUid();
        $path = '/files/archive-test-' . bin2hex(random_bytes(8)) . "-$name";

        if ($content !== null) {
            Storage::disk(Config::get('file.filesystem_driver', 'local'))->put(ltrim($path, '/'), $content);
        }

        return File::create([
            'uid' => $uid,
            'original_name' => $name,
            'path' => $path,
            'size' => strlen($content ?? ''),
            'ext' => pathinfo($name, PATHINFO_EXTENSION),
            'type' => $type,
            'identity_address' => $identity->address,
        ]);
    }

    /**
     * @param TestResponse $response
     * @param string $fileName
     * @return array<string, string>
     */
    protected function readZipEntries(TestResponse $response, string $fileName): array
    {
        $response->assertSuccessful();
        $response->assertDownload($fileName);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $path = tempnam(sys_get_temp_dir(), 'file-pdf-archive-test-');
        file_put_contents($path, $content);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path));

        $entries = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            $entries[$name] = $zip->getFromName($name);
        }

        $zip->close();
        @unlink($path);

        return $entries;
    }
}
