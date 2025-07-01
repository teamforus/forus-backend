<?php

namespace App\Rules;

use App\Models\FundProvider;
use App\Models\FundProviderProduct;
use App\Models\Product;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Support\Env;

class FundProviderProductAvailableRule extends BaseRule
{
    protected int $maxAmount;

    /**
     * Create a new rule instance.
     *
     * @param FundProvider $fundProvider
     */
    public function __construct(protected FundProvider $fundProvider)
    {
        $this->maxAmount = Env::get('MAX_SPONSOR_SUBSIDY_AMOUNT', 10000);
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
        $id = $value['id'] ?? null;
        $amount = $value['amount'] ?? null;
        $limit_total = $value['limit_total'] ?? null;
        $limit_per_identity = $value['limit_per_identity'] ?? null;
        $is_subsidy_product = ($value['payment_type'] ?? null) === FundProviderProduct::PAYMENT_TYPE_SUBSIDY;

        $product = ProductQuery::inStockAndActiveFilter(Product::whereOrganizationId(
            $this->fundProvider->organization_id
        ))->find($id);

        if (!$product) {
            return $this->rejectTrans('product_not_found');
        }

        if ($is_subsidy_product && (!is_numeric($amount) || $amount > $this->maxAmount || $amount < 0)) {
            return $this->reject(trans('validation.max.numeric', [
                'max' => currency_format_locale($product->price),
                'attribute' => trans('validation.attributes.amount'),
            ]));
        }

        if (!$product->unlimited_stock) {
            if (!is_null($limit_total) && (!is_numeric($limit_total) || $product->stock_amount < $limit_total)) {
                return $this->reject(trans('validation.max.numeric', [
                    'max' => $product->stock_amount,
                    'attribute' => trans('validation.attributes.limit_total'),
                ]));
            }

            if (!is_null($limit_per_identity) && (!is_numeric($limit_per_identity) || $product->stock_amount < $limit_per_identity)) {
                return $this->reject(trans('validation.max.numeric', [
                    'max' => $product->stock_amount,
                    'attribute' => trans('validation.attributes.limit_total_per_identity'),
                ]));
            }
        }

        return true;
    }
}
