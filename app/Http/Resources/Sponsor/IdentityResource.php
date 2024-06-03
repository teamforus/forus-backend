<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Models\Identity;

/**
 * @property-read Identity $resource
 */
class IdentityResource extends BaseJsonResource
{
    public const LOAD = [
        'primary_email',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return $this->resource->only('id', 'email', 'address');
    }

    /**
     * Use IdentityQuery's addVouchersCountFields to load these columns
     *
     * @param Identity $identity
     * @return array
     */
    protected function getVoucherStats(Identity $identity): array
    {
        return $identity->only([
            'count_vouchers', 'count_vouchers_active', 'count_vouchers_active_with_balance',
        ]);
    }
}
