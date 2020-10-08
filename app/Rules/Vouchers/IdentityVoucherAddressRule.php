<?php

namespace App\Rules\Vouchers;

use App\Models\Voucher;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class IdentityVoucherAddressRule
 * @package App\Rules\Vouchers
 */
class IdentityVoucherAddressRule implements Rule
{
    private $identity_address;
    private $voucher_type;
    private $fund_type;

    /**
     * Create a new rule instance.
     *
     * @param $identity_address
     * @param null $voucher_type
     * @param null $fund_type
     */
    public function __construct($identity_address, $voucher_type = null, $fund_type = null)
    {
        $this->identity_address = $identity_address;
        $this->voucher_type = $voucher_type;
        $this->fund_type = $fund_type;
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
        /** @var Builder $query */
        $query = Voucher::whereHas('tokens', function(Builder $builder) use ($value) {
            $builder->where('address', $value);
        })->where('identity_address', '=', $this->identity_address);

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
