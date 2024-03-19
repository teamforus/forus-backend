<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\FaqResource;
use App\Http\Resources\ImplementationBlockResource;
use App\Models\Implementation;
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
     *
     * @return (\Illuminate\Http\Resources\Json\AnonymousResourceCollection|array|mixed|null|string)[]
     *
     * @psalm-return array{blocks: \Illuminate\Http\Resources\Json\AnonymousResourceCollection, url_webshop: null|string, implementation: array, faq: \Illuminate\Http\Resources\Json\AnonymousResourceCollection,...}
     */
    public function toArray($request): array
    {
        $page = $this->resource;

        return array_merge($page->only([
            'id', 'page_type', 'state', 'external', 'external_url', 'blocks', 'implementation_id',
            'description', 'description_alignment', 'description_position', 'description_html',
            'blocks_per_row',
        ]), [
            'blocks' => ImplementationBlockResource::collection($page->blocks),
            'url_webshop' => $this->webshopUrl($page),
            'implementation' => $this->getImplementationData($page->implementation),
            'faq' => FaqResource::collection($page->faq),
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
     */
    public function webshopUrl(ImplementationPage $page): string
    {
        return $page->implementation->urlWebshop(ImplementationPage::webshopUriByPageType(
            $page->page_type
        ));
    }
}