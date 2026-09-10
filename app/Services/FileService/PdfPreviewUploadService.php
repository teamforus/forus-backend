<?php

namespace App\Services\FileService;

use App\Services\FileService\Models\File;
use App\Services\MediaService\MediaService;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\TmpFile;
use App\Services\PdfToImgService\Data\PdfToImgRequestData;
use App\Services\PdfToImgService\Data\PdfToImgResponseData;
use App\Services\PdfToImgService\Exceptions\PdfToImgException;
use App\Services\PdfToImgService\PdfToImgService;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Throwable;

class PdfPreviewUploadService
{
    public function __construct(
        protected FileService $fileService,
        protected MediaService $mediaService,
    ) {
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param string $type
     * @param string|null $identityAddress
     * @throws Throwable
     * @return File
     */
    public function store(UploadedFile $uploadedFile, string $type, ?string $identityAddress): File
    {
        $file = null;
        $media = [];
        $conversionStage = null;

        try {
            $converterRequest = PdfToImgRequestData::fromConfig(file_get_contents($uploadedFile->getRealPath()));
            $conversionStage = 'resolve_converter';
            $pdfToImgService = resolve(PdfToImgService::class);
            $conversionStage = null;
            $conversion = $pdfToImgService->convert($converterRequest);

            $this->validateConversion($conversion, $converterRequest, $uploadedFile, $type);

            $file = $this->fileService->uploadSingle($uploadedFile, $type);

            $file->update([
                'identity_address' => $identityAddress,
            ]);

            $media = $this->makePreviewPages($conversion, $identityAddress);

            if (!$file->syncMedia(array_map(fn (Media $media) => $media->uid, $media), 'file_pdf_preview_page')) {
                $this->logConversionFailure(
                    'PDF preview upload failed.',
                    'sync_media',
                    new PdfToImgException('Failed to attach rendered PDF preview pages to the file.'),
                    $uploadedFile,
                    $type,
                );

                throw ValidationException::withMessages([
                    'file' => [trans('validation.file_pdf_preview.conversion_failed')],
                ]);
            }

            return $file;
        } catch (PdfToImgException $exception) {
            $this->cleanup($file, $media);

            if ($conversionStage === 'resolve_converter') {
                $this->logConversionFailure(
                    'PDF preview upload failed.',
                    $conversionStage,
                    $exception,
                    $uploadedFile,
                    $type,
                );
            }

            throw $this->makeConversionValidationException($exception);
        } catch (Throwable $exception) {
            $this->cleanup($file, $media);

            throw $exception;
        }
    }

    /**
     * @param PdfToImgResponseData $conversion
     * @param PdfToImgRequestData $request
     * @param UploadedFile $uploadedFile
     * @param string $type
     * @throws ValidationException
     * @return void
     */
    protected function validateConversion(
        PdfToImgResponseData $conversion,
        PdfToImgRequestData $request,
        UploadedFile $uploadedFile,
        string $type,
    ): void {
        if (
            $conversion->getPageCount() < 1 ||
            $conversion->getRenderedCount() !== $conversion->getPageCount() ||
            count($conversion->getPages()) !== $conversion->getPageCount()
        ) {
            $this->logConversionValidationFailure(
                'validate_partial_generation',
                new PdfToImgException('PDF preview conversion returned incomplete page set.'),
                $conversion,
                $request,
                $uploadedFile,
                $type,
            );

            throw ValidationException::withMessages([
                'file' => [trans('validation.file_pdf_preview.partial_generation')],
            ]);
        }

        foreach ($conversion->getPages() as $index => $page) {
            $imageSize = getimagesizefromstring($page->getImage());

            if (
                $page->getPage() !== $index + 1 ||
                $page->getContentType() !== 'image/jpeg' ||
                !$imageSize ||
                ($imageSize['mime'] ?? null) !== 'image/jpeg'
            ) {
                $this->logConversionValidationFailure(
                    'validate_page_image',
                    new PdfToImgException('PDF preview conversion returned invalid page image data.'),
                    $conversion,
                    $request,
                    $uploadedFile,
                    $type,
                    [
                        'page_index' => $index,
                        'page' => $page->getPage(),
                        'content_type' => $page->getContentType(),
                        'image_mime_type' => $imageSize['mime'] ?? null,
                    ],
                );

                throw ValidationException::withMessages([
                    'file' => [trans('validation.file_pdf_preview.conversion_failed')],
                ]);
            }
        }
    }

    /**
     * @param PdfToImgResponseData $conversion
     * @param string|null $identityAddress
     * @throws Throwable
     * @return Media[]
     */
    protected function makePreviewPages(PdfToImgResponseData $conversion, ?string $identityAddress): array
    {
        $media = [];

        try {
            foreach ($conversion->getPages() as $page) {
                $item = $this->mediaService->uploadSingle(
                    new TmpFile($page->getImage()),
                    "page-{$page->getPage()}.jpg",
                    'file_pdf_preview_page',
                );

                $media[] = $item;

                $item->update([
                    'identity_address' => $identityAddress,
                ]);
            }
        } catch (Throwable $exception) {
            $this->cleanupMedia($media);

            throw $exception;
        }

        return $media;
    }

    /**
     * @param File|null $file
     * @param Media[] $media
     * @throws Throwable
     * @return void
     */
    protected function cleanup(?File $file, array $media): void
    {
        $this->cleanupMedia($media);

        if ($file) {
            $this->fileService->deleteFile(ltrim($file->path, '/'));
            $file->delete();
        }
    }

    /**
     * @param Media[] $media
     * @throws Throwable
     * @return void
     */
    protected function cleanupMedia(array $media): void
    {
        foreach ($media as $item) {
            $this->mediaService->unlink($item);
        }
    }

    /**
     * @param PdfToImgException $exception
     * @return ValidationException
     */
    protected function makeConversionValidationException(PdfToImgException $exception): ValidationException
    {
        $params = $exception->getErrorParams();
        $maxPages = $params['maxPages'] ?? null;

        $message = $exception->getErrorCode() === PdfToImgException::ERROR_MAX_PAGES_EXCEEDED && is_numeric($maxPages)
            ? trans('validation.file_pdf_preview.too_many_pages', ['max' => $maxPages])
            : trans('validation.file_pdf_preview.conversion_failed');

        return ValidationException::withMessages([
            'file' => [$message],
        ]);
    }

    /**
     * @param string $stage
     * @param Throwable $exception
     * @param PdfToImgResponseData $conversion
     * @param PdfToImgRequestData $request
     * @param UploadedFile $uploadedFile
     * @param string $type
     * @param array $context
     * @return void
     */
    protected function logConversionValidationFailure(
        string $stage,
        Throwable $exception,
        PdfToImgResponseData $conversion,
        PdfToImgRequestData $request,
        UploadedFile $uploadedFile,
        string $type,
        array $context = [],
    ): void {
        $this->logConversionFailure(
            'PDF preview upload validation failed.',
            $stage,
            $exception,
            $uploadedFile,
            $type,
            array_merge([
                'page_count' => $conversion->getPageCount(),
                'rendered_count' => $conversion->getRenderedCount(),
                'page_items' => count($conversion->getPages()),
                'max_pages' => $request->getMaxPages(),
            ], $context),
        );
    }

    /**
     * @param string $message
     * @param string $stage
     * @param Throwable $exception
     * @param UploadedFile $uploadedFile
     * @param string $type
     * @param array $context
     * @return void
     */
    protected function logConversionFailure(
        string $message,
        string $stage,
        Throwable $exception,
        UploadedFile $uploadedFile,
        string $type,
        array $context = [],
    ): void {
        PdfToImgService::logError($message, $exception, array_filter(array_merge([
            'stage' => $stage,
            'type' => $type,
            'client_mime_type' => $uploadedFile->getClientMimeType(),
            'mime_type' => $uploadedFile->getMimeType(),
            'size' => $uploadedFile->getSize(),
            'extension' => $uploadedFile->getClientOriginalExtension(),
        ], $context), fn ($value) => $value !== null && $value !== ''));
    }
}
