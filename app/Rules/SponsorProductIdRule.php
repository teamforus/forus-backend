<?php

namespace App\Rules;

use App\Models\Product;
use App\Scopes\Builders\ProductQuery;

class SponsorProductIdRule extends BaseRule
{
    /**
     * @param array $fundsIds
     */
    public function __construct(protected array $fundsIds)
    {

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
        $product_id = $value;

        $builder = ProductQuery::hasPendingOrAcceptedProviderForFund(
            Product::where('id', $product_id),
            $this->fundsIds
        );

        if (!$builder->exists()) {
            return $this->reject(__('validation.product_voucher.product_not_found'));
        }

        return true;
    }
}
