<?php

namespace Tests\Feature;

use App\Mail\Vouchers\VoucherAssignedBudgetMail;
use App\Models\Fund;
use App\Models\FundFormula;
use App\Models\Identity;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class FundAwardTransactionTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestFundRequests;
    use MakesTestFunds;
    use MakesTestOrganizations;

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrdinaryApplyRollsBackMainVoucherWhenAdditionalCreationFails(): void
    {
        ['fund' => $fund, 'identity' => $identity] = $this->makeOrdinaryAwardFixture();
        $firstVoucherId = null;
        $dispatcher = Voucher::getEventDispatcher();

        Voucher::setEventDispatcher(clone $dispatcher);

        try {
            Voucher::creating(function (Voucher $voucher) use ($fund, $identity, &$firstVoucherId): void {
                if ($voucher->fund_id !== $fund->id || $voucher->identity_id !== $identity->id) {
                    return;
                }

                $firstVoucherId = $fund->vouchers()->where('identity_id', $identity->id)->value('id');

                if ($firstVoucherId !== null) {
                    throw new RuntimeException('Injected additional voucher failure.');
                }
            });

            $this->apiApplyForFundRequest($fund, $identity)->assertStatus(500);
        } finally {
            Voucher::setEventDispatcher($dispatcher);
        }

        $this->assertNotNull($firstVoucherId);
        $this->assertDatabaseMissing('vouchers', ['id' => $firstVoucherId]);
        $this->assertFalse($fund->vouchers()->exists());
        $this->assertMailableNotSent($identity->email, VoucherAssignedBudgetMail::class);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testApplicableFundsKeepCompletedAwardWhenAnotherFundFails(): void
    {
        ['fund' => $fund, 'identity' => $identity] = $this->makeOrdinaryAwardFixture();
        $second = $this->makeTestFund($fund->organization, implementation: $fund->fund_config->implementation);

        $this->configureOrdinaryAwardFormulas($second);

        $observedFundIds = [];
        $partialVoucherId = null;
        $dispatcher = Voucher::getEventDispatcher();
        Voucher::setEventDispatcher(clone $dispatcher);

        try {
            Voucher::creating(function (Voucher $voucher) use (
                $fund,
                $second,
                $identity,
                &$observedFundIds,
                &$partialVoucherId,
            ): void {
                if ($voucher->identity_id !== $identity->id ||
                    !in_array($voucher->fund_id, [$fund->id, $second->id])) {
                    return;
                }

                if (!in_array($voucher->fund_id, $observedFundIds)) {
                    $observedFundIds[] = $voucher->fund_id;
                }

                if ($voucher->fund_id === $observedFundIds[0]) {
                    return;
                }

                $partialVoucherId = Voucher::where('fund_id', $voucher->fund_id)->value('id');

                if ($partialVoucherId !== null) {
                    throw new RuntimeException('Injected later fund failure.');
                }
            });

            try {
                $fund->fund_config->implementation->makeVouchersInApplicableFunds($identity);
                $this->fail('Expected the second fund award to fail.');
            } catch (RuntimeException $exception) {
                $this->assertSame('Injected later fund failure.', $exception->getMessage());
            }
        } finally {
            Voucher::setEventDispatcher($dispatcher);
        }

        $this->assertCount(2, $observedFundIds);
        $this->assertNotNull($partialVoucherId);
        $this->assertDatabaseMissing('vouchers', ['id' => $partialVoucherId]);

        $completedIds = Voucher::where('fund_id', $observedFundIds[0])->orderBy('id')->pluck('id')->all();

        $this->assertCount(3, $completedIds);
        $this->assertFalse(Voucher::where('fund_id', $observedFundIds[1])->exists());

        $fund->fund_config->implementation->makeVouchersInApplicableFunds($identity);

        $this->assertSame(
            $completedIds,
            Voucher::where('fund_id', $observedFundIds[0])->orderBy('id')->pluck('id')->all(),
        );

        $this->assertEquals(
            [30, 25, 25],
            Voucher::where('fund_id', $observedFundIds[1])->orderBy('id')->pluck('amount')->all(),
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrdinaryApplyRejectsFuturePausedFund(): void
    {
        ['fund' => $fund, 'identity' => $identity] = $this->makeOrdinaryAwardFixture();
        $fund->update(['state' => Fund::STATE_PAUSED, 'start_date' => Carbon::now()->addMonth()]);

        $this->apiApplyForFundRequest($fund, $identity)->assertForbidden();
        $this->assertFalse($fund->vouchers()->exists());
    }

    /**
     * @throws Throwable
     * @return array{fund: Fund, identity: Identity}
     */
    private function makeOrdinaryAwardFixture(): array
    {
        $this->travelTo('2026-11-13 12:00:00');

        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail())));
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $this->configureOrdinaryAwardFormulas($fund);
        $this->makeValidatedIdentityRecordForFund($identity, $fund, 'children_nth', '2');

        return compact('fund', 'identity');
    }

    /**
     * @param Fund $fund
     * @return void
     */
    private function configureOrdinaryAwardFormulas(Fund $fund): void
    {
        $fund->fund_formulas()->delete();

        $fund->fund_formulas()->createMany([
            ['type' => FundFormula::TYPE_FIXED, 'amount' => 30],
            ['type' => FundFormula::TYPE_MULTIPLY_VOUCHERS, 'amount' => 25, 'record_type_key' => 'children_nth'],
        ]);
    }
}
