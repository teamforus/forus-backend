<?php

namespace App\Rules;

use App\Models\FundProvider;
use App\Models\Product;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Support\Env;

class FundProviderProductAvailableRule extends BaseRule
{
    protected int $maxAmount;
    private FundProvider $fundProvider;

    /**
     * Create a new rule instance.
     *
     * @param FundProvider $fundProvider
     */
    public function __construct(FundProvider $fundProvider)
    {
        $this->maxAmount = Env::get('MAX_SPONSOR_SUBSIDY_AMOUNT', 10000);
        $this->fundProvider = $fundProvider;
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
        $limit = $value['limit_total'] ?? null;
        $limit_per_identity = $value['limit_per_identity'] ?? null;
        $isSubsidyFund = $this->fundProvider->fund->isTypeSubsidy();

        /** @var Product $product */
        $product = ProductQuery::inStockAndActiveFilter(Product::whereOrganizationId(
            $this->fundProvider->organization_id
        ))->find($id);

        if (!$product) {
            return $this->rejectTrans('product_not_found');
        }

        if ($isSubsidyFund && (!is_numeric($amount) || $amount > $this->maxAmount || $amount < 0)) {
            return $this->reject(trans('validation.max.numeric', [
                'max' => currency_format_locale($product->price),
                'attribute' => trans('validation.attributes.amount'),
            ]));
        }

        if (!$product->unlimited_stock) {
            if (!is_null($limit) && (!is_numeric($limit) || $product->stock_amount < $limit)) {
                return $this->reject(trans('validation.max.numeric', [
                    'max' => $product->stock_amount,
                    'attribute' => trans('validation.attributes.limit_total'),
                ]));
            }

            if (!is_null($limit) && (!is_numeric($limit_per_identity) || $product->stock_amount < $limit_per_identity)) {
                return $this->reject(trans('validation.max.numeric', [
                    'max' => $product->stock_amount,
                    'attribute' => trans('validation.attributes.limit_total_per_identity'),
                ]));
            }
        }

        return true;
    }
}
