<?php

namespace App\Rules\Vouchers;

use App\Models\Voucher;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;

class IdentityVoucherAddressRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @param string|null $identity_address
     * @param string|null $voucher_type
     * @param string|null $fund_type
     */
    public function __construct(
        protected ?string $identity_address,
        protected ?string $voucher_type = null,
        protected ?string $fund_type = null,
    ) {}

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
            ->where('identity_address', $this->identity_address)
            ->where('id', $value));

        if (!is_null($this->fund_type)) {
            $query->whereHas('fund', function(Builder $builder) {
                $builder->where('type', '=', $this->fund_type);
            });
        }

        switch ($this->voucher_type) {
            case Voucher::TYPE_BUDGET; $query->whereNull('product_id'); break;
            case Voucher::TYPE_PRODUCT; $query->whereNotNull('product_id'); break;
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
