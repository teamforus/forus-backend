<?php

namespace Tests\Traits;

use App\Models\Identity;
use App\Services\FileService\Models\File;
use App\Services\MediaService\Models\Media;
use App\Services\PdfToImgService\Contracts\PdfToImgConverterContract;
use App\Services\PdfToImgService\Data\PdfToImgPageData;
use App\Services\PdfToImgService\Data\PdfToImgResponseData;
use App\Services\PdfToImgService\PdfToImgService;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Random\RandomException;
use Tests\Fakes\FakePdfToImgConverter;

trait MakesProductReservationPdfFiles
{
    use UsesPdfFixtures;

    /**
     * @param bool $enabled
     * @return void
     */
    protected function fakeProductReservationPdfStorage(bool $enabled = true): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Config::set('forus.pdf_to_img.enabled', $enabled);
    }

    /**
     * @param PdfToImgResponseData|null $response
     * @return FakePdfToImgConverter
     */
    protected function bindFakePdfToImgConverter(?PdfToImgResponseData $response = null): FakePdfToImgConverter
    {
        $converter = new FakePdfToImgConverter($response);

        $this->app->instance(PdfToImgConverterContract::class, $converter);
        $this->app->forgetInstance(PdfToImgService::class);
        $this->app->forgetInstance('pdf_to_img');

        return $converter;
    }

    /**
     * @param int $pageCount
     * @param int|null $renderedCount
     * @return PdfToImgResponseData
     */
    protected function makePdfToImgResponse(int $pageCount = 1, ?int $renderedCount = null): PdfToImgResponseData
    {
        $renderedCount ??= $pageCount;

        return new PdfToImgResponseData(
            pageCount: $pageCount,
            renderedCount: $renderedCount,
            dpi: 150,
            quality: 85,
            pages: $renderedCount > 0
                ? array_map(fn (int $page) => $this->makePdfToImgPage($page), range(1, $renderedCount))
                : [],
        );
    }

    /**
     * @param int $page
     * @return PdfToImgPageData
     */
    protected function makePdfToImgPage(int $page): PdfToImgPageData
    {
        return new PdfToImgPageData(
            page: $page,
            contentType: 'image/jpeg',
            width: 800,
            height: 1100,
            image: $this->makeJpegBytes(),
        );
    }

    /**
     * @param string $name
     * @return string
     */
    protected function makeJpegBytes(string $name = 'page.jpg'): string
    {
        $file = UploadedFile::fake()->image($name, 800, 1100);

        return file_get_contents($file->getRealPath());
    }

    /**
     * @param Identity $identity
     * @param string $name
     * @param string|null $content
     * @throws RandomException
     * @return File
     */
    protected function makeProductReservationCustomFieldFile(
        Identity $identity,
        string $name,
        ?string $content = null,
    ): File {
        $uid = File::makeUid();
        $path = '/files/product-reservation-' . bin2hex(random_bytes(8)) . "-$name";

        if ($content !== null) {
            Storage::disk(Config::get('file.filesystem_driver', 'local'))->put(ltrim($path, '/'), $content);
        }

        return File::create([
            'uid' => $uid,
            'original_name' => $name,
            'path' => $path,
            'size' => $content === null ? 2048 : strlen($content),
            'ext' => pathinfo($name, PATHINFO_EXTENSION),
            'type' => 'product_reservation_custom_field',
            'identity_address' => $identity->address,
        ]);
    }

    /**
     * @param Identity $identity
     * @param string $name
     * @throws RandomException
     * @return File
     */
    protected function makeProductReservationCustomFieldFileWithoutStorageObject(Identity $identity, string $name): File
    {
        return $this->makeProductReservationCustomFieldFile($identity, $name);
    }

    /**
     * @param Identity $identity
     * @param string $name
     * @param int $pageCount
     * @throws Exception
     * @return File
     */
    protected function makeProductReservationPdfFile(Identity $identity, string $name, int $pageCount): File
    {
        $file = $this->makeProductReservationCustomFieldFile($identity, $name);

        $this->attachPdfPreviewPages($file, array_map(fn (int $page) => "page-$page.jpg", range(1, $pageCount)));

        return $file->refresh();
    }

    /**
     * @param File $file
     * @param string[] $names
     * @param bool $loadPresets
     * @throws Exception
     * @return Media[]
     */
    protected function attachPdfPreviewPages(File $file, array $names, bool $loadPresets = false): array
    {
        $pages = array_map(fn (string $name) => $this->makePdfPreviewPage($name, $loadPresets), $names);

        $file->syncMedia(array_map(fn (Media $media) => $media->uid, $pages), 'file_pdf_preview_page');

        return $pages;
    }

    /**
     * @param string $name
     * @param bool $loadPresets
     * @throws Exception
     * @return Media
     */
    protected function makePdfPreviewPage(string $name = 'page.jpg', bool $loadPresets = false): Media
    {
        $name = pathinfo($name, PATHINFO_EXTENSION) ? $name : "$name.jpg";
        $file = UploadedFile::fake()->image($name, 800, 1100);

        $media = resolve('media')->uploadSingle(
            $file->getRealPath(),
            $file->getClientOriginalName(),
            'file_pdf_preview_page',
        );

        return $loadPresets ? $media->load('presets') : $media;
    }
}
