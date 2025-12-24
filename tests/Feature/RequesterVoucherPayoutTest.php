<?php

namespace Tests\Feature;

use App\Models\VoucherTransaction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\MakesApiRequests;
use Tests\Traits\MakesRequesterVoucherPayouts;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class RequesterVoucherPayoutTest extends TestCase
{
    use DatabaseTransactions;
    use MakesApiRequests;
    use MakesRequesterVoucherPayouts;
    use MakesTestFunds;
    use MakesTestOrganizations;

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterCanCreatePayoutFromVoucher(): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());

        $fund = $this->makePayoutEnabledFund($sponsorOrganization);

        $iban = $this->faker()->iban('NL');
        $ibanName = $this->makeIbanName();

        $result = $this->makePayoutVoucherViaApplication($requester, $fund, $iban, $ibanName);
        $voucher = $result['voucher'];

        $voucher->loadMissing([
            'fund.fund_config',
            'fund_request.records',
        ]);

        $this->assertEquals($requester->id, $voucher->identity_id);
        $this->assertFalse($voucher->expired);
        $this->assertFalse($voucher->deactivated);
        $this->assertFalse($voucher->external);
        $this->assertEmpty($voucher->product_id);
        $this->assertEmpty($voucher->product_reservation_id);
        $this->assertNotEmpty($voucher->fund_request?->getIban(false));
        $this->assertNotEmpty($voucher->fund_request?->getIbanName(false));
        $this->assertTrue((bool) $voucher->fund?->fund_config?->allow_voucher_payouts);

        $res = $this->postJson('/api/v1/platform/payouts', [
            'voucher_id' => $voucher->id,
            'amount' => '50.00',
        ], $this->makeApiHeaders($requester));

        $res->assertSuccessful();

        $transaction = VoucherTransaction::find($res->json('data.id'));
        $this->assertNotNull($transaction);

        $this->assertEquals(VoucherTransaction::TARGET_PAYOUT, $transaction->target);
        $this->assertEquals(VoucherTransaction::INITIATOR_REQUESTER, $transaction->initiator);
        $this->assertEquals($voucher->id, $transaction->voucher_id);
        $this->assertEquals($iban, $transaction->target_iban);
        $this->assertEquals($ibanName, $transaction->target_name);
        $this->assertEquals(50.00, (float) $transaction->amount);

        $listRes = $this->getJson('/api/v1/platform/payouts', $this->makeApiHeaders($requester));
        $listRes->assertSuccessful();
        $listRes->assertJsonFragment([
            'id' => $transaction->id,
            'iban_to' => $iban,
        ]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterCannotCreatePayoutWhenFundDisallowsVoucherPayouts(): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());

        $fund = $this->makePayoutEnabledFund($sponsorOrganization);
        $fund->fund_config->forceFill([
            'allow_voucher_payouts' => false,
        ])->save();

        $iban = $this->faker()->iban('NL');
        $ibanName = $this->makeIbanName();

        $result = $this->makePayoutVoucherViaApplication($requester, $fund, $iban, $ibanName);
        $voucher = $result['voucher'];

        $res = $this->postJson('/api/v1/platform/payouts', [
            'voucher_id' => $voucher->id,
            'amount' => '50.00',
        ], $this->makeApiHeaders($requester));

        $res->assertForbidden();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterCannotCreatePayoutWithoutIbanOrName(): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());

        $fund = $this->makePayoutEnabledFund($sponsorOrganization);

        $iban = $this->faker()->iban('NL');
        $ibanName = $this->makeIbanName();

        $result = $this->makePayoutVoucherViaApplication($requester, $fund, $iban, $ibanName);
        $fundRequest = $result['fund_request'];
        $voucher = $result['voucher'];

        $fundRequest->records()->where('record_type_key', $fund->fund_config->iban_record_key)->update([
            'value' => null,
        ]);

        $res = $this->postJson('/api/v1/platform/payouts', [
            'voucher_id' => $voucher->id,
            'amount' => '50.00',
        ], $this->makeApiHeaders($requester));

        $res->assertForbidden();

        $fundRequest->records()->where('record_type_key', $fund->fund_config->iban_record_key)->update([
            'value' => $iban,
        ]);

        $fundRequest->records()->where('record_type_key', $fund->fund_config->iban_name_record_key)->update([
            'value' => null,
        ]);

        $res = $this->postJson('/api/v1/platform/payouts', [
            'voucher_id' => $voucher->id,
            'amount' => '50.00',
        ], $this->makeApiHeaders($requester));

        $res->assertForbidden();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutAmountValidation(): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());

        $fund = $this->makePayoutEnabledFund($sponsorOrganization);

        $iban = $this->faker()->iban('NL');
        $ibanName = $this->makeIbanName();

        $result = $this->makePayoutVoucherViaApplication($requester, $fund, $iban, $ibanName);
        $voucher = $result['voucher'];

        $res = $this->postJson('/api/v1/platform/payouts', [
            'voucher_id' => $voucher->id,
            'amount' => 0.05,
        ], $this->makeApiHeaders($requester));

        $res->assertJsonValidationErrorFor('amount');

        $res = $this->postJson('/api/v1/platform/payouts', [
            'voucher_id' => $voucher->id,
            'amount' => 1000,
        ], $this->makeApiHeaders($requester));

        $res->assertJsonValidationErrorFor('amount');
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutFixedAmountValidation(): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());

        $fund = $this->makePayoutEnabledFund($sponsorOrganization);
        $fund->fund_config->forceFill([
            'allow_voucher_payout_amount' => '25.00',
        ])->save();

        $iban = $this->faker()->iban('NL');
        $ibanName = $this->makeIbanName();

        $fund->fund_formulas()->update(['amount' => 100]);

        $result = $this->makePayoutVoucherViaApplication($requester, $fund, $iban, $ibanName);
        $voucher = $result['voucher'];

        $res = $this->postJson('/api/v1/platform/payouts', [
            'voucher_id' => $voucher->id,
            'amount' => '20.00',
        ], $this->makeApiHeaders($requester));

        $res->assertJsonValidationErrorFor('amount');

        $res = $this->postJson('/api/v1/platform/payouts', [
            'voucher_id' => $voucher->id,
            'amount' => '25.00',
        ], $this->makeApiHeaders($requester));

        $res->assertSuccessful();

        $transaction = VoucherTransaction::find($res->json('data.id'));
        $this->assertNotNull($transaction);
        $this->assertEquals(25.00, (float) $transaction->amount);

        $fund->fund_formulas()->update(['amount' => 10]);

        $lowBalanceIdentity = $this->makeIdentity($this->makeUniqueEmail());
        $lowBalanceIban = $this->faker()->iban('NL');
        $lowBalanceIbanName = $this->makeIbanName();

        $resultLowBalance = $this->makePayoutVoucherViaApplication(
            $lowBalanceIdentity,
            $fund,
            $lowBalanceIban,
            $lowBalanceIbanName,
        );
        $voucherLowBalance = $resultLowBalance['voucher'];

        $res = $this->postJson('/api/v1/platform/payouts', [
            'voucher_id' => $voucherLowBalance->id,
            'amount' => '25.00',
        ], $this->makeApiHeaders($lowBalanceIdentity));

        $res->assertJsonValidationErrorFor('amount');
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutCountLimit(): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());

        $fund = $this->makePayoutEnabledFund($sponsorOrganization);
        $fund->fund_config->forceFill([
            'allow_voucher_payout_count' => 1,
        ])->save();

        $iban = $this->faker()->iban('NL');
        $ibanName = $this->makeIbanName();

        $result = $this->makePayoutVoucherViaApplication($requester, $fund, $iban, $ibanName);
        $voucher = $result['voucher'];

        $res = $this->postJson('/api/v1/platform/payouts', [
            'voucher_id' => $voucher->id,
            'amount' => '10.00',
        ], $this->makeApiHeaders($requester));

        $res->assertSuccessful();

        $res = $this->postJson('/api/v1/platform/payouts', [
            'voucher_id' => $voucher->id,
            'amount' => '10.00',
        ], $this->makeApiHeaders($requester));

        $res->assertJsonValidationErrorFor('amount');
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutCountIgnoresNonRequesterPayouts(): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());

        $fund = $this->makePayoutEnabledFund($sponsorOrganization);
        $fund->fund_config->forceFill([
            'allow_voucher_payout_count' => 1,
        ])->save();

        $iban = $this->faker()->iban('NL');
        $ibanName = $this->makeIbanName();

        $result = $this->makePayoutVoucherViaApplication($requester, $fund, $iban, $ibanName);
        $voucher = $result['voucher'];

        $voucher->makeTransaction([
            'initiator' => VoucherTransaction::INITIATOR_SPONSOR,
            'target' => VoucherTransaction::TARGET_PAYOUT,
            'target_iban' => $iban,
            'target_name' => $ibanName,
            'amount' => '10.00',
        ]);

        $res = $this->postJson('/api/v1/platform/payouts', [
            'voucher_id' => $voucher->id,
            'amount' => '10.00',
        ], $this->makeApiHeaders($requester));

        $res->assertSuccessful();

        $res = $this->postJson('/api/v1/platform/payouts', [
            'voucher_id' => $voucher->id,
            'amount' => '10.00',
        ], $this->makeApiHeaders($requester));

        $res->assertJsonValidationErrorFor('amount');
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutCountZeroDisallowsAll(): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());

        $fund = $this->makePayoutEnabledFund($sponsorOrganization);
        $fund->fund_config->forceFill([
            'allow_voucher_payout_count' => 0,
        ])->save();

        $iban = $this->faker()->iban('NL');
        $ibanName = $this->makeIbanName();

        $result = $this->makePayoutVoucherViaApplication($requester, $fund, $iban, $ibanName);
        $voucher = $result['voucher'];

        $res = $this->postJson('/api/v1/platform/payouts', [
            'voucher_id' => $voucher->id,
            'amount' => '10.00',
        ], $this->makeApiHeaders($requester));

        $res->assertJsonValidationErrorFor('amount');
    }

}
