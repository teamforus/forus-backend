<?php

namespace App\Http\Requests\Api\Platform\Funds;

use App\Http\Requests\BaseFormRequest;
use App\Models\Prevalidation;
use App\Models\Voucher;
use App\Traits\ThrottleWithMeta;
use Illuminate\Support\Collection;

/**
 * Class RedeemFundsRequest
 * @package App\Http\Requests\Api\Platform\Funds
 */
class RedeemFundsRequest extends BaseFormRequest
{
    use ThrottleWithMeta;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     * @throws \App\Exceptions\AuthorizationJsonException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize(): bool
    {
        $this->maxAttempts = env('ACTIVATION_CODE_ATTEMPTS', 3);
        $this->decayMinutes = env('ACTIVATION_CODE_DECAY', 180);

        if ((bool) $this->auth_address()) {
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
                authorize('redeem', $prevalidation);
            }

            return true;
        }

        return false;
    }

    /**
     * @return Collection|Voucher[]
     */
    public function getAvailableVouchers(): Collection
    {
        return Voucher::whereNull('identity_address')->where([
            'activation_code' => $this->input('code'),
        ])->whereNotNull('activation_code')->get();
    }

    /**
     * @return Collection|Voucher[]
     */
    public function getUsedVouchers(): Collection
    {
        return Voucher::whereNotNull('identity_address')->where([
            'activation_code' => $this->input('code'),
        ])->whereNotNull('activation_code')->get();
    }

    /**
     * @return Prevalidation
     */
    public function getPrevalidation(): ?Prevalidation
    {
        return Prevalidation::findByCode($this->input('code'));
    }
}
