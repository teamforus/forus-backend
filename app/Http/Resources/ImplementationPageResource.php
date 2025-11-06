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
        $translateDescription = $page->page_type === ImplementationPage::TYPE_BLOCK_HOME_PRODUCT_CATEGORIES;

        return [
            ...$page->only([
                'page_type', 'external', 'blocks_per_row', 'description_position', 'description_alignment',
                'description_html',
            ]),
            ...$page->translateColumns(
                $page->external
                    ? $page->only(['title'])
                    : [
                        ...$page->only(['title', 'description_html']),
                        ...$translateDescription ? $page->only('description') : [],
                    ],
            ),
            'external_url' => $page->external ? $page->external_url : '',
            'blocks' => ImplementationBlockResource::collection($page->blocks),
            'faq' => FaqResource::collection($page->faq),
        ];
    }
}
