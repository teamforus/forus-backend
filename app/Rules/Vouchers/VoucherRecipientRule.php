<?php

namespace App\Rules\Vouchers;

use App\Models\Identity;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class VoucherRecipientRule implements ValidationRule
{
    /**
     * @param bool $byBsn
     */
    public function __construct(protected bool $byBsn = false)
    {
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_scalar($value)) {
            return;
        }

        $identity = $this->byBsn ? Identity::findByBsn((string) $value) : Identity::findByEmail((string) $value);

        if ($identity?->canReceiveVouchers() === false) {
            $fail(__('validation.voucher.managed_requester_inactive'));
        }
    }
}
