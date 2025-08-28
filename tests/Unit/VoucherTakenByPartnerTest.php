<?php

namespace Tests\Unit;

use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Throwable;

class VoucherTakenByPartnerTest extends TestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use CreatesApplication;
    use DatabaseTransactions;

    /**
     * Test the logic of determining if a voucher has been taken
     * by a partner, covering both directions of the bsn ↔ partner_bsn link.
     *
     * Scenario 1:
     * - Identity1 gets a voucher and adds a validated partner_bsn record.
     * - Assert that Identity2 (with no bsn) is not marked as taken by a partner.
     * - When Identity2 sets its own bsn matching Identity1's partner_bsn,
     *   assert that the voucher is recognized as taken by a partner.
     *
     * Scenario 2:
     * - Identity1 gets a voucher and sets its own bsn record.
     * - Assert that Identity2 (with no partner_bsn) is not marked as taken by a partner.
     * - When Identity2 adds a partner_bsn record matching Identity1’s bsn
     *   and gets it validated, assert that the voucher is recognized as taken.
     *
     * @return void
     * @throws Throwable
     */
    public function testVoucherTakenByPartner(): void
    {
        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));
        $identity1 = $this->makeIdentity();
        $identity2 = $this->makeIdentity();

        DB::transaction(function () use ($fund, $identity1, $identity2) {
            $fund->makeVoucher($identity1, ['amount' => 100]);

            $identity1->addRecords(['partner_bsn' => 123456789])[0]
                ->makeValidationRequest()
                ->approve($fund->organization->identity);

            $this->assertFalse($fund->isTakenByPartner($identity2), 'Voucher is not taken by partner');
            $identity2->setBsnRecord(123456789);
            $this->assertTrue($fund->isTakenByPartner($identity2), 'Voucher is taken by partner');
            DB::rollBack();
        });

        DB::transaction(function () use ($fund, $identity1, $identity2) {
            $fund->makeVoucher($identity1, ['amount' => 100]);
            $identity1->setBsnRecord(123456789);
            $this->assertFalse($fund->isTakenByPartner($identity2), 'Voucher is not taken by partner');

            $identity2->addRecords(['partner_bsn' => 123456789])[0]
                ->makeValidationRequest()
                ->approve($fund->organization->identity);

            $this->assertTrue($fund->isTakenByPartner($identity2), 'Voucher is taken by partner');
            DB::rollBack();
        });
    }
}
