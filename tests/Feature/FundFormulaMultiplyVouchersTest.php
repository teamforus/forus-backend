<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\FundFormula;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Voucher;
use App\Services\Forus\TestData\TestData;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class FundFormulaMultiplyVouchersTest extends TestCase
{
    use WithFaker;
    use MakesTestFunds;
    use MakesTestIdentities;
    use DatabaseTransactions;
    use MakesTestFundRequests;
    use MakesTestOrganizations;

    /**
     * Amount of each voucher created by the multiply_vouchers formula.
     */
    protected int $formulaAmount = 25;

    /**
     * Record used by the multiply_vouchers formula, test criteria/prevalidation sets it to 2.
     */
    protected string $recordTypeKey = 'test_select_number';
    protected int $recordValue = 2;

    /**
     * Amount of the main voucher, from the fixed formula created by makeTestFund.
     */
    protected int $mainVoucherAmount = 300;

    /**
     * @throws Throwable
     * @return void
     */
    public function testVouchersCreatedOnApplyWithPrevalidation(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail(), bsn: TestData::randomFakeBsn());
        $fund = $this->makeMultiplyVouchersFund();
        $startDate = Carbon::now();

        $this->makePrevalidationForTestCriteria($fund->organization, $fund)->assignToIdentity($identity);

        $this->apiApplyForFundRequest($fund, $identity)->assertSuccessful();

        $this->assertMultiplyVouchersCreated($fund, $identity, $startDate);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVouchersCreatedOnPrevalidationRedeem(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail(), bsn: TestData::randomFakeBsn());
        $fund = $this->makeMultiplyVouchersFund();
        $startDate = Carbon::now();

        $prevalidation = $this->makePrevalidationForTestCriteria($fund->organization, $fund);

        $response = $this->apiRedeemFundRequest($fund, [
            'code' => $prevalidation->uid,
        ], $identity);

        $response->assertSuccessful();
        $response->assertJsonCount(1 + $this->recordValue, 'vouchers');

        $this->assertMultiplyVouchersCreated($fund, $identity, $startDate);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVouchersCreatedOnFundCheckByBsn(): void
    {
        $bsn = TestData::randomFakeBsn();
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['bsn_enabled' => true]);
        $identity = $this->makeIdentity($this->makeUniqueEmail(), bsn: $bsn);
        $fund = $this->makeMultiplyVouchersFund($organization, ['csv_primary_key' => 'bsn']);
        $startDate = Carbon::now();

        $this->makePrevalidationForTestCriteria($organization, $fund, $bsn);

        $this->apiCheckFundRequest($fund, $identity)->assertSuccessful();

        $this->assertMultiplyVouchersCreated($fund, $identity, $startDate);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVouchersCreatedOnFundRequestApprove(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $fund = $this->makeMultiplyVouchersFund();
        $startDate = Carbon::now();

        $fundRequest = $this->makeAndApproveFundRequest($identity, $fund);

        $this->assertMultiplyVouchersCreated($fund, $identity, $startDate, ['fund_request_id' => $fundRequest->id]);
    }

    /**
     * Fund has only multiply_vouchers formula, main voucher amount is zero - only additional vouchers are created.
     *
     * @throws Throwable
     * @return void
     */
    public function testOnlyMultiplyVouchersCreatedOnFundRequestApproveWithoutMainAmount(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $fund = $this->makeMultiplyVouchersFund(withFixedFormula: false);
        $startDate = Carbon::now();

        $fundRequest = $this->makeAndApproveFundRequest($identity, $fund);

        $this->assertOnlyMultiplyVouchersCreated($fund, $identity, $startDate, $fundRequest);
    }

    /**
     * Sponsor resolve policy "apply_auto_available": vouchers for related funds are created on approve,
     * related fund main voucher amount is zero - only additional vouchers are created for it.
     *
     * @throws Throwable
     * @return void
     */
    public function testOnlyMultiplyVouchersCreatedForRelatedFundOnFundRequestApprove(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fund = $this->makeMultiplyVouchersFund($organization);
        $relatedFund = $this->makeMultiplyVouchersFund($organization, withFixedFormula: false, withCriteria: false);

        $organization->forceFill([
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_AVAILABLE,
        ])->save();

        $startDate = Carbon::now();

        $fundRequest = $this->makeAndApproveFundRequest($identity, $fund);

        $this->assertMultiplyVouchersCreated($fund, $identity, $startDate, ['fund_request_id' => $fundRequest->id]);
        $this->assertOnlyMultiplyVouchersCreated($relatedFund, $identity, $startDate, $fundRequest);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVouchersCreatedBySponsorForIdentity(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail(), bsn: TestData::randomFakeBsn());
        $fund = $this->makeMultiplyVouchersFund(fundConfigsData: $this->sponsorVoucherFundConfigs());
        $startDate = Carbon::now();
        $note = $this->faker->sentence();

        $this->makePrevalidationForTestCriteria($fund->organization, $fund)->assignToIdentity($identity);

        $this->apiMakeVoucherAsSponsorRequest($fund->organization, $fund, [
            'assign_by_type' => 'email',
            'email' => $identity->email,
            'amount' => $this->mainVoucherAmount,
            'activate' => true,
            'note' => $note,
        ], $fund->organization->identity)->assertSuccessful();

        $this->assertMultiplyVouchersCreated($fund, $identity, $startDate, ['note' => $note]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSponsorVoucherAggregateBudgetLimit(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $fund = $this->makeMultiplyVouchersFund(fundConfigsData: [
            ...$this->sponsorVoucherFundConfigs(),
            'generator_ignore_fund_budget' => false,
        ]);

        $organization = $fund->organization;
        $startDate = Carbon::now();
        $totalAmount = $this->mainVoucherAmount + $this->formulaAmount * $this->recordValue;

        $this->makePrevalidationForTestCriteria($organization, $fund)->assignToIdentity($identity);
        $this->setTestFundBudget($fund, $totalAmount - 1);

        $data = [
            'assign_by_type' => 'email',
            'email' => $identity->email,
            'amount' => $this->mainVoucherAmount,
            'activate' => true,
        ];

        $this->assertLessThanOrEqual($fund->getMaxAmountPerVoucher(), $this->mainVoucherAmount);

        foreach ([true, false] as $validate) {
            $this->apiMakeVoucherAsSponsorRequest($organization, $fund, $data, $organization->identity, $validate)
                ->assertStatus(422)
                ->assertOnlyJsonValidationErrors([
                    'amount' => __('validation.voucher_generator.budget_exceeded'),
                ]);

            $this->assertSame(0, $fund->vouchers()->count());
        }

        $this->setTestFundBudget($fund, $totalAmount);

        foreach ([true, false] as $validate) {
            $this->apiMakeVoucherAsSponsorRequest($organization, $fund, $data, $organization->identity, $validate)
                ->assertSuccessful();
        }

        $this->assertMultiplyVouchersCreated($fund, $identity, $startDate);
    }

    /**
     * Sponsor vouchers without email have no identity to read records from, no extra vouchers must be created.
     *
     * @throws Throwable
     * @return void
     */
    public function testNoVouchersCreatedBySponsorWithoutIdentity(): void
    {
        $fund = $this->makeMultiplyVouchersFund(fundConfigsData: $this->sponsorVoucherFundConfigs());
        $startDate = Carbon::now();

        $response = $this->apiMakeVoucherAsSponsorRequest($fund->organization, $fund, [
            'assign_by_type' => 'activation_code',
            'amount' => $this->mainVoucherAmount,
            'activate' => false,
            'activation_code' => true,
        ], $fund->organization->identity);

        $response->assertSuccessful();

        $this->assertEquals(1, $this->budgetVouchersQuery($fund, null, $startDate)->count());
        $this->assertEquals($response->json('data.id'), $this->budgetVouchersQuery($fund, null, $startDate)->first()->id);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVouchersCreatedBySponsorBatch(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail(), bsn: TestData::randomFakeBsn());
        $otherIdentity = $this->makeIdentity($this->makeUniqueEmail());
        $fund = $this->makeMultiplyVouchersFund(fundConfigsData: $this->sponsorVoucherFundConfigs());
        $startDate = Carbon::now();

        // only the first identity has the trusted record
        $this->makePrevalidationForTestCriteria($fund->organization, $fund)->assignToIdentity($identity);

        $this->apiMakeVoucherRequestBatchRequest($fund->organization, [
            'fund_id' => $fund->id,
            'vouchers' => [[
                'email' => $identity->email,
                'amount' => $this->mainVoucherAmount,
                'activate' => true,
            ], [
                'email' => $otherIdentity->email,
                'amount' => $this->mainVoucherAmount,
                'activate' => true,
            ]],
        ])->assertSuccessful();

        $this->assertMultiplyVouchersCreated($fund, $identity, $startDate);
        $this->assertEquals(1, $this->budgetVouchersQuery($fund, $otherIdentity, $startDate)->count());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSponsorBatchAggregateBudgetLimitWithMixedCaseRepeatedEmail(): void
    {
        $email = $this->makeUniqueEmail('Mixed.');
        $identity = $this->makeIdentity($email);
        $fund = $this->makeMultiplyVouchersFund(fundConfigsData: [
            ...$this->sponsorVoucherFundConfigs(),
            'generator_ignore_fund_budget' => false,
        ]);

        $organization = $fund->organization;
        $emails = [$email, Str::upper($email), Str::lower($email)];
        $totalAmount = count($emails) * ($this->mainVoucherAmount + $this->formulaAmount * $this->recordValue);

        $this->makePrevalidationForTestCriteria($organization, $fund)->assignToIdentity($identity);
        $this->setTestFundBudget($fund, $totalAmount - 1);

        $data = [
            'fund_id' => $fund->id,
            'vouchers' => array_map(fn (string $email) => [
                'email' => $email,
                'amount' => $this->mainVoucherAmount,
                'activate' => true,
            ], $emails),
        ];

        foreach ([true, false] as $validate) {
            $this->apiMakeVoucherRequestBatchRequest($organization, $data, validate: $validate)
                ->assertStatus(422)
                ->assertOnlyJsonValidationErrors([
                    'vouchers.0.amount' => __('validation.voucher_generator.budget_exceeded'),
                    'vouchers.1.amount' => __('validation.voucher_generator.budget_exceeded'),
                    'vouchers.2.amount' => __('validation.voucher_generator.budget_exceeded'),
                ]);

            $this->assertSame(0, $fund->vouchers()->count());
        }

        $this->setTestFundBudget($fund, $totalAmount);

        $this->apiMakeVoucherRequestBatchRequest($organization, $data, validate: true)->assertSuccessful();
        $this->assertSame(0, $fund->vouchers()->count());
        $this->apiMakeVoucherRequestBatchRequest($organization, $data)->assertSuccessful();

        $vouchers = $fund->vouchers()->get();

        $this->assertCount(count($emails) * (1 + $this->recordValue), $vouchers);
        $this->assertCount(count($emails), $vouchers->where('amount', $this->mainVoucherAmount));
        $this->assertCount(count($emails) * $this->recordValue, $vouchers->where('amount', $this->formulaAmount));
        $this->assertEquals($totalAmount, $vouchers->sum('amount'));
        $this->assertSame([$identity->id], $vouchers->pluck('identity_id')->unique()->values()->all());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testNoVouchersCreatedWithoutRecordValue(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail(), bsn: TestData::randomFakeBsn());
        $fund = $this->makeMultiplyVouchersFund();
        $startDate = Carbon::now();

        $this->makePrevalidationForTestCriteria($fund->organization, $fund)->assignToIdentity($identity);

        // switch formula to a record the identity doesn't have (prevalidation doesn't contain children_nth)
        $fund->fund_formulas()
            ->where('type', FundFormula::TYPE_MULTIPLY_VOUCHERS)
            ->update(['record_type_key' => 'children_nth']);

        $fund->refresh();

        $this->apiApplyForFundRequest($fund, $identity)->assertSuccessful();

        $vouchers = $this->budgetVouchersQuery($fund, $identity, $startDate)->get();

        $this->assertCount(1, $vouchers);
        $this->assertEquals($this->mainVoucherAmount, $vouchers[0]->amount);
    }

    /**
     * @param Organization|null $organization
     * @param array $fundConfigsData
     * @param bool $withFixedFormula
     * @param bool $withCriteria
     * @return Fund
     */
    protected function makeMultiplyVouchersFund(
        ?Organization $organization = null,
        array $fundConfigsData = [],
        bool $withFixedFormula = true,
        bool $withCriteria = true,
    ): Fund {
        $organization ??= $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, [], $fundConfigsData);

        if ($withCriteria) {
            $this->addTestCriteriaToFund($fund);
        } else {
            $fund->criteria()->delete();
        }

        if (!$withFixedFormula) {
            $fund->fund_formulas()->where('type', FundFormula::TYPE_FIXED)->delete();
        }

        $fund->fund_formulas()->create([
            'type' => FundFormula::TYPE_MULTIPLY_VOUCHERS,
            'amount' => $this->formulaAmount,
            'record_type_key' => $this->recordTypeKey,
        ]);

        return $fund->refresh();
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @return FundRequest
     */
    protected function makeAndApproveFundRequest(Identity $identity, Fund $fund): FundRequest
    {
        $response = $this->makeFundRequest($identity, $fund, [
            $this->makeRequestCriterionValue($fund, 'test_bool', 'Ja'),
            $this->makeRequestCriterionValue($fund, 'test_iban', fake()->iban()),
            $this->makeRequestCriterionValue($fund, 'test_date', '01-01-2010'),
            $this->makeRequestCriterionValue($fund, 'test_email', fake()->email()),
            $this->makeRequestCriterionValue($fund, 'test_string', 'lorem_ipsum'),
            $this->makeRequestCriterionValue($fund, 'test_string_any', 'ipsum_lorem'),
            $this->makeRequestCriterionValue($fund, 'test_number', 7),
            $this->makeRequestCriterionValue($fund, 'test_select', 'foo'),
            $this->makeRequestCriterionValue($fund, 'test_select_number', $this->recordValue),
        ], false);

        $response->assertSuccessful();

        $fundRequest = FundRequest::find($response->json('data.id'));
        $fundRequest->assignEmployee($fund->organization->employees->first());
        $fundRequest->approve();

        $this->assertTrue($fundRequest->refresh()->isApproved());

        return $fundRequest;
    }

    /**
     * @return array
     */
    protected function sponsorVoucherFundConfigs(): array
    {
        return [
            'limit_generator_amount' => 1000,
            'limit_voucher_total_amount' => 1000,
            'generator_ignore_fund_budget' => true,
        ];
    }

    /**
     * @param Fund $fund
     * @param Identity|null $identity
     * @param Carbon $startDate
     * @return Builder
     */
    protected function budgetVouchersQuery(Fund $fund, ?Identity $identity, Carbon $startDate): Builder
    {
        return Voucher::query()
            ->where('fund_id', $fund->id)
            ->where('identity_id', $identity?->id)
            ->whereNull('product_id')
            ->whereNull('parent_id')
            ->where('created_at', '>=', $startDate->copy()->startOfSecond());
    }

    /**
     * Assert identity got the main voucher plus record value count of vouchers with formula amount.
     *
     * @param Fund $fund
     * @param Identity $identity
     * @param Carbon $startDate
     * @param array $voucherFields
     * @return void
     */
    protected function assertMultiplyVouchersCreated(
        Fund $fund,
        Identity $identity,
        Carbon $startDate,
        array $voucherFields = [],
    ): void {
        $vouchers = $this->budgetVouchersQuery($fund, $identity, $startDate)->get();

        $this->assertCount(1 + $this->recordValue, $vouchers, 'Main voucher and multiply vouchers expected.');
        $this->assertCount(1, $vouchers->where('amount', $this->mainVoucherAmount), 'Main voucher expected.');
        $this->assertCount($this->recordValue, $vouchers->where('amount', $this->formulaAmount));

        foreach ($vouchers as $voucher) {
            foreach ($voucherFields as $key => $value) {
                $this->assertEquals($value, $voucher->$key, "Voucher field [$key] mismatch.");
            }
        }
    }

    /**
     * Assert identity got only record value count of vouchers with formula amount, without main voucher.
     *
     * @param Fund $fund
     * @param Identity $identity
     * @param Carbon $startDate
     * @param FundRequest $fundRequest
     * @return void
     */
    protected function assertOnlyMultiplyVouchersCreated(
        Fund $fund,
        Identity $identity,
        Carbon $startDate,
        FundRequest $fundRequest,
    ): void {
        $vouchers = $this->budgetVouchersQuery($fund, $identity, $startDate)->get();

        $this->assertCount($this->recordValue, $vouchers, 'Only multiply vouchers expected.');
        $this->assertCount($this->recordValue, $vouchers->where('amount', $this->formulaAmount));
        $this->assertCount($this->recordValue, $vouchers->where('fund_request_id', $fundRequest->id));
    }
}
