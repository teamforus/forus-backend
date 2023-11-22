<?php

namespace App\Http\Resources;

use App\Models\Implementation;
use App\Models\ImplementationPage;
use Illuminate\Http\Resources\Json\JsonResource;

class ImplementationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @property Implementation $resource
     * @return ?array
     */
    public function toArray($request): ?array
    {
        /** @var Implementation $implementation **/
        if (is_null($implementation = $this->resource)) {
            return null;
        }

        return array_merge($implementation->only([
            'id', 'key', 'name', 'url_webshop', 'informal_communication', 'organization_id',
            'pre_check_enabled', 'pre_check_title', 'pre_check_description',
            'pre_check_homepage_title', 'pre_check_homepage_description', 'pre_check_homepage_label',
        ]), [
            'has_provider_terms_page' => $this->hasTermsPage($implementation->page_provider),
        ]);
    }

    /**
     * @param ImplementationPage|null $page_provider
     * @return bool
     */
    protected function hasTermsPage(?ImplementationPage $page_provider): bool
    {
        return $page_provider && $page_provider->content;
    }
}
