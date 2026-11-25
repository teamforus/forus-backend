<?php

namespace App\Services\MeAppApiService\Http\Resources\Provider;

use App\Models\VoucherToken;

/**
 * @property-read VoucherToken $resource
 */
class ProviderVoucherTokenResource extends ProviderVoucherResource
{
    public const array LOAD = [];

    public const array LOAD_NESTED = [
        'voucher' => ProviderVoucherResource::class,
    ];
}
