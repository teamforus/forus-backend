<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\ImplementationBlockResource;
use App\Models\ImplementationPage;

/**
 * @property ImplementationPage $resource
 */
class ImplementationPageResource extends BaseJsonResource
{
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
            'id', 'page_type', 'content', 'content_alignment', 'content_html',
            'state', 'external', 'external_url', 'blocks', 'implementation_id',
        ]), [
            'url_webshop'       => $this->webshopUrl($page),
            'blocks'            => ImplementationBlockResource::collection($page->blocks),
            'implementation'    => $page->implementation->only([
                'id', 'name', 'url_webshop', 'organization_id',
            ]),
        ]);
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