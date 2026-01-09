<?php

namespace App\Http\Resources;

use App\Models\ImplementationBlock;

/**
 * @property ImplementationBlock $resource
 */
class ImplementationBlockResource extends BaseJsonResource
{
    public const array LOAD = [
        'photo',
        'implementation_page.implementation',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {
        $block = $this->resource;

        return [
            ...$block->only([
                'id', 'description', 'button_link', 'button_target_blank', 'button_enabled',
                'button_link_label', 'description_html',
            ]),
            ...$block->translateColumns($block->only([
                'label', 'title', 'button_text', 'description_html',
            ])),
            'media' => new MediaResource($block->photo),
        ];
    }
}
