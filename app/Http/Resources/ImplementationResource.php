<?php

namespace App\Http\Resources;

use App\Models\Implementation;
use App\Models\ImplementationPage;

class ImplementationResource extends BaseJsonResource
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

        return [
            ...$implementation->only([
                'id', 'key', 'name', 'url_webshop', 'informal_communication', 'organization_id',
                'pre_check_enabled', 'pre_check_title', 'pre_check_description',
                'pre_check_banner_state', 'pre_check_banner_title',
                'pre_check_banner_description', 'pre_check_banner_label',
            ]),
            'has_provider_terms_page' => $implementation->page_provider?->isPublic(),
        ];
    }
}
