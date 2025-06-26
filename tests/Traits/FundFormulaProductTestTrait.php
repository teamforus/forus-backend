<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Voucher;
use Carbon\Carbon;

use function PHPUnit\Framework\assertCount;

trait FundFormulaProductTestTrait
{
    /**
     * @param Fund $fund
     * @param Identity|null $identity
     * @param Carbon $startDate
     * @param string|null $note
     * @return void
     */
    protected function assertFundFormulaProductVouchersCreated(
        Fund $fund,
        ?Identity $identity,
        Carbon $startDate,
        string $note = null,
    ): void {
        assertCount(3, $fund->fund_formula_products);

        foreach ($fund->fund_formula_products as $formulaProduct) {
            $productVoucherCount = Voucher::query()
                ->where('identity_id', $identity?->id ?: null)
                ->where('product_id', $formulaProduct->product_id)
                ->where('created_at', '>=', $startDate)
                ->where('amount', floatval($formulaProduct->price) ?: $formulaProduct->product->price)
                ->where('note', $note)
                ->count();

            $this->assertEquals(
                $formulaProduct->getIdentityMultiplier($identity),
                $productVoucherCount,
            );
        }
    }

    /**
     * @param Voucher $voucher
     * @return void
     */
    protected function assertFundFormulaProductVouchersCreatedByMainVoucher(Voucher $voucher): void
    {
        $this->assertFundFormulaProductVouchersCreated(
            $voucher->fund,
            $voucher->identity,
            $voucher->created_at,
            $voucher->note,
        );
    }
}
