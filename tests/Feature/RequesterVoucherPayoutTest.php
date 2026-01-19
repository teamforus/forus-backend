<?php

namespace Tests\Feature;

use App\Models\FundRequest;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\MakesApiRequests;
use Tests\Traits\MakesRequesterVoucherPayouts;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class RequesterVoucherPayoutTest extends TestCase
{
    use DatabaseTransactions;
    use MakesApiRequests;
    use MakesRequesterVoucherPayouts;
    use MakesTestFunds;
    use MakesTestFundRequests;
    use MakesTestOrganizations;
    use MakesTestVouchers;

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterCanCreatePayoutFromVoucher(): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());
        $sponsorOrganization->forceFill(['allow_profiles' => true])->save();

        $fund = $this->makePayoutEnabledFund($sponsorOrganization);

        $result = $this->makePayoutVoucherViaApplication($requester, $fund);
        $voucher = $result['voucher'];
        $fundRequest = $result['fund_request'];
        $iban = $result['iban'];
        $ibanName = $result['iban_name'];

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
        $this->assertEquals($iban, $voucher->fund_request?->getIban(false));
        $this->assertEquals($ibanName, $voucher->fund_request?->getIbanName(false));
        $this->assertTrue((bool) $voucher->fund?->fund_config?->allow_voucher_payouts);

        $transaction = $this->apiMakePayout([
            'voucher_id' => $voucher->id,
            'amount' => '50.00',
            'fund_request_id' => $fundRequest->id,
        ], $requester);

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
    public function testProfileIncludesFundRequestBankAccount(): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());
        $sponsorOrganization->forceFill(['allow_profiles' => true])->save();

        $fund = $this->makePayoutEnabledFund($sponsorOrganization);
        $result = $this->makePayoutVoucherViaApplication($requester, $fund);
        $fundRequest = $result['fund_request'];
        $iban = $result['iban'];
        $ibanName = $result['iban_name'];

        $profileRes = $this->getJson('/api/v1/platform/profile', $this->makeApiHeaders(
            $requester,
            ['Client-Key' => $fund->getImplementation()->key],
        ));

        $profileRes->assertSuccessful();
        $profileRes->assertJsonFragment([
            'type' => 'fund_request',
            'type_id' => $fundRequest->id,
            'iban' => $iban,
            'name' => $ibanName,
        ]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterCannotCreatePayoutWhenFundDisallowsVoucherPayouts(): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());
        $sponsorOrganization->forceFill(['allow_profiles' => true])->save();

        $fund = $this->makePayoutEnabledFund($sponsorOrganization);
        $fund->fund_config->forceFill([
            'allow_voucher_payouts' => false,
        ])->save();

        $result = $this->makePayoutVoucherViaApplication($requester, $fund);
        $voucher = $result['voucher'];
        $fundRequest = $result['fund_request'];

        $res = $this->apiMakePayoutRequest([
            'voucher_id' => $voucher->id,
            'amount' => '50.00',
            'fund_request_id' => $fundRequest->id,
        ], $requester);

        $res->assertForbidden();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterCannotCreatePayoutWhenProfilesDisabled(): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());
        $sponsorOrganization->forceFill(['allow_profiles' => true])->save();

        $fund = $this->makePayoutEnabledFund($sponsorOrganization);

        $result = $this->makePayoutVoucherViaApplication($requester, $fund);
        $fundRequest = $result['fund_request'];
        $voucher = $result['voucher'];

        $sponsorOrganization->forceFill(['allow_profiles' => false])->save();

        $res = $this->apiMakePayoutRequest([
            'voucher_id' => $voucher->id,
            'amount' => '50.00',
            'fund_request_id' => $fundRequest->id,
        ], $requester);

        $res->assertForbidden();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterCannotCreatePayoutWithoutIbanOrName(): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());
        $sponsorOrganization->forceFill(['allow_profiles' => true])->save();

        $fund = $this->makePayoutEnabledFund($sponsorOrganization);
        $result = $this->makePayoutVoucherViaApplication($requester, $fund);
        $fundRequest = $result['fund_request'];
        $voucher = $result['voucher'];
        $iban = $result['iban'];

        $fundRequest->records()->where('record_type_key', $fund->fund_config->iban_record_key)->update([
            'value' => null,
        ]);

        $res = $this->apiMakePayoutRequest([
            'voucher_id' => $voucher->id,
            'amount' => '50.00',
            'fund_request_id' => $fundRequest->id,
        ], $requester);

        $res->assertJsonValidationErrorFor('fund_request_id');

        $fundRequest->records()->where('record_type_key', $fund->fund_config->iban_record_key)->update([
            'value' => $iban,
        ]);

        $fundRequest->records()->where('record_type_key', $fund->fund_config->iban_name_record_key)->update([
            'value' => null,
        ]);

        $res = $this->apiMakePayoutRequest([
            'voucher_id' => $voucher->id,
            'amount' => '50.00',
            'fund_request_id' => $fundRequest->id,
        ], $requester);

        $res->assertJsonValidationErrorFor('fund_request_id');
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutAmountValidation(): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());
        $sponsorOrganization->forceFill(['allow_profiles' => true])->save();

        $fund = $this->makePayoutEnabledFund($sponsorOrganization);

        $result = $this->makePayoutVoucherViaApplication($requester, $fund);
        $voucher = $result['voucher'];
        $fundRequest = $result['fund_request'];

        $res = $this->apiMakePayoutRequest([
            'voucher_id' => $voucher->id,
            'amount' => 0.05,
            'fund_request_id' => $fundRequest->id,
        ], $requester);

        $res->assertJsonValidationErrorFor('amount');

        $res = $this->apiMakePayoutRequest([
            'voucher_id' => $voucher->id,
            'amount' => 1000,
            'fund_request_id' => $fundRequest->id,
        ], $requester);

        $res->assertJsonValidationErrorFor('amount');
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutFixedAmountValidation(): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());
        $sponsorOrganization->forceFill(['allow_profiles' => true])->save();

        $fund = $this->makePayoutEnabledFund($sponsorOrganization);
        $fund->fund_config->forceFill([
            'allow_voucher_payout_amount' => '25.00',
        ])->save();

        $fund->fund_formulas()->update(['amount' => 100]);

        $result = $this->makePayoutVoucherViaApplication($requester, $fund);
        $fundRequest = $result['fund_request'];
        $voucher = $result['voucher'];

        $res = $this->apiMakePayoutRequest([
            'voucher_id' => $voucher->id,
            'amount' => '20.00',
            'fund_request_id' => $fundRequest->id,
        ], $requester);

        $res->assertJsonValidationErrorFor('amount');

        $transaction = $this->apiMakePayout([
            'voucher_id' => $voucher->id,
            'amount' => '25.00',
            'fund_request_id' => $fundRequest->id,
        ], $requester);
        $this->assertEquals(25.00, (float) $transaction->amount);

        $fund->fund_formulas()->update(['amount' => 10]);

        $lowBalanceIdentity = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $resultLowBalance = $this->makePayoutVoucherViaApplication($lowBalanceIdentity, $fund);
        $fundRequestLowBalance = $resultLowBalance['fund_request'];
        $voucherLowBalance = $resultLowBalance['voucher'];

        $res = $this->apiMakePayoutRequest([
            'voucher_id' => $voucherLowBalance->id,
            'amount' => '25.00',
            'fund_request_id' => $fundRequestLowBalance->id,
        ], $lowBalanceIdentity);

        $res->assertJsonValidationErrorFor('amount');
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutCountLimit(): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());
        $sponsorOrganization->forceFill(['allow_profiles' => true])->save();

        $fund = $this->makePayoutEnabledFund($sponsorOrganization);
        $fund->fund_config->forceFill([
            'allow_voucher_payout_count' => 1,
        ])->save();

        $result = $this->makePayoutVoucherViaApplication($requester, $fund);
        $fundRequest = $result['fund_request'];
        $voucher = $result['voucher'];

        $res = $this->apiMakePayoutRequest([
            'voucher_id' => $voucher->id,
            'amount' => '10.00',
            'fund_request_id' => $fundRequest->id,
        ], $requester);

        $res->assertSuccessful();

        $res = $this->apiMakePayoutRequest([
            'voucher_id' => $voucher->id,
            'amount' => '10.00',
            'fund_request_id' => $fundRequest->id,
        ], $requester);

        $res->assertJsonValidationErrorFor('amount');
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutCountIgnoresNonRequesterPayouts(): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());
        $sponsorOrganization->forceFill(['allow_profiles' => true])->save();

        $fund = $this->makePayoutEnabledFund($sponsorOrganization);
        $fund->fund_config->forceFill([
            'allow_voucher_payout_count' => 1,
        ])->save();

        $result = $this->makePayoutVoucherViaApplication($requester, $fund);
        $fundRequest = $result['fund_request'];
        $voucher = $result['voucher'];

        $voucher->makeTransaction([
            'initiator' => VoucherTransaction::INITIATOR_SPONSOR,
            'target' => VoucherTransaction::TARGET_PAYOUT,
            'target_iban' => $result['iban'],
            'target_name' => $result['iban_name'],
            'amount' => '10.00',
        ]);

        $res = $this->apiMakePayoutRequest([
            'voucher_id' => $voucher->id,
            'amount' => '10.00',
            'fund_request_id' => $fundRequest->id,
        ], $requester);

        $res->assertSuccessful();

        $res = $this->apiMakePayoutRequest([
            'voucher_id' => $voucher->id,
            'amount' => '10.00',
            'fund_request_id' => $fundRequest->id,
        ], $requester);

        $res->assertJsonValidationErrorFor('amount');
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutCountZeroDisallowsAll(): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());
        $sponsorOrganization->forceFill(['allow_profiles' => true])->save();

        $fund = $this->makePayoutEnabledFund($sponsorOrganization);
        $fund->fund_config->forceFill([
            'allow_voucher_payout_count' => 0,
        ])->save();

        $result = $this->makePayoutVoucherViaApplication($requester, $fund);
        $fundRequest = $result['fund_request'];
        $voucher = $result['voucher'];

        $res = $this->apiMakePayoutRequest([
            'voucher_id' => $voucher->id,
            'amount' => '10.00',
            'fund_request_id' => $fundRequest->id,
        ], $requester);

        $res->assertJsonValidationErrorFor('amount');
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutRequiresEligibleFundRequestId(): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());
        $sponsorOrganization->forceFill(['allow_profiles' => true])->save();

        $fund = $this->makePayoutEnabledFund($sponsorOrganization);
        $result = $this->makePayoutVoucherViaApplication($requester, $fund);
        $voucher = $result['voucher'];

        $this->apiMakePayoutRequest([
            'voucher_id' => $voucher->id,
            'amount' => '50.00',
        ], $requester)->assertJsonValidationErrorFor('fund_request_id');

        $sponsorOrganization2 = $this->makeTestOrganization($this->makeIdentity());
        $otherOrgFund = $this->makePayoutEnabledFund($sponsorOrganization2);
        $resultOtherOrg = $this->makePayoutVoucherViaApplication($requester, $otherOrgFund);

        $this->apiMakePayoutRequest([
            'voucher_id' => $voucher->id,
            'amount' => '50.00',
            'fund_request_id' => $resultOtherOrg['fund_request']->id,
        ], $requester)->assertJsonValidationErrorFor('fund_request_id');

        $fund2 = $this->makePayoutEnabledFund($sponsorOrganization);
        $fund2->loadMissing(['fund_config', 'criteria']);

        $pendingRequestRes = $this->makeFundRequest($requester, $fund2, [
            $this->makeRequestCriterionValue($fund2, $fund2->fund_config->iban_record_key, $this->makeIban()),
            $this->makeRequestCriterionValue($fund2, $fund2->fund_config->iban_name_record_key, $this->makeIbanName()),
        ], false);

        $pendingRequestRes->assertSuccessful();

        $pendingFundRequest = FundRequest::find($pendingRequestRes->json('data.id'));
        $this->assertNotNull($pendingFundRequest);

        $this->apiMakePayoutRequest([
            'voucher_id' => $voucher->id,
            'amount' => '50.00',
            'fund_request_id' => $pendingFundRequest->id,
        ], $requester)->assertJsonValidationErrorFor('fund_request_id');

        $fund3 = $this->makePayoutEnabledFund($sponsorOrganization);

        $resultExpired = $this->makePayoutVoucherViaApplication($requester, $fund3);
        $expiredVoucher = $resultExpired['voucher'];
        $expiredFundRequest = $resultExpired['fund_request'];

        $expiredVoucher->forceFill(['state' => Voucher::STATE_DEACTIVATED])->save();

        $this->apiMakePayoutRequest([
            'voucher_id' => $voucher->id,
            'amount' => '50.00',
            'fund_request_id' => $expiredFundRequest->id,
        ], $requester)->assertJsonValidationErrorFor('fund_request_id');
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutCanUseFundRequestFromAnotherFundSameSponsor(): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());
        $sponsorOrganization->forceFill(['allow_profiles' => true])->save();

        $fund1 = $this->makePayoutEnabledFund($sponsorOrganization);
        $fund2 = $this->makePayoutEnabledFund($sponsorOrganization);

        $result1 = $this->makePayoutVoucherViaApplication($requester, $fund1);
        $fundRequest1 = $result1['fund_request'];

        $result2 = $this->makePayoutVoucherViaApplication($requester, $fund2);
        $voucher2 = $result2['voucher'];

        $this->assertNotEquals($fund1->id, $fund2->id);
        $this->assertNotEquals($fundRequest1->id, $result2['fund_request']->id);

        $transaction = $this->apiMakePayout([
            'voucher_id' => $voucher2->id,
            'amount' => '50.00',
            'fund_request_id' => $fundRequest1->id,
        ], $requester);

        $this->assertNotNull($transaction);
        $this->assertEquals($voucher2->id, $transaction->voucher_id);
        $this->assertEquals($result1['iban'], $transaction->target_iban);
        $this->assertEquals($result1['iban_name'], $transaction->target_name);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutUsesSelectedFundRequestWhenVoucherHasNoFundRequest(): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());
        $sponsorOrganization->forceFill(['allow_profiles' => true])->save();

        $fund1 = $this->makePayoutEnabledFund($sponsorOrganization);
        $fund2 = $this->makePayoutEnabledFund($sponsorOrganization);

        $result1 = $this->makePayoutVoucherViaApplication($requester, $fund1);
        $fundRequest1 = $result1['fund_request'];

        $voucher2 = $this->makeTestVoucher($fund2, $requester, amount: 100);
        $this->assertNull($voucher2->fund_request_id);

        $transaction = $this->apiMakePayout([
            'voucher_id' => $voucher2->id,
            'amount' => '50.00',
            'fund_request_id' => $fundRequest1->id,
        ], $requester);
        $this->assertNotNull($transaction);
        $this->assertEquals($voucher2->id, $transaction->voucher_id);
        $this->assertEquals($result1['iban'], $transaction->target_iban);
        $this->assertEquals($result1['iban_name'], $transaction->target_name);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutRequiresEligibleFundRequest(): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());
        $sponsorOrganization->forceFill(['allow_profiles' => true])->save();

        $fund = $this->makePayoutEnabledFund($sponsorOrganization);
        $voucher = $this->makeTestVoucher($fund, $requester, amount: 100);

        $this->assertNull($voucher->fund_request_id);

        $this->apiMakePayoutRequest([
            'voucher_id' => $voucher->id,
            'amount' => '50.00',
            'fund_request_id' => 0,
        ], $requester)->assertForbidden();
    }
}
