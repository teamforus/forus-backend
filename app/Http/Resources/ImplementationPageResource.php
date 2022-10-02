<?php

namespace App\Http\Resources;

use App\Models\ImplementationPageFaq;
use App\Models\ImplementationPage;

/**
 * @property ImplementationPage $resource
 */
class ImplementationPageResource extends BaseJsonResource
{
    public const LOAD = [
        'faq',
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

        return array_merge($page->only('page_type', 'external', 'description_alignment'), [
            'description_html' => $page->external ? '' : $page->description_html,
            'external_url' => $page->external ? $page->external_url : '',
            'blocks' => ImplementationBlockResource::collection($page->blocks),
            'faq' => $page->faq->map(function(ImplementationPageFaq $faq) {
                return $faq->only('id', 'title', 'description', 'description_html');
            }),
        ]);
    }
}