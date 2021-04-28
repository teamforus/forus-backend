<?php

namespace App\Http\Resources;

use App\Models\FundProvider;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ProviderFinancialResource
 * @property Organization $resource
 * @package App\Http\Resources
 */
class ProviderFinancialResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Organization $sponsor */
        $sponsor = $request->organization ?? abort(403);

        return FundProvider::getOrganizationProviderFinances($sponsor, $this->resource);
    }
}
