<?php

namespace App\Http\Resources\Provider\App;

use App\Models\VoucherToken;

/**
 * @property VoucherToken $resource
 */
class ProviderAppVoucherTokenResource extends ProviderAppVoucherResource
{
    public const array LOAD = [];

    public const array LOAD_NESTED = [
        'voucher' => ProviderAppVoucherResource::class,
    ];
}
