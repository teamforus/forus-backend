<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\Small\FundSmallResource;
use App\Http\Resources\Tiny\OrganizationTinyResource;
use App\Models\FundProvider;

/**
 * @property FundProvider $resource
 */
class ProviderFundProviderResource extends BaseJsonResource
{
    public const LOAD = [
        'fund.logo.presets',
        'fund.fund_formulas',
        'fund.organization.logo.presets',
        'organization.logo.presets',
        'fund_provider_products',
        'fund_unsubscribes',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
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
            'can_unsubscribe' => $fundProvider->canUnsubscribe(),
        ]);
    }
}
