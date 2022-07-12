<?php

namespace App\Http\Resources;

use App\Models\ImplementationBlock;

/**
 * @property ImplementationBlock $resource
 * @noinspection PhpUnused
 */
class ImplementationBlockResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {
        $block = $this->resource;

        return array_merge($block->only([
            'id', 'label', 'title', 'description', 'button_text', 'button_link', 'button_enabled',
            'description', 'description_html',
        ]), [
            'media' => new MediaResource($block->photo),
        ]);
    }
}