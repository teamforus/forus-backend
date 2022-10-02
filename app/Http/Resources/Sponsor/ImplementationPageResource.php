<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\ImplementationBlockResource;
use App\Models\Implementation;
use App\Models\ImplementationPage;
use App\Models\ImplementationPageFaq;

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

        return array_merge($page->only([
            'id', 'page_type', 'description', 'description_alignment', 'description_html',
            'state', 'external', 'external_url', 'blocks', 'implementation_id',
        ]), [
            'blocks' => ImplementationBlockResource::collection($page->blocks),
            'url_webshop' => $this->webshopUrl($page),
            'implementation' => $this->getImplementationData($page->implementation),
            'faq' => $page->faq->map(function(ImplementationPageFaq $faq) {
                return $faq->only('id', 'title', 'description', 'description_html');
            }),
        ]);
    }

    /**
     * @param Implementation $implementation
     * @return array
     */
    protected function getImplementationData(Implementation $implementation): array
    {
        return $implementation->only('id', 'name', 'url_webshop', 'organization_id');
    }

    /**
     * @param ImplementationPage $page
     * @return string|null
     */
    public function webshopUrl(ImplementationPage $page): ?string
    {
        return $page->implementation->urlWebshop(ImplementationPage::webshopUriByPageType(
            $page->page_type
        ));
    }
}