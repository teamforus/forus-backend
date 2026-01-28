<?php

namespace App\Http\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;

/**
 * @property Organization $resource
 */
class ProviderFinancialResource extends BaseJsonResource
{
    public const array LOAD = [];

    public const array LOAD_NESTED = [
        'logo' => MediaCompactResource::class,
        'business_type' => BusinessTypeResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return array_merge($this->resource->only([
            'highest_transaction', 'nr_transactions', 'total_spent',
        ]), [
            'total_spent_locale' => currency_format_locale($this->resource->total_spent),
            'highest_transaction_locale' => currency_format_locale($this->resource->highest_transaction),
            'provider' => new OrganizationBasicResource($this->resource),
        ]);
    }
}
