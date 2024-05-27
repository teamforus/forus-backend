<?php

namespace Tests\Traits;

use App\Models\Voucher;
use Carbon\Carbon;

trait FundFormulaProductTestTrait
{
    /**
     * @param Voucher $voucher
     * @param Carbon $startDate
     * @param bool $byAddress
     * @return void
     */
    protected function assertFundFormulaProducts(
        Voucher $voucher,
        Carbon $startDate,
        bool $byAddress = true
    ): void {
        if ($voucher->isBudgetType()) {
            foreach ($voucher->fund->fund_formula_products as $formulaProduct) {
                $address = $voucher->identity_address;
                $multiplier = $formulaProduct->getIdentityMultiplier($byAddress ? $address : null);

                $productVoucherCount = Voucher::query()
                    ->where('identity_address', $address)
                    ->where('note', $voucher->note)
                    ->where('product_id', $formulaProduct->product_id)
                    ->where('created_at', '>=', $startDate)
                    ->where('amount', $formulaProduct->price)
                    ->count();

                $this->assertEquals($multiplier, $productVoucherCount);
            }
        }
    }
}
