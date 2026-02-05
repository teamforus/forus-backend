<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\Small\FundSmallResource;
use App\Http\Resources\Tiny\OrganizationTinyResource;
use App\Models\FundProvider;
use Illuminate\Http\Request;

/**
 * @property FundProvider $resource
 */
class ProviderFundProviderResource extends BaseJsonResource
{
    public const array LOAD = [
        'fund_provider_products',
    ];

    public const array LOAD_NESTED = [
        'fund' => FundSmallResource::class,
        'organization' => OrganizationTinyResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $fundProvider = $this->resource;

        return array_merge($fundProvider->only([
            'id', 'organization_id', 'fund_id', 'state', 'state_locale',
            'allow_products', 'allow_some_products', 'allow_budget', 'excluded',
        ]), [
            'fund' => new FundSmallResource($fundProvider->fund),
            'products' => $fundProvider->fund_provider_products->pluck('product_id'),
            'organization' => new OrganizationTinyResource($fundProvider->organization),
            'can_cancel' => !$fundProvider->hasTransactions() && !$fundProvider->isApproved() && $fundProvider->isPending(),
        ]);
    }
}
