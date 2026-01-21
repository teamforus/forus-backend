<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\FundAmountPreset;
use App\Models\FundConfig;
use App\Models\FundFormula;
use App\Models\FundRequest;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\VoucherTransactionQuery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;

class PayoutsTest extends TestCase
{
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesProductReservations;

    /**
     * @return void
     */
    public function testFundPayoutSettingsWhileNotAllowed(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $organization->forceFill([ 'allow_payouts' => false ])->update();

        $this->configureFundPayouts($fund);
        $this->assertPayoutsNotUpdated($fund);
    }

    /**
     * @return void
     */
    public function testFundPayoutSettingsWhileAllowed(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $organization->forceFill(['allow_payouts' => true])->save();

        $this->configureFundPayouts($fund);
        $this->assertPayoutsUpdated($fund);
    }

    /**
     * @return void
     */
    public function testFundPayoutSettingsUpdatePresets(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $organization->forceFill(['allow_payouts' => true])->save();

        $this->configureFundPayouts($fund);
        $this->assertPayoutsUpdated($fund);

        $presets = $fund->amount_presets->map(fn (FundAmountPreset $preset) => $preset->only([
            'id', 'amount', 'name',
        ]))->toArray();

        $presets[0]['name'] = 'Updated';
        $presets[0]['amount'] = '50.00';
        $presets[2] = [ 'name' => 'New', 'amount' => '100.00' ];

        $res = $this->patchJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds/$fund->id",
            [ 'amount_presets' => $presets],
            $this->makeApiHeaders($this->makeIdentityProxy($fund->organization->identity)),
        );

        $res->assertSuccessful();
        $fund->refresh();

        self::assertEquals(
            array_sum(array_pluck($presets, 'amount')),
            $fund->amount_presets->sum('amount'),
        );
    }

    /**
     * @return void
     */
    public function testPayoutCreateSuccess(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $organization->forceFill(['allow_payouts' => true])->save();

        $this->configureFundPayouts($fund);
        $this->assertPayoutsUpdated($fund);

        $data = [
            'amount' => '50.00',
            'fund_id' => $fund->id,
            'description' => 'Test description',
            'target_iban' => $this->faker()->iban(),
            'target_name' => $this->makeIbanName(),
        ];

        $res = $this->storeRequest($fund, $data);

        $res->assertSuccessful();

        self::assertEquals(
            VoucherTransaction::TARGET_PAYOUT,
            VoucherTransaction::find($res->json('data.id'))->target,
        );

        self::assertEquals($data['amount'], $res->json('data.amount'));
        self::assertEquals($data['fund_id'], $res->json('data.fund.id'));
        self::assertEquals($data['description'], $res->json('data.description'));
        self::assertEquals($data['target_iban'], $res->json('data.iban_to'));
        self::assertEquals($data['target_name'], $res->json('data.iban_to_name'));
    }

    /**
     * @return void
     */
    public function testPayoutCreateValidationErrors(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $organization2 = $this->makeTestOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization2);

        $organization2->forceFill([ 'allow_payouts' => true ])->update();
        $organization->forceFill(['allow_payouts' => true])->save();

        $this->configureFundPayouts($fund);
        $this->configureFundPayouts($fund2);

        $this->assertPayoutsUpdated($fund);
        $this->assertPayoutsUpdated($fund2);

        $presets = $fund->amount_presets->pluck('id')->toArray();

        // assert can't use fund_id from another organization while correct fund id works
        $this->storeRequest($fund, ['fund_id' => $fund2->id])->assertJsonValidationErrorFor('fund_id');
        $this->storeRequest($fund, ['fund_id' => $fund->id])->assertJsonMissingValidationErrors('fund_id');

        // assert can't use non string for description but null is allowed
        $this->storeRequest($fund, ['description' => []])->assertJsonValidationErrorFor('description');
        $this->storeRequest($fund, ['description' => null])->assertJsonMissingValidationErrors('description');

        // assert can't use amount lower or higher than configured
        $this->storeRequest($fund, ['amount' => 5])->assertJsonValidationErrorFor('amount');
        $this->storeRequest($fund, ['amount' => 200])->assertJsonValidationErrorFor('amount');
        $this->storeRequest($fund, ['amount' => 50])->assertJsonMissingValidationErrors('amount');

        // assert amount_preset_id is validated
        $this->storeRequest($fund, ['amount_preset_id' => null])->assertJsonValidationErrorFor('amount_preset_id');
        $this->storeRequest($fund, ['amount_preset_id' => 999])->assertJsonValidationErrorFor('amount_preset_id');
        $this->storeRequest($fund, ['amount_preset_id' => $presets[0]])->assertJsonMissingValidationErrors('amount_preset_id');
        $this->storeRequest($fund, ['amount_preset_id' => $presets[1]])->assertJsonMissingValidationErrors('amount_preset_id');
        $this->storeRequest($fund, ['amount_preset_id' => $presets[2]])->assertJsonMissingValidationErrors('amount_preset_id');

        // assert can't use non numeric
        $this->storeRequest($fund, ['amount' => []])->assertJsonValidationErrorFor('amount');
        $this->storeRequest($fund, ['amount' => null])->assertJsonValidationErrorFor('amount');

        // assert can't use non iban value
        $this->storeRequest($fund, ['target_iban' => 'invalid'])->assertJsonValidationErrorFor('target_iban');
        $this->storeRequest($fund, ['target_iban' => null])->assertJsonValidationErrorFor('target_iban');
        $this->storeRequest($fund, ['target_iban' => []])->assertJsonValidationErrorFor('target_iban');

        // assert iban_name is required and can't use non string values
        $this->storeRequest($fund, ['target_name' => null])->assertJsonValidationErrorFor('target_name');
        $this->storeRequest($fund, ['target_name' => []])->assertJsonValidationErrorFor('target_name');

        // assert amount is required when amount_preset_id is missing
        $this->storeRequest($fund, ['amount' => null ])->assertJsonValidationErrorFor('amount');
        $this->storeRequest($fund, ['amount_preset_id' => $presets[1]])->assertJsonMissingValidationErrors('amount');
    }

    /**
     * @return void
     */
    public function testPayoutCreateBatchSuccess(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $organization->forceFill(['allow_payouts' => true])->save();

        $this->configureFundPayouts($fund);
        $this->assertPayoutsUpdated($fund);

        $res = $this->storeRequestBatch($fund, [
            'description' => 'Test description',
            'amount' => '50',
            'target_iban' => $this->faker()->iban(),
            'target_name' => $this->makeIbanName(),
        ]);

        $res->assertSuccessful();

        self::assertEquals(
            VoucherTransaction::TARGET_PAYOUT,
            VoucherTransaction::find($res->json('data.0.id'))?->target,
        );
    }

    /**
     * @return void
     */
    public function testPayoutCreateBatchValidationErrors(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($organization);

        $organization->forceFill(['allow_payouts' => true])->save();

        $this->configureFundPayouts($fund);
        $this->assertPayoutsUpdated($fund);

        $presets = $fund->amount_presets->pluck('amount')->toArray();

        // assert can't use fund_id from another organization while correct fund id works
        $this->storeRequestBatch($fund, ['fund_id' => $fund->id])->assertJsonMissingValidationErrors('fund_id');

        // assert can't use non string for description but null is allowed
        $this->storeRequestBatch($fund, ['description' => []])->assertJsonValidationErrorFor('payouts.0.description');
        $this->storeRequestBatch($fund, ['description' => null])->assertJsonMissingValidationErrors('payouts.0.description');

        // assert can't use amount lower or higher than configured
        $this->storeRequestBatch($fund, ['amount' => 5])->assertJsonValidationErrorFor('payouts.0.amount');
        $this->storeRequestBatch($fund, ['amount' => 200])->assertJsonValidationErrorFor('payouts.0.amount');
        $this->storeRequestBatch($fund, ['amount' => 50])->assertJsonMissingValidationErrors('payouts.0.amount');

        // assert amount_preset_id is validated
        $this->storeRequestBatch($fund, ['amount_preset' => null])->assertJsonValidationErrorFor('payouts.0.amount_preset');
        $this->storeRequestBatch($fund, ['amount_preset' => 999])->assertJsonValidationErrorFor('payouts.0.amount_preset');
        $this->storeRequestBatch($fund, ['amount_preset' => $presets[0]])->assertJsonMissingValidationErrors('payouts.0.amount_preset');
        $this->storeRequestBatch($fund, ['amount_preset' => $presets[1]])->assertJsonMissingValidationErrors('payouts.0.amount_preset');
        $this->storeRequestBatch($fund, ['amount_preset' => $presets[2]])->assertJsonMissingValidationErrors('payouts.0.amount_preset');

        // assert can't use non numeric
        $this->storeRequestBatch($fund, ['amount' => []])->assertJsonValidationErrorFor('payouts.0.amount');
        $this->storeRequestBatch($fund, ['amount' => null])->assertJsonValidationErrorFor('payouts.0.amount');

        // assert can't use non iban value
        $this->storeRequestBatch($fund, ['target_iban' => 'invalid'])->assertJsonValidationErrorFor('payouts.0.target_iban');
        $this->storeRequestBatch($fund, ['target_iban' => null])->assertJsonValidationErrorFor('payouts.0.target_iban');
        $this->storeRequestBatch($fund, ['target_iban' => []])->assertJsonValidationErrorFor('payouts.0.target_iban');

        // assert iban_name is required and can't use non string values
        $this->storeRequestBatch($fund, ['target_name' => null])->assertJsonValidationErrorFor('payouts.0.target_name');
        $this->storeRequestBatch($fund, ['target_name' => []])->assertJsonValidationErrorFor('payouts.0.target_name');

        // assert amount is required when amount_preset_id is missing
        $this->storeRequestBatch($fund, ['amount' => null ])->assertJsonValidationErrorFor('payouts.0.amount');
        $this->storeRequestBatch($fund, ['amount_preset' => $presets[1]])->assertJsonMissingValidationErrors('payouts.0.amount');
    }

    /**
     * @return void
     */
    public function testPayoutFundRequestUsingFormula(): void
    {
        $fundRequest = $this->makePayoutFundRequest();
        $fundRequest = $this->approveFundRequest($fundRequest);

        $this->assertFundRequestGeneratedPayout($fundRequest);

        self::assertEquals(
            $fundRequest->fund->fund_formulas->where('type', FundFormula::TYPE_FIXED)->sum('amount'),
            $fundRequest->vouchers[0]?->transactions[0]?->amount,
        );
    }

    /**
     * @return void
     */
    public function testPayoutFundRequestUsingCustomAmount(): void
    {
        $fundRequest = $this->makePayoutFundRequest();
        $fundRequest = $this->approveFundRequest($fundRequest, ['amount' => 75]);

        $this->assertFundRequestGeneratedPayout($fundRequest);

        self::assertEquals(75, $fundRequest->vouchers[0]?->transactions[0]?->amount);
    }

    /**
     * @return void
     */
    public function testPayoutFundRequestUsingCustomAmountWhenFormulaIsMissing(): void
    {
        $fundRequest = $this->makePayoutFundRequest();
        $fundRequest->fund->fund_formulas()->delete();

        $fundRequest = $this->approveFundRequest($fundRequest, ['amount' => 75]);
        $this->assertFundRequestGeneratedPayout($fundRequest);

        self::assertEquals(75, $fundRequest->vouchers[0]?->transactions[0]?->amount);
    }

    /**
     * @return void
     */
    public function testPayoutFundRequestUsingCustomPreset(): void
    {
        $fundRequest = $this->makePayoutFundRequest();

        $fundRequest = $this->approveFundRequest($fundRequest, [
            'fund_amount_preset_id' => $fundRequest->fund->amount_presets[1]->id,
        ]);

        $this->assertFundRequestGeneratedPayout($fundRequest);

        self::assertEquals(
            $fundRequest->fund->amount_presets[1]->amount,
            $fundRequest->vouchers[0]?->transactions[0]?->amount,
        );
    }

    /**
     * @return void
     */
    public function testPayoutDelayTime(): void
    {
        $fundRequest = $this->makePayoutFundRequest();
        $fundRequest = $this->approveFundRequest($fundRequest);
        $organization = $fundRequest->fund->organization;

        $this->assertFundRequestGeneratedPayout($fundRequest);

        $transaction = $fundRequest->vouchers[0]?->transactions[0];

        self::assertTrue($transaction->transfer_at->isTomorrow());
        self::assertEquals(0, VoucherTransactionQuery::whereAvailableForBulking(
            VoucherTransaction::whereId($transaction->id),
        )->count());

        $res = $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/sponsor/payouts/$transaction->address",
            ['skip_transfer_delay' => true],
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity))
        );

        $res->assertSuccessful();
        $transaction->refresh();

        self::assertTrue($transaction->transfer_at->isToday());
        self::assertEquals(1, VoucherTransactionQuery::whereAvailableForBulking(
            VoucherTransaction::whereId($transaction->id),
        )->count());
    }

    /**
     * @return void
     */
    public function testPayoutCancel(): void
    {
        $fundRequest = $this->makePayoutFundRequest();
        $fundRequest = $this->approveFundRequest($fundRequest);
        $organization = $fundRequest->fund->organization;

        $this->assertFundRequestGeneratedPayout($fundRequest);

        $transaction = $fundRequest->vouchers[0]?->transactions[0];

        self::assertEquals(VoucherTransaction::STATE_PENDING, $transaction->state);

        $res = $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/sponsor/payouts/$transaction->address",
            ['cancel' => true],
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity))
        );

        $res->assertSuccessful();
        $transaction->refresh();

        self::assertEquals(VoucherTransaction::STATE_CANCELED, $transaction->state);
    }

    /**
     * @param Fund $fund
     * @return void
     */
    protected function configureFundPayouts(Fund $fund): void
    {
        $res = $this->patchJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds/$fund->id",
            $this->getFundPayoutConfigs(),
            $this->makeApiHeaders($this->makeIdentityProxy($fund->organization->identity)),
        );

        $res->assertSuccessful();
        $fund->refresh();
    }

    /**
     * @param Fund $fund
     * @return void
     */
    protected function configureFundFundRequestPayouts(Fund $fund): void
    {
        $fund->fund_config->forceFill([
            'outcome_type' => FundConfig::OUTCOME_TYPE_PAYOUT,
            'iban_record_key' => 'iban',
            'iban_name_record_key' => 'iban_name',
        ])->save();

        $fund->organization->forceFill([
            'allow_payouts' => true,
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        $this->configureFundPayouts($fund);
        $this->assertPayoutsUpdated($fund);

        $fund->syncCriteria([
            ['record_type_key' => 'iban', 'operator' => '*', 'value' => '', 'show_attachment' => false],
            ['record_type_key' => 'iban_name', 'operator' => '*', 'value' => '', 'show_attachment' => false],
        ]);

        $fund->refresh();
    }

    /**
     * @param Fund $fund
     * @return void
     */
    protected function assertPayoutsNotUpdated(Fund $fund): void
    {
        self::assertNull($fund->fund_config->custom_amount_min);
        self::assertNull($fund->fund_config->custom_amount_max);
        self::assertFalse($fund->fund_config->allow_preset_amounts);
        self::assertFalse($fund->fund_config->allow_preset_amounts_validator);
        self::assertFalse($fund->fund_config->allow_custom_amounts);
        self::assertFalse($fund->fund_config->allow_custom_amounts_validator);
        self::assertEmpty($fund->amount_presets->toArray());
    }

    /**
     * @param Fund $fund
     * @return void
     */
    protected function assertPayoutsUpdated(Fund $fund): void
    {
        $configs = $this->getFundPayoutConfigs();

        self::assertEquals($configs['custom_amount_min'], $fund->fund_config->custom_amount_min);
        self::assertEquals($configs['custom_amount_max'], $fund->fund_config->custom_amount_max);
        self::assertEquals($configs['allow_preset_amounts'], $fund->fund_config->allow_preset_amounts);
        self::assertEquals($configs['allow_preset_amounts_validator'], $fund->fund_config->allow_preset_amounts_validator);
        self::assertEquals($configs['allow_custom_amounts'], $fund->fund_config->allow_custom_amounts);
        self::assertEquals($configs['allow_custom_amounts_validator'], $fund->fund_config->allow_custom_amounts_validator);

        self::assertEquals(
            array_sum(Arr::pluck($configs['amount_presets'], 'amount')),
            $fund->amount_presets->sum('amount'),
        );
    }

    /**
     * @return array
     */
    protected function getFundPayoutConfigs(): array
    {
        return [
            'custom_amount_min' => '10.00',
            'custom_amount_max' => '100.00',
            'allow_preset_amounts' => true,
            'allow_preset_amounts_validator' => true,
            'allow_custom_amounts' => true,
            'allow_custom_amounts_validator' => true,
            'amount_presets' => [
                ['name' => 'Preset #1', 'amount' => '10.00'],
                ['name' => 'Preset #2', 'amount' => '20.00'],
                ['name' => 'Preset #3', 'amount' => '30.00'],
            ],
        ];
    }

    /**
     * @param Fund $fund
     * @param array $data
     * @return \Illuminate\Testing\TestResponse
     */
    protected function storeRequest(Fund $fund, array $data): TestResponse
    {
        $apiUrl = "/api/v1/platform/organizations/$fund->organization_id/sponsor/payouts";

        return $this->postJson($apiUrl, [
            'fund_id' => $fund->id,
            ...$data,
        ], $this->makeApiHeaders($this->makeIdentityProxy($fund->organization->identity)));
    }

    /**
     * @param Fund $fund
     * @param array $data
     * @return \Illuminate\Testing\TestResponse
     */
    protected function storeRequestBatch(Fund $fund, array $data): TestResponse
    {
        $apiUrl = "/api/v1/platform/organizations/$fund->organization_id/sponsor/payouts/batch";

        return $this->postJson($apiUrl, [
            'fund_id' => $fund->id,
            'payouts' => [$data],
        ], $this->makeApiHeaders($this->makeIdentityProxy($fund->organization->identity)));
    }

    /**
     * @return FundRequest
     */
    protected function makePayoutFundRequest(): FundRequest
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);
        $this->configureFundFundRequestPayouts($fund);

        $recordTypes = $fund->criteria->pluck('id', 'record_type.key');

        return $this->makeFundRequest($fund, [
            ['fund_criterion_id' => $recordTypes['iban'], 'value' => $this->faker()->iban(), 'files' => null],
            ['fund_criterion_id' => $recordTypes['iban_name'], 'value' => 'John doe', 'files' => null],
        ]);
    }

    /**
     * @param Fund $fund
     * @param array $records
     * @return FundRequest
     */
    protected function makeFundRequest(Fund $fund, array $records): FundRequest
    {
        $requester = $this->makeIdentity(email: $this->makeUniqueEmail(), bsn: 123456789);
        $requesterIdentityAuth = $this->makeApiHeaders($requester);

        // make the fund request
        $response = $this->postJson("/api/v1/platform/funds/$fund->id/requests", [
            'contact_information' => 'Test info',
            'records' => $records,
        ], $requesterIdentityAuth);

        $response->assertSuccessful();
        $fundRequest = FundRequest::find($response->json('data.id'));

        self::assertNotNull($fundRequest);

        return $fundRequest;
    }

    /**
     * @param FundRequest $fundRequest
     * @param array $data
     * @return FundRequest
     */
    protected function approveFundRequest(FundRequest $fundRequest, array $data = []): FundRequest
    {
        $res = $this->patchJson(
            "/api/v1/platform/organizations/{$fundRequest->fund->organization_id}/fund-requests/$fundRequest->id/assign",
            [],
            $this->makeApiHeaders($this->makeIdentityProxy($fundRequest->fund->organization->identity)),
        );

        $res->assertSuccessful();

        $res = $this->patchJson(
            "/api/v1/platform/organizations/{$fundRequest->fund->organization_id}/fund-requests/$fundRequest->id/approve",
            $data,
            $this->makeApiHeaders($this->makeIdentityProxy($fundRequest->fund->organization->identity)),
        );

        $res->assertSuccessful();

        return $fundRequest;
    }

    /**
     * @param FundRequest $fundRequest
     * @return void
     */
    protected function assertFundRequestGeneratedPayout(FundRequest $fundRequest): void
    {
        $voucher = $fundRequest->vouchers[0];
        $transaction = $voucher?->transactions[0];

        self::assertNotNull($voucher);
        self::assertNotNull($transaction);
        self::assertEquals(VoucherTransaction::TARGET_PAYOUT, $transaction?->target);
    }
}
