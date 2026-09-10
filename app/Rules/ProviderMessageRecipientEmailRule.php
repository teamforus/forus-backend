<?php

namespace App\Rules;

use App\Models\ProductReservation;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ProviderMessageRecipientEmailRule implements ValidationRule
{
    /**
     * @param ProductReservation $productReservation
     */
    public function __construct(
        protected ProductReservation $productReservation,
    ) {
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @param Closure(string, ?string=): PotentiallyTranslatedString $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->productReservation->voucher->identity?->email) {
            $fail(trans('validation.provider_message.recipient_email_missing'));
        }
    }
}
