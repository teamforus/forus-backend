<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\FundPayoutFormula;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
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
        [$requester, $fund] = $this->makeRequesterWithPayoutFund();

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
        [$requester, $fund] = $this->makeRequesterWithPayoutFund();
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
        [$requester, $fund] = $this->makeRequesterWithPayoutFund();
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
        [$requester, $fund] = $this->makeRequesterWithPayoutFund();

        $result = $this->makePayoutVoucherViaApplication($requester, $fund);
        $fundRequest = $result['fund_request'];
        $voucher = $result['voucher'];

        $fund->organization->forceFill(['allow_profiles' => false])->save();

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
        [$requester, $fund] = $this->makeRequesterWithPayoutFund();
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
    public function testRequesterPayoutFixedFormulaValidation(): void
    {
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());
        $sponsorOrganization->forceFill(['allow_profiles' => true])->save();

        $fund = $this->makePayoutEnabledFund($sponsorOrganization);
        $this->runPayoutFormulaScenario(
            $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn()),
            $fund,
            [
                ['type' => FundPayoutFormula::TYPE_FIXED, 'amount' => 25],
            ],
            [],
        );

        $this->runPayoutFormulaScenario(
            $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn()),
            $fund,
            [
                ['type' => FundPayoutFormula::TYPE_FIXED, 'amount' => 20],
                ['type' => FundPayoutFormula::TYPE_FIXED, 'amount' => 80],
            ],
            [],
        );

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
    public function testRequesterPayoutMultiplyFormulaValidation(): void
    {
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());
        $sponsorOrganization->forceFill(['allow_profiles' => true])->save();

        $fund = $this->makePayoutEnabledFund($sponsorOrganization);

        $keyA = 'payout_income_' . token_generator()->generate(6);
        $keyB = 'payout_bonus_' . token_generator()->generate(6);

        $this->runPayoutFormulaScenario(
            $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn()),
            $fund,
            [
                ['type' => FundPayoutFormula::TYPE_MULTIPLY, 'amount' => 10, 'record_type_key' => $keyA],
            ],
            [$keyA => 3],
        );

        $this->runPayoutFormulaScenario(
            $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn()),
            $fund,
            [
                ['type' => FundPayoutFormula::TYPE_MULTIPLY, 'amount' => 5, 'record_type_key' => $keyA],
                ['type' => FundPayoutFormula::TYPE_MULTIPLY, 'amount' => 7, 'record_type_key' => $keyB],
            ],
            [$keyA => 4, $keyB => 2],
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutPartialAmountsAreCalculated(): void
    {
        [$requester, $fund] = $this->makeRequesterWithPartialPayoutFund();

        $recordKey = 'payout_partial_' . token_generator()->generate(6);
        $this->ensureNumberRecordType($fund->organization, $recordKey);

        $fund->fund_payout_formulas()->create([
            'type' => FundPayoutFormula::TYPE_MULTIPLY,
            'amount' => 50,
            'record_type_key' => $recordKey,
        ]);

        $result = $this->makePayoutVoucherViaApplication($requester, $fund);
        $voucher = $result['voucher'];
        $fundRequest = $result['fund_request'];

        $this->createTrustedRecord($requester, $fund, $fundRequest, $recordKey, 3);

        $voucherRes = $this->getVoucherShowResponse($requester, $voucher);
        $voucherRes->assertSuccessful();
        $voucherRes->assertJsonPath('data.voucher_payout_partial_amounts_label_type', null);
        $voucherRes->assertJsonPath('data.voucher_payout_partial_amounts', [
            '50.00',
            '100.00',
            '150.00',
        ]);

        $this->apiMakePayout([
            'voucher_id' => $voucher->id,
            'amount' => '100.00',
            'fund_request_id' => $fundRequest->id,
        ], $requester);

        $voucherRes = $this->getVoucherShowResponse($requester, $voucher);
        $voucherRes->assertSuccessful();
        $voucherRes->assertJsonPath('data.voucher_payout_partial_amounts_label_type', null);
        $voucherRes->assertJsonPath('data.voucher_payout_partial_amounts', [
            '50.00',
        ]);

        $this->apiMakePayoutRequest([
            'voucher_id' => $voucher->id,
            'amount' => '150.00',
            'fund_request_id' => $fundRequest->id,
        ], $requester)->assertJsonValidationErrorFor('amount');
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutPartialAmountsLabelTypeIsPersonsForPartnersSameAddress(): void
    {
        [$requester, $fund] = $this->makeRequesterWithPartialPayoutFund();

        $this->ensureNumberRecordType($fund->organization, Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS);

        $fund->fund_payout_formulas()->create([
            'type' => FundPayoutFormula::TYPE_MULTIPLY,
            'amount' => 50,
            'record_type_key' => Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS,
        ]);

        $result = $this->makePayoutVoucherViaApplication($requester, $fund);
        $voucher = $result['voucher'];
        $fundRequest = $result['fund_request'];

        $this->createTrustedRecord($requester, $fund, $fundRequest, Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS, 2);

        $voucherRes = $this->getVoucherShowResponse($requester, $voucher);
        $voucherRes->assertSuccessful();
        $voucherRes->assertJsonPath('data.voucher_payout_partial_amounts', [
            '50.00',
            '100.00',
        ]);
        $voucherRes->assertJsonPath('data.voucher_payout_partial_amounts_label_type', Voucher::PAYOUT_PARTIAL_AMOUNTS_LABEL_TYPE_PERSONS);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutPartialAmountsLabelTypeIsNullWhenPartialDoesNotApply(): void
    {
        [$requester, $fund] = $this->makeRequesterWithPayoutFund();
        $result = $this->makePayoutVoucherViaApplication($requester, $fund);
        $voucher = $result['voucher'];

        $voucherRes = $this->getVoucherShowResponse($requester, $voucher);
        $voucherRes->assertSuccessful();
        $voucherRes->assertJsonPath('data.voucher_payout_partial_amounts', null);
        $voucherRes->assertJsonPath('data.voucher_payout_partial_amounts_label_type', null);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutPartialAmountCapApplied(): void
    {
        [$requester, $fund] = $this->makeRequesterWithPartialPayoutFund();

        $recordKey = 'payout_partial_' . token_generator()->generate(6);
        $this->ensureNumberRecordType($fund->organization, $recordKey);

        $fund->fund_payout_formulas()->create([
            'type' => FundPayoutFormula::TYPE_MULTIPLY,
            'amount' => 50,
            'max_amount' => 200,
            'record_type_key' => $recordKey,
        ]);

        $result = $this->makePayoutVoucherViaApplication($requester, $fund);
        $voucher = $result['voucher'];
        $fundRequest = $result['fund_request'];

        $this->createTrustedRecord($requester, $fund, $fundRequest, $recordKey, 10);

        $voucherRes = $this->getVoucherShowResponse($requester, $voucher);
        $voucherRes->assertSuccessful();
        $voucherRes->assertJsonPath('data.voucher_payout_partial_amounts_label_type', null);
        $voucherRes->assertJsonPath('data.voucher_payout_partial_amounts', [
            '50.00',
            '100.00',
            '150.00',
            '200.00',
        ]);

        $this->apiMakePayoutRequest([
            'voucher_id' => $voucher->id,
            'amount' => '250.00',
            'fund_request_id' => $fundRequest->id,
        ], $requester)->assertJsonValidationErrorFor('amount');
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutPartialAmountDefaultCapApplied(): void
    {
        [$requester, $fund] = $this->makeRequesterWithPartialPayoutFund();

        $recordKey = 'payout_partial_' . token_generator()->generate(6);
        $this->ensureNumberRecordType($fund->organization, $recordKey);

        $fund->fund_payout_formulas()->create([
            'type' => FundPayoutFormula::TYPE_MULTIPLY,
            'amount' => 1000,
            'record_type_key' => $recordKey,
        ]);

        $result = $this->makePayoutVoucherViaApplication($requester, $fund);
        $voucher = $result['voucher'];
        $fundRequest = $result['fund_request'];

        $this->createTrustedRecord($requester, $fund, $fundRequest, $recordKey, 100);
        $voucher->forceFill(['amount' => 10000])->save();

        $voucherRes = $this->getVoucherShowResponse($requester, $voucher);
        $voucherRes->assertSuccessful();
        $voucherRes->assertJsonCount(5, 'data.voucher_payout_partial_amounts');
        $voucherRes->assertJsonPath('data.voucher_payout_partial_amounts.4', '5000.00');
        $voucherRes->assertJsonPath('data.voucher_payout_partial_amounts_label_type', null);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutPartialAmountZeroMaxReturnsEmpty(): void
    {
        [$requester, $fund] = $this->makeRequesterWithPartialPayoutFund();

        $recordKey = 'payout_partial_' . token_generator()->generate(6);
        $this->ensureNumberRecordType($fund->organization, $recordKey);

        $fund->fund_payout_formulas()->create([
            'type' => FundPayoutFormula::TYPE_MULTIPLY,
            'amount' => 50,
            'max_amount' => 0,
            'record_type_key' => $recordKey,
        ]);

        $result = $this->makePayoutVoucherViaApplication($requester, $fund);
        $voucher = $result['voucher'];
        $fundRequest = $result['fund_request'];

        $this->createTrustedRecord($requester, $fund, $fundRequest, $recordKey, 3);

        $voucherRes = $this->getVoucherShowResponse($requester, $voucher);
        $voucherRes->assertSuccessful();
        $voucherRes->assertJsonPath('data.voucher_payout_partial_amounts', []);
        $voucherRes->assertJsonPath('data.voucher_payout_partial_amounts_label_type', null);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutPartialAmountCappedByVoucherBalance(): void
    {
        [$requester, $fund] = $this->makeRequesterWithPartialPayoutFund();

        $recordKey = 'payout_partial_' . token_generator()->generate(6);
        $this->ensureNumberRecordType($fund->organization, $recordKey);

        $fund->fund_payout_formulas()->create([
            'type' => FundPayoutFormula::TYPE_MULTIPLY,
            'amount' => 50,
            'record_type_key' => $recordKey,
        ]);

        $result = $this->makePayoutVoucherViaApplication($requester, $fund);
        $voucher = $result['voucher'];
        $fundRequest = $result['fund_request'];

        $this->createTrustedRecord($requester, $fund, $fundRequest, $recordKey, 3);
        $voucher->forceFill(['amount' => 120])->save();

        $voucherRes = $this->getVoucherShowResponse($requester, $voucher);
        $voucherRes->assertSuccessful();
        $voucherRes->assertJsonPath('data.voucher_payout_partial_amounts_label_type', null);

        $voucherRes->assertJsonPath('data.voucher_payout_partial_amounts', [
            '50.00',
            '100.00',
        ]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutPartialAmountsLabelTypeIsNullForChildrenSameAddress(): void
    {
        [$requester, $fund] = $this->makeRequesterWithPartialPayoutFund();

        $this->ensureNumberRecordType($fund->organization, Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS);

        $fund->fund_payout_formulas()->create([
            'type' => FundPayoutFormula::TYPE_MULTIPLY,
            'amount' => 50,
            'record_type_key' => Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
        ]);

        $result = $this->makePayoutVoucherViaApplication($requester, $fund);
        $voucher = $result['voucher'];
        $fundRequest = $result['fund_request'];

        $this->createTrustedRecord($requester, $fund, $fundRequest, Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS, 2);

        $voucherRes = $this->getVoucherShowResponse($requester, $voucher);
        $voucherRes->assertSuccessful();
        $voucherRes->assertJsonPath('data.voucher_payout_partial_amounts', [
            '50.00',
            '100.00',
        ]);
        $voucherRes->assertJsonPath('data.voucher_payout_partial_amounts_label_type', null);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutPartialAmountsLabelTypeIsNullForUnsupportedPartialFormulaShape(): void
    {
        [$requester, $fund] = $this->makeRequesterWithPartialPayoutFund();

        $fund->fund_payout_formulas()->create([
            'type' => FundPayoutFormula::TYPE_FIXED,
            'amount' => 50,
        ]);

        $result = $this->makePayoutVoucherViaApplication($requester, $fund);
        $voucher = $result['voucher'];

        $voucherRes = $this->getVoucherShowResponse($requester, $voucher);
        $voucherRes->assertSuccessful();
        $voucherRes->assertJsonPath('data.voucher_payout_partial_amounts', null);
        $voucherRes->assertJsonPath('data.voucher_payout_partial_amounts_label_type', null);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterPayoutMixedFormulaValidation(): void
    {
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());
        $sponsorOrganization->forceFill(['allow_profiles' => true])->save();

        $fund = $this->makePayoutEnabledFund($sponsorOrganization);

        $keyX = 'payout_multiplier_' . token_generator()->generate(6);
        $keyY = 'payout_multiplier_' . token_generator()->generate(6);

        $this->runPayoutFormulaScenario(
            $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn()),
            $fund,
            [
                ['type' => FundPayoutFormula::TYPE_FIXED, 'amount' => 15],
                ['type' => FundPayoutFormula::TYPE_MULTIPLY, 'amount' => 3, 'record_type_key' => $keyX],
            ],
            [$keyX => 5],
        );

        $this->runPayoutFormulaScenario(
            $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn()),
            $fund,
            [
                ['type' => FundPayoutFormula::TYPE_FIXED, 'amount' => 20],
                ['type' => FundPayoutFormula::TYPE_FIXED, 'amount' => 80],
                ['type' => FundPayoutFormula::TYPE_MULTIPLY, 'amount' => 2, 'record_type_key' => $keyX],
                ['type' => FundPayoutFormula::TYPE_MULTIPLY, 'amount' => 4, 'record_type_key' => $keyY],
            ],
            [$keyX => 3, $keyY => 2],
        );
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
        [$requester, $fund] = $this->makeRequesterWithPayoutFund();
        $voucher = $this->makeTestVoucher($fund, $requester, amount: 100);

        $this->assertNull($voucher->fund_request_id);

        $this->apiMakePayoutRequest([
            'voucher_id' => $voucher->id,
            'amount' => '50.00',
            'fund_request_id' => 0,
        ], $requester)->assertForbidden();
    }

    /**
     * @throws Throwable
     * @return array{Identity, Fund}
     */
    protected function makeRequesterWithPayoutFund(): array
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());
        $sponsorOrganization->forceFill(['allow_profiles' => true])->save();

        return [$requester, $this->makePayoutEnabledFund($sponsorOrganization)];
    }

    /**
     * @throws Throwable
     * @return array{Identity, Fund}
     */
    protected function makeRequesterWithPartialPayoutFund(): array
    {
        [$requester, $fund] = $this->makeRequesterWithPayoutFund();

        $fund->fund_config->forceFill([
            'allow_voucher_payouts_partial' => true,
        ])->save();

        return [$requester, $fund->refresh()];
    }

    /**
     * @param Identity $requester
     * @param Voucher $voucher
     * @return TestResponse
     */
    protected function getVoucherShowResponse(Identity $requester, Voucher $voucher): TestResponse
    {
        return $this->getJson("/api/v1/platform/vouchers/$voucher->number", $this->makeApiHeaders($requester));
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param array $formulas
     * @param array $recordValues
     * @return void
     */
    private function runPayoutFormulaScenario(
        Identity $identity,
        Fund $fund,
        array $formulas,
        array $recordValues,
    ): void {
        $fund->fund_payout_formulas()->delete();

        foreach (array_unique(array_filter(array_column($formulas, 'record_type_key'))) as $recordTypeKey) {
            $this->ensureNumberRecordType($fund->organization, $recordTypeKey);
        }

        foreach ($formulas as $formula) {
            $fund->fund_payout_formulas()->create($formula);
        }

        $result = $this->makePayoutVoucherViaApplication($identity, $fund);
        $fundRequest = $result['fund_request'];
        $voucher = $result['voucher'];

        foreach ($recordValues as $key => $value) {
            $this->createTrustedRecord($identity, $fund, $fundRequest, $key, $value);
        }

        $expectedAmount = $this->calculateFormulaTotal($formulas, $recordValues);
        $invalidAmount = max(0.01, $expectedAmount - 0.01);

        if (abs($invalidAmount - $expectedAmount) < 0.0001) {
            $invalidAmount = max(0.01, $invalidAmount - 0.01);
        }

        $res = $this->apiMakePayoutRequest([
            'voucher_id' => $voucher->id,
            'amount' => number_format($invalidAmount, 2, '.', ''),
            'fund_request_id' => $fundRequest->id,
        ], $identity);

        $res->assertJsonValidationErrorFor('amount');

        $transaction = $this->apiMakePayout([
            'voucher_id' => $voucher->id,
            'amount' => number_format($expectedAmount, 2, '.', ''),
            'fund_request_id' => $fundRequest->id,
        ], $identity);

        $this->assertEquals($expectedAmount, (float) $transaction->amount);
    }

    /**
     * @param array $formulas
     * @param array $recordValues
     * @return float
     */
    private function calculateFormulaTotal(array $formulas, array $recordValues): float
    {
        $total = 0.0;

        foreach ($formulas as $formula) {
            if ($formula['type'] === FundPayoutFormula::TYPE_FIXED) {
                $total += (float) $formula['amount'];
                continue;
            }

            if ($formula['type'] === FundPayoutFormula::TYPE_MULTIPLY) {
                $key = $formula['record_type_key'] ?? null;

                if (!$key) {
                    continue;
                }

                $value = isset($recordValues[$key]) ? (float) $recordValues[$key] : 0.0;
                $amount = (float) $formula['amount'] * $value;

                if (isset($formula['max_amount'])) {
                    $amount = min($amount, (float) $formula['max_amount']);
                }

                $total += $amount;
            }
        }

        return $total;
    }
}
