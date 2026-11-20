<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundConfig;
use App\Models\FundFormula;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\ProfileRecord;
use App\Models\RecordType;
use App\Models\Voucher;
use App\Modules\Voucher\Services\ProfileVoucherAwardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Log;
use Mockery;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestProducts;
use Tests\Traits\MakesTestRecords;
use Tests\Traits\MakesTestRequesterImports;
use Throwable;

class ProfileVoucherAwardTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestFundRequests;
    use MakesTestFunds;
    use MakesTestProducts;
    use MakesTestRecords;
    use MakesTestRequesterImports;

    /**
     * @throws Throwable
     * @return void
     */
    public function testPreviewAndAwardUseImportedProfileValues(): void
    {
        ['fund' => $fund, 'profile' => $profile, 'employee' => $employee] = $this->makeAwardFixture();
        $before = $profile->profile_records()->get()->toArray();

        $this->assertNotSame($profile->id, $profile->identity_id);

        $this
            ->apiStoreVouchersFromProfilesRequest($fund->organization, [
                'fund_ids' => [$fund->id],
                'profile_ids' => [$profile->id],
            ], $employee->identity, preview: true)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'eligible')
            ->assertJsonPath('data.0.voucher_count', 1)
            ->assertJsonPath('data.0.profile_id', $profile->id)
            ->assertJsonPath('data.0.identity_id', $profile->identity_id);

        $this->assertFalse($fund->vouchers()->exists());
        $this->assertSame($before, $profile->profile_records()->get()->toArray());

        $response = $this
            ->apiStoreVouchersFromProfilesRequest($fund->organization, [
                'fund_ids' => [$fund->id],
                'profile_ids' => [$profile->id],
            ], $employee->identity)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'created')
            ->assertJsonPath('data.0.profile_id', $profile->id)
            ->assertJsonPath('data.0.identity_id', $profile->identity_id);

        $voucher = $fund->vouchers()->sole();

        $this->assertSame([$voucher->id], $response->json('data.0.voucher_ids'));
        $this->assertSame($profile->identity_id, $voucher->identity_id);
        $this->assertSame($employee->id, $voucher->employee_id);
        $this->assertEquals(30, $voucher->amount);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testLatestNonImportValueMasksOlderImportedValue(): void
    {
        foreach (['requester', 'employee', 'application', 'import'] as $source) {
            ['fund' => $fund, 'profile' => $profile, 'number' => $number, 'employee' => $employee] =
                $this->makeAwardFixture();

            $this->travel(1)->seconds();
            $attributes = ['employee_id' => $source === 'requester' ? null : $employee->id];

            if ($source === 'application') {
                $request = $fund->fund_requests()->create(['identity_id' => $profile->identity_id]);
                $answer = $request->records()->create(['record_type_key' => $number->key, 'value' => '2']);
                $attributes += ['origin_type' => $answer->getMorphClass(), 'origin_id' => $answer->id];
            }

            $attributes['source'] = $source === 'import' ? ProfileRecord::SOURCE_REQUESTER_IMPORT : null;

            $this->makeProfileRecord($profile, $number, '2', $attributes);

            $this
                ->apiStoreVouchersFromProfilesRequest($fund->organization, [
                    'fund_ids' => [$fund->id],
                    'profile_ids' => [$profile->id],
                ], $employee->identity, preview: true)
                ->assertOk()
                ->assertJsonPath('data.0.status', $source === 'import' ? 'eligible' : 'skipped');

            $this->assertFalse($fund->vouchers()->exists());
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProfilesRequireAnImportLinkInTheFundOrganization(): void
    {
        ['fund' => $fund, 'profile' => $profile, 'employee' => $employee] = $this->makeAwardFixture();
        ['profile' => $foreign] = $this->makeAwardFixture();

        $profile->requester_import_rows()->update(['profile_id' => null]);

        $this
            ->apiGetProfileVoucherCandidateIdsRequest($fund->organization, $employee->identity)
            ->assertOk()
            ->assertExactJson(['data' => []]);

        foreach ([true, false] as $preview) {
            $this
                ->apiStoreVouchersFromProfilesRequest($fund->organization, [
                    'fund_ids' => [$fund->id],
                    'profile_ids' => [$profile->id, $foreign->id],
                ], $employee->identity, $preview)
                ->assertOk()
                ->assertExactJson(['data' => []]);
        }

        $this->assertFalse($fund->vouchers()->exists());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProfileCriteriaRespectConditionalAndRequiredGroups(): void
    {
        foreach (['missing' => false, 'present' => true] as $variant => $present) {
            ['fund' => $fund, 'profile' => $profile, 'number' => $number, 'employee' => $employee] =
                $this->makeAwardFixture();

            $group = $this->makeCriteriaGroup($fund, required: true);
            $optional = $this->makeProfileRecordType($fund->organization);
            $excluded = $this->makeProfileRecordType($fund->organization);

            $fund->criteria()->create([
                'record_type_key' => $optional->key,
                'operator' => '*',
                'optional' => true,
                'fund_criteria_group_id' => $group->id,
                'show_attachment' => false,
            ]);

            $criterion = $fund->criteria()->create([
                'record_type_key' => $excluded->key,
                'operator' => '*',
                'show_attachment' => false,
            ]);

            $criterion->fund_criterion_rules()->forceCreate([
                'record_type_key' => $number->key,
                'operator' => '>',
                'value' => '5',
            ]);

            if ($present) {
                $this->makeImportedAwardRecord($profile, $optional, 'Provided');
            }

            $response = $this
                ->apiStoreVouchersFromProfilesRequest($fund->organization, [
                    'fund_ids' => [$fund->id],
                    'profile_ids' => [$profile->id],
                ], $employee->identity, preview: true)
                ->assertOk();

            $this->assertSame($present ? 'eligible' : 'skipped', $response->json('data.0.status'), $variant);
            $this->assertFalse($fund->vouchers()->exists());
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundSelectionMatchesProfileAwardAvailability(): void
    {
        foreach (['active', 'future_paused', 'waiting', 'expired', 'external', 'unconfigured', 'payout'] as $variant) {
            ['fund' => $fund, 'profile' => $profile, 'employee' => $employee] = $this->makeAwardFixture();

            match ($variant) {
                'future_paused' => $fund->update([
                    'state' => Fund::STATE_PAUSED,
                    'start_date' => Carbon::now()->addMonth(),
                ]),
                'waiting' => $fund->update(['state' => Fund::STATE_WAITING]),
                'expired' => $fund->update(['end_date' => Carbon::now()->subDay()]),
                'external' => $fund->update(['external' => true]),
                'unconfigured' => $fund->fund_config->forceFill(['is_configured' => false])->save(),
                'payout' => $fund->fund_config->forceFill(['outcome_type' => FundConfig::OUTCOME_TYPE_PAYOUT])->save(),
                default => null,
            };

            $allowed = in_array($variant, ['active', 'future_paused']);

            $list = $this
                ->apiGetOrganizationFundsRequest($fund->organization, $fund->organization->identity, [
                    'voucher_creation_from_profiles' => 1,
                ])
                ->assertOk();

            $this->assertSame($allowed ? [$fund->id] : [], array_column($list->json('data'), 'id'), $variant);

            $response = $this
                ->apiStoreVouchersFromProfilesRequest($fund->organization, [
                    'fund_ids' => [$fund->id],
                    'profile_ids' => [$profile->id],
                ], $employee->identity, preview: true)
                ->assertOk();

            $this->assertSame($allowed ? 'eligible' : 'skipped', $response->json('data.0.status'), $variant);
            $this->assertFalse($fund->vouchers()->exists());
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProfileAwardDoesNotRequireRequesterImportsEnabled(): void
    {
        ['fund' => $fund, 'profile' => $profile, 'employee' => $employee] = $this->makeAwardFixture();
        $organization = $fund->organization;
        $organization->forceFill(['allow_requester_imports' => false])->save();
        $payload = ['fund_ids' => [$fund->id], 'profile_ids' => [$profile->id]];

        $this
            ->apiGetProfileVoucherCandidateIdsRequest($organization, $employee->identity)
            ->assertOk()
            ->assertExactJson(['data' => [$profile->id]]);

        $this
            ->apiStoreVouchersFromProfilesRequest($organization, $payload, $employee->identity, preview: true)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'eligible');

        $this
            ->apiStoreVouchersFromProfilesRequest($organization, $payload, $employee->identity)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'created');

        $this->assertSame(1, $fund->vouchers()->count());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProfileAwardRequiresOrganizationFeatureFlags(): void
    {
        foreach (['allow_vouchers_from_profiles', 'allow_profiles', 'bsn_enabled'] as $flag) {
            ['fund' => $fund, 'profile' => $profile, 'employee' => $employee] = $this->makeAwardFixture();
            $fund->organization->forceFill([$flag => false])->save();

            $this->assertProfileAwardAccessDenied($fund, $profile, $employee);
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProfileAwardRequiresManageVouchersPermission(): void
    {
        ['fund' => $fund, 'profile' => $profile] = $this->makeAwardFixture();
        $employee = $this->makeTestEmployeeWithPermissions($fund->organization, [Permission::MANAGE_IDENTITIES]);

        $this->assertProfileAwardAccessDenied($fund, $profile, $employee);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProfileSelectionIncludesOnlyRequestedOrganizationProfiles(): void
    {
        foreach (['empty', 'subset', 'omitted'] as $variant) {
            ['fund' => $fund, 'profile' => $first, 'number' => $number, 'employee' => $employee] =
                $this->makeAwardFixture();

            $second = $this->makeAwardProfile($fund, $number);
            $stale = $fund->organization->findOrMakeProfile($this->makeIdentity($this->makeUniqueEmail()));

            $stale->delete();

            ['profile' => $foreign] = $this->makeAwardFixture();

            [$selection, $expected] = match ($variant) {
                'empty' => [['profile_ids' => []], []],
                'subset' => [['profile_ids' => [$second->id, $foreign->id, $stale->id]], [$second]],
                'omitted' => [[], [$first, $second]],
            };

            $this
                ->apiGetProfileVoucherCandidateIdsRequest($fund->organization, $employee->identity)
                ->assertOk()
                ->assertExactJson(['data' => [$first->id, $second->id]]);

            foreach ([true, false] as $preview) {
                $response = $this
                    ->apiStoreVouchersFromProfilesRequest($fund->organization, [
                        'fund_ids' => [$fund->id],
                        ...$selection,
                    ], $employee->identity, $preview)
                    ->assertOk();

                $this->assertSame(
                    array_map(fn (Profile $profile) => $profile->id, $expected),
                    array_column($response->json('data'), 'profile_id'),
                    $variant,
                );

                $this->assertSame(
                    $preview ? [] : array_map(fn (Profile $profile) => $profile->identity_id, $expected),
                    $fund->vouchers()->orderBy('id')->pluck('identity_id')->all(),
                    $variant,
                );
            }
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectsForeignFundsAndInvalidProfileBatchIds(): void
    {
        ['fund' => $fund, 'profile' => $profile, 'employee' => $employee] = $this->makeAwardFixture();
        ['fund' => $foreign] = $this->makeAwardFixture();

        foreach ([
            'foreign fund' => [
                ['fund_ids' => [$foreign->id]],
                ['fund_ids.0'],
            ],
            'duplicate profile' => [
                ['profile_ids' => [$profile->id, $profile->id]],
                ['profile_ids.0', 'profile_ids.1'],
            ],
            'oversized batch' => [
                ['profile_ids' => range(1, ProfileVoucherAwardService::BATCH_SIZE + 1)],
                ['profile_ids'],
            ],
        ] as [$invalid, $errors]) {
            foreach ([true, false] as $preview) {
                $this
                    ->apiStoreVouchersFromProfilesRequest($fund->organization, [
                        'fund_ids' => [$fund->id],
                        'profile_ids' => [$profile->id],
                        ...$invalid,
                    ], $employee->identity, $preview)
                    ->assertUnprocessable()
                    ->assertJsonValidationErrors($errors);
            }
        }

        $this->assertFalse($fund->vouchers()->exists());
        $this->assertFalse($foreign->vouchers()->exists());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAwardRechecksEligibilityAfterPreview(): void
    {
        foreach (['record', 'fund'] as $variant) {
            ['fund' => $fund, 'profile' => $profile, 'number' => $number, 'employee' => $employee] = $this->makeAwardFixture();
            $payload = ['fund_ids' => [$fund->id], 'profile_ids' => [$profile->id]];

            $this
                ->apiStoreVouchersFromProfilesRequest(
                    $fund->organization,
                    $payload,
                    $employee->identity,
                    preview: true,
                )
                ->assertOk()
                ->assertJsonPath('data.0.status', 'eligible');

            if ($variant === 'record') {
                $this->travel(1)->seconds();
                $this->makeProfileRecord($profile, $number, '0');
            } else {
                $fund->update(['end_date' => Carbon::now()->subDay()]);
            }

            $this
                ->apiStoreVouchersFromProfilesRequest($fund->organization, $payload, $employee->identity)
                ->assertOk()
                ->assertJsonPath('data.0.status', 'skipped');

            $this->assertFalse($fund->vouchers()->exists());
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRepeatedAwardSkipsAlreadyAwardedPair(): void
    {
        ['fund' => $fund, 'profile' => $profile, 'employee' => $employee] = $this->makeAwardFixture();
        $payload = ['fund_ids' => [$fund->id], 'profile_ids' => [$profile->id]];

        $first = $this
            ->apiStoreVouchersFromProfilesRequest($fund->organization, $payload, $employee->identity)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'created');

        $this
            ->apiStoreVouchersFromProfilesRequest($fund->organization, $payload, $employee->identity)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'skipped')
            ->assertJsonPath('data.0.voucher_ids', []);

        $this->assertSame($first->json('data.0.voucher_ids'), $fund->vouchers()->pluck('id')->all());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProfileAwardCalculatesMultiplyAndLimitMultiplier(): void
    {
        foreach (['present' => 6, 'missing' => 3] as $variant => $expectedLimit) {
            ['fund' => $fund, 'profile' => $profile, 'number' => $number, 'employee' => $employee] =
                $this->makeAwardFixture();

            $fund->fund_formulas()->delete();
            $fund->fund_formulas()->create([
                'type' => FundFormula::TYPE_MULTIPLY,
                'amount' => 25,
                'record_type_key' => $number->key,
            ]);

            $limit = $variant === 'present' ? $number : $this->makeProfileRecordType($fund->organization);
            $fund->fund_limit_multipliers()->create(['record_type_key' => $limit->key, 'multiplier' => 3]);

            $response = $this
                ->apiStoreVouchersFromProfilesRequest($fund->organization, [
                    'fund_ids' => [$fund->id],
                    'profile_ids' => [$profile->id],
                ], $employee->identity)
                ->assertOk()
                ->assertJsonPath('data.0.status', 'created');

            $voucher = $fund->vouchers()->sole();

            $this->assertEquals(50, $voucher->amount, $variant);
            $this->assertSame($expectedLimit, $voucher->limit_multiplier, $variant);
            $this->assertSame([$voucher->id], $response->json('data.0.voucher_ids'));
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProfileAwardCreatesAdditionalBudgetVouchers(): void
    {
        ['fund' => $fund, 'profile' => $profile, 'number' => $number, 'employee' => $employee] =
            $this->makeAwardFixture();

        $fund->fund_formulas()->delete();

        $fund->fund_formulas()->create([
            'type' => FundFormula::TYPE_MULTIPLY_VOUCHERS,
            'amount' => 25,
            'record_type_key' => $number->key,
        ]);

        $response = $this
            ->apiStoreVouchersFromProfilesRequest($fund->organization, [
                'fund_ids' => [$fund->id],
                'profile_ids' => [$profile->id],
            ], $employee->identity)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'created');

        $vouchers = $fund->vouchers()->orderBy('id')->get();

        $this->assertCount(2, $vouchers);
        $this->assertSame($vouchers->modelKeys(), $response->json('data.0.voucher_ids'));

        foreach ($vouchers as $voucher) {
            $this->assertNull($voucher->product_id);
            $this->assertEquals(25, $voucher->amount);
            $this->assertSame($employee->id, $voucher->employee_id);
            $this->assertSame($profile->identity_id, $voucher->identity_id);
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProfileAwardUsesConditionalBudgetAndProductFormulas(): void
    {
        foreach (['budget and products' => true, 'products only' => false] as $variant => $budgetMatches) {
            ['fund' => $fund, 'profile' => $profile, 'number' => $number, 'employee' => $employee] =
                $this->makeAwardFixture();

            $date = $this->makeProfileRecordType($fund->organization, ['type' => RecordType::TYPE_DATE]);
            $this->makeImportedAwardRecord($profile, $date, '1990-01-01');

            $fund->fund_formulas()->sole()->conditions()->create([
                'record_type_id' => $date->id,
                'operator' => $budgetMatches ? '<' : '>',
                'value' => '2000-01-01',
            ]);

            $excluded = $fund->fund_formulas()->create(['type' => FundFormula::TYPE_FIXED, 'amount' => 100]);

            $excluded->conditions()->create([
                'record_type_id' => $date->id,
                'operator' => '>',
                'value' => '2000-01-01',
            ]);

            $provider = $this->makeTestProviderOrganization($this->makeIdentity($this->makeUniqueEmail()));
            $product = $this->makeTestProduct($provider, attributes: ['unlimited_stock' => true]);

            $this->addProductToFund($fund, $product, true);

            $formula = $fund->fund_formula_products()->create([
                'product_id' => $product->id,
                'price' => 10,
                'record_type_key_multiplier' => $number->key,
            ]);

            $formula->conditions()->create([
                'record_type_id' => $date->id,
                'operator' => '<',
                'value' => '2000-01-01',
            ]);

            $formula->conditions()->create(['record_type_id' => $number->id, 'operator' => '>=', 'value' => '2']);

            $excludedProduct = $fund->fund_formula_products()->create(['product_id' => $product->id, 'price' => 90]);

            $excludedProduct->conditions()->create([
                'record_type_id' => $number->id,
                'operator' => '>',
                'value' => '2',
            ]);

            $response = $this
                ->apiStoreVouchersFromProfilesRequest($fund->organization, [
                    'fund_ids' => [$fund->id],
                    'profile_ids' => [$profile->id],
                ], $employee->identity)
                ->assertOk()
                ->assertJsonPath('data.0.status', 'created');

            $vouchers = $fund->vouchers()->orderBy('id')->get();

            $this->assertCount($budgetMatches ? 3 : 2, $vouchers, $variant);
            $this->assertSame($vouchers->modelKeys(), $response->json('data.0.voucher_ids'));
            $this->assertCount(2, $vouchers->where('product_id', $product->id));

            foreach ($vouchers->where('product_id', $product->id) as $voucher) {
                $this->assertEquals(10, $voucher->amount);
            }

            if ($budgetMatches) {
                $this->assertEquals(40, $vouchers->whereNull('product_id')->sole()->amount);
            } else {
                $this->assertCount(0, $vouchers->whereNull('product_id'));
            }
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testMissingOrInvalidRequiredFormulaInputProducesReadableResult(): void
    {
        foreach (['missing' => null, 'invalid' => 'invalid'] as $variant => $value) {
            ['fund' => $fund, 'profile' => $profile, 'employee' => $employee] = $this->makeAwardFixture();
            $multiplier = $this->makeProfileRecordType($fund->organization, ['type' => RecordType::TYPE_NUMBER]);
            $multiplier->translateOrNew(app()->getLocale())->fill(['name' => 'Award units'])->save();

            $fund->fund_formulas()->delete();

            $fund->fund_formulas()->create([
                'type' => FundFormula::TYPE_MULTIPLY,
                'amount' => 25,
                'record_type_key' => $multiplier->key,
            ]);

            if ($value !== null) {
                $this->makeImportedAwardRecord($profile, $multiplier, $value);
            }

            foreach ([true, false] as $preview) {
                $response = $this
                    ->apiStoreVouchersFromProfilesRequest($fund->organization, [
                        'fund_ids' => [$fund->id],
                        'profile_ids' => [$profile->id],
                    ], $employee->identity, $preview)
                    ->assertOk()
                    ->assertJsonPath('data.0.status', 'skipped');

                $this->assertCount(1, $response->json('data.0.errors'), $variant);
                $this->assertStringContainsString('Award units', $response->json('data.0.errors.0'));
                $this->assertNotEmpty($response->json('data.0.diagnostic_id'));
            }

            $this->assertFalse($fund->vouchers()->exists());
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProfileAwardValidatesAggregateBudgetAndProductStock(): void
    {
        foreach (['budget', 'stock'] as $variant) {
            ['fund' => $fund, 'profile' => $profile, 'number' => $number, 'employee' => $employee] =
                $this->makeAwardFixture();

            $fund->fund_formulas()->delete();

            if ($variant === 'budget') {
                $this->setTestFundBudget($fund, 40);

                $fund->fund_formulas()->create([
                    'type' => FundFormula::TYPE_MULTIPLY_VOUCHERS,
                    'amount' => 25,
                    'record_type_key' => $number->key,
                ]);
            } else {
                $provider = $this->makeTestProviderOrganization($this->makeIdentity($this->makeUniqueEmail()));
                $product = $this->makeTestProduct($provider, attributes: ['total_amount' => 1]);

                $this->addProductToFund($fund, $product, true);

                $fund->fund_formula_products()->createMany([
                    ['product_id' => $product->id, 'price' => 10],
                    ['product_id' => $product->id, 'price' => 10],
                ]);
            }

            $response = $this
                ->apiStoreVouchersFromProfilesRequest($fund->organization, [
                    'fund_ids' => [$fund->id],
                    'profile_ids' => [$profile->id],
                ], $employee->identity)
                ->assertOk()
                ->assertJsonPath('data.0.status', 'failed');

            $this->assertNotEmpty($response->json('data.0.errors'), $variant);

            $this->assertContains(
                $variant === 'budget' ?
                    trans('validation.voucher_generator.budget_exceeded') :
                    trans('validation.in', ['attribute' => 'vouchers.0.product_id']),
                $response->json('data.0.errors'),
                $variant,
            );

            $this->assertNotEmpty($response->json('data.0.diagnostic_id'), $variant);
            $this->assertSame([], $response->json('data.0.voucher_ids'), $variant);
            $this->assertFalse($fund->vouchers()->exists(), $variant);
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherWriteFailureRollsBackOnlyItsProfileFundPair(): void
    {
        ['fund' => $fund, 'profile' => $failed, 'number' => $number, 'employee' => $employee] =
            $this->makeAwardFixture();

        $successful = $this->makeAwardProfile($fund, $number);

        $fund->fund_formulas()->create([
            'type' => FundFormula::TYPE_MULTIPLY_VOUCHERS,
            'amount' => 25,
            'record_type_key' => $number->key,
        ]);

        $firstVoucherId = null;
        $contexts = [];
        $logger = Log::getFacadeRoot();
        $diagnostics = Mockery::mock(LoggerInterface::class);

        $diagnostics
            ->shouldReceive('log')
            ->andReturnUsing(function (string $level, string $message, array $context) use (&$contexts): void {
                $contexts[] = $context;
            });

        Log::partialMock()->shouldReceive('channel')->with('voucher_awards')->andReturn($diagnostics);
        $dispatcher = Voucher::getEventDispatcher();
        Voucher::setEventDispatcher(clone $dispatcher);

        try {
            Voucher::creating(function (Voucher $voucher) use ($fund, $failed, &$firstVoucherId): void {
                if ($voucher->fund_id !== $fund->id || $voucher->identity_id !== $failed->identity_id) {
                    return;
                }

                $firstVoucherId = $fund->vouchers()->where('identity_id', $failed->identity_id)->value('id');

                if ($firstVoucherId !== null) {
                    throw new RuntimeException('Injected second voucher failure.');
                }
            });

            $response = $this
                ->apiStoreVouchersFromProfilesRequest($fund->organization, [
                    'fund_ids' => [$fund->id],
                    'profile_ids' => [$failed->id, $successful->id],
                ], $employee->identity)
                ->assertOk();
        } finally {
            Voucher::setEventDispatcher($dispatcher);
            Log::swap($logger);
        }

        $this->assertNotNull($firstVoucherId);
        $this->assertDatabaseMissing('vouchers', ['id' => $firstVoucherId]);
        $this->assertFalse($fund->vouchers()->where('identity_id', $failed->identity_id)->exists());

        $response
            ->assertJsonPath('data.0.status', 'failed')
            ->assertJsonPath('data.0.voucher_ids', [])
            ->assertJsonPath('data.1.status', 'created');

        $vouchers = $fund->vouchers()->where('identity_id', $successful->identity_id)->orderBy('id')->get();

        $this->assertCount(3, $vouchers);
        $this->assertEquals([30, 25, 25], $vouchers->pluck('amount')->all());
        $this->assertSame($vouchers->modelKeys(), $response->json('data.1.voucher_ids'));
        $this->assertCount(1, $contexts);
        $this->assertSame($response->json('data.0.diagnostic_id'), $contexts[0]['diagnostic_id']);
        $this->assertSame('award_failed', $contexts[0]['error_code']);
        $this->assertSame($fund->id, $contexts[0]['fund_id']);
        $this->assertSame($failed->id, $contexts[0]['profile_id']);
        $this->assertSame($failed->identity_id, $contexts[0]['identity_id']);
    }

    /**
     * @param Fund $fund
     * @param Profile $profile
     * @param Employee $employee
     * @return void
     */
    private function assertProfileAwardAccessDenied(Fund $fund, Profile $profile, Employee $employee): void
    {
        $this
            ->apiGetProfileVoucherCandidateIdsRequest($fund->organization, $employee->identity)
            ->assertForbidden();

        foreach ([true, false] as $preview) {
            $this
                ->apiStoreVouchersFromProfilesRequest($fund->organization, [
                    'fund_ids' => [$fund->id],
                    'profile_ids' => [$profile->id],
                ], $employee->identity, $preview)
                ->assertForbidden();
        }

        $this->assertFalse($fund->vouchers()->exists());
    }

    /**
     * @throws Throwable
     * @return array{fund: Fund, profile: Profile, number: RecordType, employee: Employee}
     */
    private function makeAwardFixture(): array
    {
        $this->travelTo('2026-11-13 12:00:00');

        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()), [
            'is_sponsor' => true,
            'allow_profiles' => true,
            'bsn_enabled' => true,
            'allow_requester_imports' => true,
            'allow_vouchers_from_profiles' => true,
        ]);

        $fund = $this->makeTestFund($organization);

        $fund->criteria()->delete();
        $fund->fund_formulas()->delete();

        $number = $this->makeProfileRecordType($organization, [
            'type' => RecordType::TYPE_NUMBER,
            'requester_imports' => true,
        ]);

        $fund->criteria()->create([
            'record_type_key' => $number->key,
            'operator' => '>=',
            'value' => '1',
            'show_attachment' => false,
        ]);

        $fund->fund_formulas()->create(['type' => FundFormula::TYPE_FIXED, 'amount' => 30]);

        $employee = $this->makeTestEmployeeWithPermissions($organization, [Permission::MANAGE_VOUCHERS]);
        $profile = $this->makeAwardProfile($fund, $number);

        return compact('fund', 'profile', 'number', 'employee');
    }

    /**
     * @param Fund $fund
     * @param RecordType $number
     * @throws Throwable
     * @return Profile
     */
    private function makeAwardProfile(Fund $fund, RecordType $number): Profile
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail(), $this->makeUniqueBsn());
        $profile = $fund->organization->findOrMakeProfile($identity);
        $row = $this->makeRequesterImportRow($fund->organization, ['bsn' => $identity->bsn]);

        $row->update(['profile_id' => $profile->id]);
        $this->makeImportedAwardRecord($profile, $number, '2');

        return $profile;
    }

    /**
     * @param Profile $profile
     * @param RecordType $type
     * @param string $value
     * @return ProfileRecord
     */
    private function makeImportedAwardRecord(Profile $profile, RecordType $type, string $value): ProfileRecord
    {
        $row = $profile->requester_import_rows()->sole();

        $origin = $row->records()->create([
            'organization_id' => $profile->organization_id,
            'record_type_key' => $type->key,
            'value' => $value,
            'synced_at' => Carbon::now(),
        ]);

        return $this->makeProfileRecord($profile, $type, $value, [
            'source' => ProfileRecord::SOURCE_REQUESTER_IMPORT,
            'origin_type' => $origin->getMorphClass(),
            'origin_id' => $origin->id,
        ]);
    }
}
