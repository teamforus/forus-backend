<?php

namespace App\Http\Resources;

use App\Models\ImplementationBlock;

/**
 * @property ImplementationBlock $resource
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
            'id', 'label', 'title', 'description', 'description_html',
            'button_text', 'button_link', 'button_target_blank', 'button_enabled',
        ]), [
            'media' => new MediaResource($block->photo),
        ]);
    }
}