<?php

namespace App\Http\Requests\Api\Platform\Funds;

use App\Http\Requests\BaseFormRequest;
use App\Models\Prevalidation;
use App\Models\Voucher;
use App\Traits\ThrottleWithMeta;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;

class RedeemFundsRequest extends BaseFormRequest
{
    use ThrottleWithMeta;

    public function getAvailableVouchers(): Arrayable|Collection|Arrayable
    {
        return Voucher::whereNull('identity_address')->where([
            'activation_code' => $this->input('code'),
        ])->whereNotNull('activation_code')->get();
    }

    public function getUsedVouchers(): Arrayable|Collection|Arrayable
    {
        return Voucher::whereNotNull('identity_address')->where([
            'activation_code' => $this->input('code'),
        ])->whereNotNull('activation_code')->get();
    }

    /**
     * @return Prevalidation|null
     */
    public function getPrevalidation(): ?Prevalidation
    {
        return Prevalidation::findByCode($this->input('code'));
    }
}
