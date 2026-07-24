<?php

namespace App\Services\CmsService\ImplementationBlocks\Resources;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\MediaResource;
use App\Services\CmsService\ImplementationBlocks\ImplementationCmsBlockService;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlock;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockItem;
use App\Services\MediaService\Models\Media;
use Illuminate\Http\Request;

/**
 * @property ImplementationCmsBlock $resource
 */
class ImplementationCmsBlockResource extends BaseJsonResource
{
    public const array LOAD = [
        'values.translation_values',
        'values.medias.presets',
        'items.values.translation_values',
        'items.values.medias.presets',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $cmsBlock = $this->resource;
        $cmsBlockService = resolve(ImplementationCmsBlockService::class);

        return [
            'id' => $cmsBlock->id,
            'block_type_key' => $cmsBlock->block_type_key,
            'state' => $cmsBlock->state,
            'order' => $cmsBlock->order,
            'values' => $cmsBlockService->resolveBlockValues($cmsBlock),
            'values_html' => $cmsBlockService->resolveBlockValuesHtml($cmsBlock),
            'media' => collect($cmsBlockService->resolveBlockMedia($cmsBlock))->map(
                fn (?Media $media) => new MediaResource($media),
            )->all(),
            'items' => $cmsBlock->items->map(fn (ImplementationCmsBlockItem $item) => [
                'id' => $item->id,
                'item_type_key' => $item->item_type_key,
                'order' => $item->order,
                'values' => $cmsBlockService->resolveItemValues($item),
                'values_html' => $cmsBlockService->resolveItemValuesHtml($item),
                'media' => collect($cmsBlockService->resolveItemMedia($item))->map(
                    fn (?Media $media) => new MediaResource($media),
                )->all(),
            ])->values(),
        ];
    }
}
