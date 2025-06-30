<?php

namespace App\Rules\Vouchers;

use App\Models\Identity;
use App\Models\Voucher;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Contracts\Validation\Rule;

class IdentityVoucherAddressRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @param Identity|null $identity
     * @param string|null $voucher_type
     */
    public function __construct(
        protected ?Identity $identity,
        protected ?string $voucher_type = null,
    ) {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $query = VoucherQuery::whereNotExpiredAndActive(Voucher::query()
            ->where('identity_id', $this->identity?->id)
            ->where('id', $value));

        switch ($this->voucher_type) {
            case Voucher::TYPE_BUDGET: $query->whereNull('product_id');
                break;
            case Voucher::TYPE_PRODUCT: $query->whereNotNull('product_id');
                break;
        }

        return $query->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The validation error message.';
    }
}
