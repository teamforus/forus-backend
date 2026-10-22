<?php

namespace App\Services\MeAppApiService\Http\Resources\Requester;

use App\Http\Resources\BaseJsonResource;
use App\Models\Product;
use App\Services\MeAppApiService\Http\Resources\Shared\MediaResource;
use App\Services\MeAppApiService\Http\Resources\Shared\OrganizationResource;
use Illuminate\Http\Request;

/**
 * @property-read Product $resource
 */
class VoucherProductResource extends BaseJsonResource
{
    public const array LOAD_NESTED = [
        'photos' => MediaResource::class,
        'organization' => OrganizationResource::class,
    ];

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $product = $this->resource;

        return [
            ...$product->only([
                'id', 'name', 'description', 'price', 'price_locale', 'organization_id',
            ]),
            ...$product->translateColumns($product->only([
                'name',
            ])),
            'price_locale' => $product->priceLocale(),
            'photos' => MediaResource::collection($product->photos),
            'organization' => new OrganizationResource($product->organization),
        ];
    }
}
