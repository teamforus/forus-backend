<?php

namespace App\Http\Resources;

use App\Models\ImplementationPage;

/**
 * @property ImplementationPage $resource
 */
class ImplementationPageResource extends BaseJsonResource
{
    public const array LOAD = [
        'faq',
        'blocks.photo',
        'blocks.implementation_page.implementation',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {
        $page = $this->resource;

        return [
            ...$page->only([
                'page_type', 'external', 'blocks_per_row', 'description_position', 'description_alignment',
                'description_html',
            ]),
            ...$page->translateColumns(
                $page->external
                    ? $page->only(['name'])
                    : $page->only(['name', 'description_html']),
            ),
            'external_url' => $page->external ? $page->external_url : '',
            'blocks' => ImplementationBlockResource::collection($page->blocks),
            'faq' => FaqResource::collection($page->faq),
        ];
    }
}
