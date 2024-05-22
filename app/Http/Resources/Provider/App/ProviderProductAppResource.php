<?php

namespace App\Http\Resources\Provider\App;

use App\Http\Resources\MediaResource;
use App\Http\Resources\OrganizationBasicResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * @property Product|null $resource
 */
class ProviderProductAppResource extends ProductResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $data = $this->baseFields($this->resource);

        return array_merge($data, [
            'photo' => new MediaResource($this->resource->photo),
            'organization' => new OrganizationBasicResource($this->resource->organization),
            'description_html' => $this->resource->description_html,
            'price_user' => currency_format(0),
            'price_user_locale' => 'Gratis',
            'sponsor_subsidy' => array_get($data, 'price'),
            'sponsor_subsidy_locale' => array_get($data, 'price_locale'),
        ]);
    }
}
