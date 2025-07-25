<?php

namespace App\Http\Resources\Small;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\ImplementationResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\Tiny\OrganizationTinyResource;
use App\Models\Fund;

/**
 * @property Fund $resource
 * @property ?string $stats
 */
class FundSmallResource extends BaseJsonResource
{
    public const array LOAD = [
        'logo.presets',
        'fund_formulas',
        'organization.logo.presets',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $fundAmount = $this->resource->amountFixedByFormula();

        return [
            ...$this->resource->only([
                'id', 'name', 'description', 'description_html', 'description_short',
                'organization_id', 'state', 'type', 'type_locale', 'archived', 'request_btn_text',
                'external_link_text', 'external_link_url', 'external',
            ]),
            'logo' => new MediaResource($this->resource->logo),
            'organization' => new OrganizationTinyResource($this->resource->organization),
            'fund_amount' => $fundAmount ? currency_format($fundAmount) : null,
            'fund_amount_locale' => $fundAmount ? currency_format_locale($fundAmount) : null,
            'implementation' => new ImplementationResource($this->resource->fund_config->implementation ?? null),
            ...$this->makeTimestamps($this->resource->only(['start_date', 'end_date']), true),
        ];
    }
}
