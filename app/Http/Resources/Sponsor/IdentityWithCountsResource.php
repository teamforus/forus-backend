<?php

namespace App\Http\Resources\Sponsor;

use App\Models\Identity;

/**
 * @property-read Identity $resource
 */
class IdentityWithCountsResource extends IdentityResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge(
            parent::toArray($request),
            $this->getVoucherStats($this->resource),
        );
    }
}
