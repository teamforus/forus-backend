<?php

namespace App\Rules\Vouchers;

use App\Models\Fund;
use App\Rules\ProductIdInStockRule;
use App\Rules\VouchersArraySumAmountsRule;
use Illuminate\Contracts\Validation\Rule;

class VoucherBatchRules
{
    /**
     * @param Fund $fund
     * @param mixed $vouchers
     * @return array<int, string|Rule>
     */
    public static function ruleAmount(Fund $fund, mixed $vouchers): array
    {
        return [
            'nullable',
            'required_without:vouchers.*.product_id',
            'numeric',
            'between:0,' . currency_format($fund->getMaxAmountPerVoucher()),
            new VouchersArraySumAmountsRule($fund, $vouchers),
        ];
    }

    /**
     * @param Fund $fund
     * @param array<int|string, int> $productCounts
     * @return array<int, string|Rule>
     */
    public static function ruleProductId(Fund $fund, array $productCounts): array
    {
        return [
            'nullable',
            'required_without:vouchers.*.amount',
            'exists:products,id',
            new ProductIdInStockRule($fund, $productCounts),
        ];
    }

    /**
     * @return string
     */
    public static function ruleLimitMultiplier(): string
    {
        return 'nullable|numeric|min:1|max:1000';
    }
}
