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

    /**
     * Determine if the user is authorized to make this request.
     *
     * @throws \App\Exceptions\AuthorizationJsonException
     * @return bool
     */
    public function authorize(): bool
    {
        $this->maxAttempts = Config::get('forus.throttles.activation_code.attempts');
        $this->decayMinutes = Config::get('forus.throttles.activation_code.decay');

        if ($this->isAuthenticated()) {
            $prevalidation = $this->getPrevalidation();
            $vouchersAvailable = $this->getAvailableVouchers();
            $vouchersUsed = $this->getUsedVouchers();

            if ($vouchersAvailable->isEmpty() && $vouchersUsed->isEmpty() && !$prevalidation) {
                $this->incrementLoginAttempts($this);
                $this->responseWithThrottleMeta('not_found', $this, 'prevalidations', 404);
            }

            if (($vouchersAvailable->isEmpty() && $vouchersUsed->isNotEmpty()) ||
                ($prevalidation && $prevalidation->is_used)) {
                $this->incrementLoginAttempts($this);
                $this->responseWithThrottleMeta('used', $this, 'prevalidations', 403);
            }

            if ($prevalidation) {
                return Gate::allows('redeem', $prevalidation);
            }

            return true;
        }

        return false;
    }

    /**
     * @return Collection|Voucher[]
     */
    public function getAvailableVouchers(): Collection|Arrayable
    {
        return Voucher::whereNull('identity_id')->where([
            'activation_code' => $this->input('code'),
        ])->whereNotNull('activation_code')->get();
    }

    /**
     * @return Collection|Voucher[]
     */
    public function getUsedVouchers(): Collection|Arrayable
    {
        return Voucher::whereNotNull('identity_id')->where([
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
