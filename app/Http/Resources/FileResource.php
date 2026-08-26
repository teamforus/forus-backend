<?php

namespace App\Http\Resources;

use App\Services\FileService\FilePdfPreviewService;
use App\Services\FileService\Models\File;
use Illuminate\Http\Request;

/**
 * @property File $resource
 */
class FileResource extends BaseJsonResource
{
    public const array LOAD = [
        'pdf_preview_pages',
    ];

    public const array LOAD_NESTED = [
        'preview' => MediaCompactResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $usesPdfPreviewPages = resolve(FilePdfPreviewService::class)->usesPdfPreviewPages($this->resource);
        $hasPdfPreviewPages = $usesPdfPreviewPages && $this->resource->pdf_preview_pages->isNotEmpty();
        $hasPreview = $this->resource->type === 'reimbursement_proof';

        return [
            ...$this->resource->only([
                'original_name', 'type', 'ext', 'uid', 'order',
            ]),
            'size' => pretty_file_size($this->resource->size),
            'uses_pdf_preview' => $usesPdfPreviewPages,
            'has_pdf_preview_pages' => $hasPdfPreviewPages,
            'preview' => $hasPreview ? new MediaCompactResource($this->resource->preview) : null,
        ];
    }
}
