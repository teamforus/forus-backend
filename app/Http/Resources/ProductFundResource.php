<?php

namespace App\Http\Resources;

use App\Models\Fund;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ProductFundResource
 * @property Fund $resource
 * @package App\Http\Resources
 */
class ProductFundResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $fund = $this->resource;

        return array_merge($fund->only([
            'id', 'name', 'description', 'organization_id', 'state', 'approved',
        ]), [
            'key' => $fund->fund_config->key ?? '',
            'logo' => new MediaResource($fund->logo),
            'start_date' => $fund->start_date->format('Y-m-d'),
            'end_date' => $fund->end_date->format('Y-m-d'),
            'start_date_locale' => format_date_locale($fund->start_date),
            'end_date_locale' => format_date_locale($fund->end_date),
            'organization' => new OrganizationResource($fund->organization),
            'implementation' => new ImplementationResource($fund->fund_config->implementation),
        ]);
    }
}
