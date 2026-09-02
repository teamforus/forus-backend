<?php

namespace App\Services\FileService;

use App\Services\FileService\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class FilePdfPreviewService
{
    protected const array PDF_PREVIEW_TYPES = [
        'product_reservation_custom_field',
    ];

    /**
     * @param string|null $type
     * @return bool
     */
    public function supportsPdfPreview(?string $type): bool
    {
        return in_array($type, self::PDF_PREVIEW_TYPES, true);
    }

    /**
     * @param string|null $type
     * @param UploadedFile|null $uploadedFile
     * @return bool
     */
    public function isPdfPreviewUpload(?string $type, ?UploadedFile $uploadedFile): bool
    {
        return $this->supportsPdfPreview($type) &&
            $uploadedFile &&
            Validator::make(['file' => $uploadedFile], ['file' => 'required|file|mimes:pdf'])->passes();
    }

    /**
     * @param UploadedFile|null $uploadedFile
     * @return bool
     */
    public function hasPdfClientExtension(?UploadedFile $uploadedFile): bool
    {
        return strtolower((string) $uploadedFile?->getClientOriginalExtension()) === 'pdf';
    }

    /**
     * @param File $file
     * @return bool
     */
    public function usesPdfPreviewPages(File $file): bool
    {
        return $file->isPdf() && $this->supportsPdfPreview($file->type);
    }
}
