<?php

namespace App\Http\Resources;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\Product;
use App\Scopes\Builders\FundQuery;
use Illuminate\Http\Request;

/**
 * @property Fund $resource
 * @property Product $product
 * @property Organization $organization
 */
class ProductFundResource extends BaseJsonResource
{
    public const array LOAD = [];

    public const array LOAD_NESTED = [
        'logo' => MediaResource::class,
        'organization' => OrganizationResource::class,
        'fund_config.implementation' => ImplementationResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $fund = $this->resource;
        $fundProvider = $this->product->organization->fund_providers->where('fund_id', $fund->id)->first();

        return array_merge($fund->only([
            'id', 'name', 'description', 'organization_id', 'state',
        ]), [
            'key' => $fund->fund_config->key ?? '',
            'logo' => new MediaResource($fund->logo),
            'start_date' => $fund->start_date->format('Y-m-d'),
            'end_date' => $fund->end_date->format('Y-m-d'),
            'start_date_locale' => format_date_locale($fund->start_date),
            'end_date_locale' => format_date_locale($fund->end_date),
            'organization' => new OrganizationResource($fund->organization),
            'implementation' => new ImplementationResource($fund->fund_config->implementation),
            'approved' => FundQuery::whereProductsAreApprovedFilter(Fund::where([
                'id' => $fund->id,
            ]), $this->product)->exists(),
            'provider_excluded' => $fundProvider?->excluded,
        ]);
    }
}
