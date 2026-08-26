<?php

namespace App\Services\FileService;

use Illuminate\Support\Facades\Config;

class FileUploadConfigService
{
    public function __construct(
        protected FilePdfPreviewService $filePdfPreviewService,
    ) {
    }

    /**
     * @return string[]
     */
    public function getAllowedTypes(): array
    {
        return Config::get('file.allowed_types', []);
    }

    /**
     * @param string|null $type
     * @return bool
     */
    public function isTypeAllowed(?string $type): bool
    {
        return in_array($type, $this->getAllowedTypes(), true);
    }

    /**
     * @param string|null $type
     * @return string[]
     */
    public function getAllowedExtensions(?string $type): array
    {
        $extensions = Config::get(
            "file.allowed_extensions_per_type.$type",
            Config::get('file.allowed_extensions', []),
        );

        if ($this->filePdfPreviewService->supportsPdfPreview($type) && !Config::get('forus.pdf_to_img.enabled')) {
            return array_values(array_diff($extensions, ['pdf']));
        }

        return $extensions;
    }

    /**
     * @param string|null $type
     * @param string $extension
     * @return bool
     */
    public function isExtensionAllowed(?string $type, string $extension): bool
    {
        return in_array($extension, $this->getAllowedExtensions($type), true);
    }

    /**
     * @param string|null $type
     * @return int
     */
    public function getMaxSize(?string $type): int
    {
        return (int) Config::get(
            "file.allowed_size_per_type.$type",
            Config::get('file.max_file_size', 2000),
        );
    }
}
