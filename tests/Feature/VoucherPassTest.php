<?php

namespace Tests\Feature;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Voucher;
use App\Modules\VoucherPass\Models\VoucherPass;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery\VerificationDirector;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\MakesTestVoucherPasses;
use Throwable;

class VoucherPassTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestVoucherPasses;

    /**
     * @throws Throwable
     * @return void
     */
    public function testPassPreviewDoesNotCreatePassOrCode(): void
    {
        ['organization' => $organization, 'owner' => $owner, 'target' => $target] = $this->makeVoucherPassFixture();

        $rows = [
            $this->makeVoucherPassData($owner, $owner),
            $this->makeVoucherPassData($owner, $target, ['external_id' => 'pass-2']),
        ];

        $before = $this->passStorageCounts();

        $response = $this
            ->apiStoreVoucherPassesRequest($organization, $rows, $organization->identity, validate: true)
            ->assertOk()
            ->assertJsonCount(2, 'data');

        foreach ($rows as $index => $row) {
            $response
                ->assertJsonPath("data.$index.index", $index)
                ->assertJsonPath("data.$index.action", 'create')
                ->assertJsonPath("data.$index.id", null)
                ->assertJsonPath("data.$index.code", null);

            $changes = collect($response->json("data.$index.changes"))->keyBy('field');

            $this->assertSame([
                'field' => 'target_bsn',
                'previous_value' => null,
                'value' => $row['target_bsn'],
                'action' => 'add',
            ], $changes['target_bsn']);

            $this->assertSame(['Family'], $changes['type_names']['value']);
        }

        $this->assertSame($before, $this->passStorageCounts());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testImportsSelfAndSharedPassesWithoutVouchers(): void
    {
        ['organization' => $organization, 'type' => $type, 'owner' => $owner, 'target' => $target] =
            $this->makeVoucherPassFixture();

        $this->assertSame(0, Voucher::whereIn('identity_id', [$owner->id, $target->id])->count());

        $response = $this
            ->apiStoreVoucherPassesRequest($organization, [
                $this->makeVoucherPassData($owner, $owner),
                $this->makeVoucherPassData($owner, $target, ['external_id' => 'pass-2']),
            ], $organization->identity)
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $codes = [];

        foreach ([$owner, $target] as $index => $recipient) {
            $response->assertJsonPath("data.$index.status", 'created');
            $pass = VoucherPass::findOrFail($response->json("data.$index.id"));
            $codes[] = $pass->code;

            $this->assertSame($organization->id, $pass->organization_id);
            $this->assertSame($owner->id, $pass->owner_identity_id);
            $this->assertSame($recipient->id, $pass->target_identity_id);
            $this->assertSame([$type->id], $pass->types->modelKeys());
            $this->assertSame([], $pass->explicit_funds->modelKeys());
            $this->assertSame($pass->code, $response->json("data.$index.code"));
            $this->assertMatchesRegularExpression('/^[A-F0-9]{4}(?:-[A-F0-9]{4}){3}$/', $pass->code);
        }

        $this->assertNotSame($codes[0], $codes[1]);
        $this->assertSame(0, Voucher::whereIn('identity_id', [$owner->id, $target->id])->count());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUidPatchReplacesMutableValuesAndPreservesCode(): void
    {
        $this->freezeTime();
        ['organization' => $organization, 'fund' => $fund, 'owner' => $owner, 'target' => $target] =
            $this->makeVoucherPassFixture();

        $data = $this->makeVoucherPassData($owner, $target, ['fund_ids' => [$fund->id]]);
        $pass = $this->importVoucherPass($organization, $data);
        $code = $pass->code;
        $createdAt = $pass->created_at->toDateTimeString();
        $recipient = $this->makeVoucherPassParty($organization);
        $otherFund = $this->makeTestFund($organization);
        $otherType = $this->makeVoucherPassType($organization, 'Helper', [$otherFund->id]);

        $patch = [
            ...$data,
            'target_bsn' => $recipient->bsn,
            'print_label' => 'Helper budget',
            'state' => 'deactivated',
            'print_requested' => false,
            'type_names' => ['Helper'],
            'fund_ids' => [$otherFund->id],
        ];

        $this->travel(1)->minute();

        $this
            ->apiStoreVoucherPassesRequest($organization, [$patch], $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'updated')
            ->assertJsonPath('data.0.id', $pass->id)
            ->assertJsonPath('data.0.code', $code);

        $pass->refresh();
        $updatedAt = $pass->updated_at->toDateTimeString();

        $this->assertSame($owner->id, $pass->owner_identity_id);
        $this->assertSame($recipient->id, $pass->target_identity_id);
        $this->assertSame('Helper budget', $pass->print_label);
        $this->assertSame('deactivated', $pass->state);
        $this->assertFalse($pass->print_requested);
        $this->assertSame([$otherType->id], $pass->types->modelKeys());
        $this->assertSame([$otherFund->id], $pass->explicit_funds->modelKeys());
        $this->assertSame($createdAt, $pass->created_at->toDateTimeString());
        $this->assertNotSame($createdAt, $updatedAt);

        $this->travel(1)->minute();

        $this
            ->apiStoreVoucherPassesRequest($organization, [$patch], $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'unchanged')
            ->assertJsonPath('data.0.id', $pass->id)
            ->assertJsonPath('data.0.code', $code)
            ->assertJsonPath('data.0.changes', []);

        $this->assertSame($updatedAt, $pass->refresh()->updated_at->toDateTimeString());
        $this->assertSame(1, $organization->voucher_passes()->count());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testMissingUidCreatesNewPassAndUidIsOrganizationScoped(): void
    {
        ['organization' => $organization, 'owner' => $owner, 'target' => $target] = $this->makeVoucherPassFixture();
        $data = $this->makeVoucherPassData($owner, $target, ['external_id' => null]);
        $first = $this->importVoucherPass($organization, $data);
        $second = $this->importVoucherPass($organization, $data);

        $this->assertNotSame($first->id, $second->id);
        $this->assertNotSame($first->code, $second->code);
        $this->assertNull($first->external_id);
        $this->assertNull($second->external_id);

        $firstUid = $this->importVoucherPass($organization, [...$data, 'external_id' => 'shared-uid']);

        ['organization' => $other, 'owner' => $otherOwner, 'target' => $otherTarget] = $this->makeVoucherPassFixture();

        $secondUid = $this->importVoucherPass(
            $other,
            $this->makeVoucherPassData($otherOwner, $otherTarget, ['external_id' => 'shared-uid']),
        );

        $this->assertNotSame($firstUid->id, $secondUid->id);
        $this->assertSame($organization->id, $firstUid->organization_id);
        $this->assertSame($other->id, $secondUid->organization_id);
        $this->assertSame('shared-uid', $secondUid->external_id);
        $this->assertSame($firstUid->getAttributes(), $firstUid->fresh()->getAttributes());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPrintLabelMayBeClearedWithoutChangingPassCode(): void
    {
        ['organization' => $organization, 'owner' => $owner, 'target' => $target] = $this->makeVoucherPassFixture();
        $data = $this->makeVoucherPassData($owner, $target);
        $pass = $this->importVoucherPass($organization, $data);
        $code = $pass->code;

        foreach ([null, 'Restored label', '', 'Final label'] as $label) {
            $this
                ->apiUpdateVoucherPassRequest($organization, $pass, [
                    ...$data,
                    'print_label' => $label,
                ], $organization->identity)
                ->assertOk()
                ->assertJsonPath('data.print_label', $label ?? '')
                ->assertJsonPath('data.code', $code);

            $this->assertSame($label ?? '', $pass->refresh()->print_label);
        }

        $this
            ->apiUpdateVoucherPassRequest(
                $organization,
                $pass,
                Arr::except($data, 'print_label'),
                $organization->identity,
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('print_label');

        $this
            ->apiStoreVoucherPassesRequest($organization, [Arr::except($data, 'print_label')], $organization->identity)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rows.0.print_label');

        $this->assertSame('Final label', $pass->refresh()->print_label);
        $this->assertSame($code, $pass->code);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPassStateAndPrintIntentAreIndependent(): void
    {
        ['organization' => $organization, 'owner' => $owner, 'target' => $target] = $this->makeVoucherPassFixture();
        $data = $this->makeVoucherPassData($owner, $target);
        $pass = $this->importVoucherPass($organization, $data);
        $code = $pass->code;

        foreach (['active', 'deactivated'] as $state) {
            foreach ([false, true] as $printRequested) {
                $this
                    ->apiUpdateVoucherPassRequest($organization, $pass, [
                        ...$data,
                        'state' => $state,
                        'print_requested' => $printRequested,
                    ], $organization->identity)
                    ->assertOk()
                    ->assertJsonPath('data.state', $state)
                    ->assertJsonPath('data.print_requested', $printRequested)
                    ->assertJsonPath('data.code', $code);

                $this->assertSame($state, $pass->refresh()->state);
                $this->assertSame($printRequested, $pass->print_requested);
            }
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectsOwnerOrEstablishedUidChanges(): void
    {
        ['organization' => $organization, 'owner' => $owner, 'target' => $target] = $this->makeVoucherPassFixture();
        $data = $this->makeVoucherPassData($owner, $target);
        $pass = $this->importVoucherPass($organization, $data);
        $before = $pass->getAttributes();
        $types = $pass->types->modelKeys();

        foreach (['owner_bsn' => $target->bsn, 'external_id' => 'replacement-uid'] as $field => $value) {
            $this
                ->apiUpdateVoucherPassRequest($organization, $pass, [
                    ...$data,
                    $field => $value,
                    'print_label' => 'Must not be saved',
                ], $organization->identity)
                ->assertUnprocessable()
                ->assertJsonValidationErrors($field);

            $this->assertSame($before, $pass->refresh()->getAttributes());
            $this->assertSame($types, $pass->types->modelKeys());
        }

        $this
            ->apiUpdateVoucherPassRequest($organization, $pass, [
                ...$data,
                'external_id' => null,
            ], $organization->identity)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('external_id');

        $this->assertSame($before, $pass->refresh()->getAttributes());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectsPartiesWithoutOwnSynchronizedImportProfiles(): void
    {
        ['organization' => $organization, 'owner' => $owner, 'target' => $target] = $this->makeVoucherPassFixture();
        ['organization' => $foreign, 'owner' => $foreignOwner] = $this->makeVoucherPassFixture();
        $unsynchronized = $this->makeIdentity($this->makeUniqueEmail(), $this->makeUniqueBsn());
        $this->makeRequesterImportRow($organization, ['bsn' => $unsynchronized->bsn]);
        $missingBsn = $this->makeUniqueBsn();
        $data = $this->makeVoucherPassData($owner, $target);
        $before = $this->passStorageCounts();
        $identities = Identity::count();

        $variants = [
            'missing' => $missingBsn,
            'unsynchronized' => $unsynchronized->bsn,
            'foreign' => $foreignOwner->bsn,
        ];

        foreach ($variants as $bsn) {
            foreach (['owner_bsn', 'target_bsn'] as $field) {
                $this->assertPassRowRejected($organization, [...$data, $field => $bsn], $field);
            }
        }

        $foreignProfile = $foreign->findOrMakeProfile($foreignOwner);
        $mislinked = $this->makeRequesterImportRow($organization, ['bsn' => $foreignOwner->bsn]);
        $mislinked->update(['profile_id' => $foreignProfile->id]);

        foreach (['owner_bsn', 'target_bsn'] as $field) {
            $this->assertPassRowRejected($organization, [...$data, $field => $foreignOwner->bsn], $field);
        }

        $this->assertSame($identities, Identity::count());
        $this->assertSame($before, $this->passStorageCounts());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectsDuplicateBatchUidAndSubmittedCode(): void
    {
        ['organization' => $organization, 'owner' => $owner, 'target' => $target] = $this->makeVoucherPassFixture();
        $data = $this->makeVoucherPassData($owner, $target);
        $before = $this->passStorageCounts();

        foreach ([false, true] as $validate) {
            $this
                ->apiStoreVoucherPassesRequest($organization, [$data, $data], $organization->identity, $validate)
                ->assertUnprocessable()
                ->assertJsonValidationErrors('rows.1.external_id');

            $this
                ->apiStoreVoucherPassesRequest($organization, [[
                    ...$data,
                    'code' => 'AAAA-BBBB-CCCC-DDDD',
                ]], $organization->identity, $validate)
                ->assertUnprocessable()
                ->assertJsonValidationErrors('rows.0.code');
        }

        $this->assertSame($before, $this->passStorageCounts());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testStoresTypeReferencesAndExplicitFundOverrideSeparately(): void
    {
        ['organization' => $organization, 'fund' => $fund, 'type' => $type, 'owner' => $owner, 'target' => $target] =
            $this->makeVoucherPassFixture();

        $otherFund = $this->makeTestFund($organization);
        $otherType = $this->makeVoucherPassType($organization, 'Helper', [$otherFund->id]);
        $override = $this->makeTestFund($organization);

        $data = $this->makeVoucherPassData($owner, $target, [
            'type_names' => ['Family', 'Helper'],
            'fund_ids' => [$override->id],
        ]);

        $pass = $this->importVoucherPass($organization, $data);

        $listed = $this
            ->apiGetVoucherPassesRequest($organization, $organization->identity)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.fund_ids', [$override->id]);

        $types = collect($listed->json('data.0.types'))->keyBy('id');
        $this->assertSame([$fund->id], $types[$type->id]['fund_ids']);
        $this->assertSame([$otherFund->id], $types[$otherType->id]['fund_ids']);

        $this
            ->apiUpdateVoucherPassRequest($organization, $pass, [
                ...$data,
                'fund_ids' => [],
            ], $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.fund_ids', [])
            ->assertJsonCount(2, 'data.types');

        $this->assertEqualsCanonicalizing([$type->id, $otherType->id], $pass->refresh()->types->modelKeys());
        $this->assertSame([], $pass->explicit_funds->modelKeys());
        $this->assertSame([$fund->id], $type->refresh()->funds->modelKeys());
        $this->assertSame([$otherFund->id], $otherType->refresh()->funds->modelKeys());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testExplicitFundOverrideDoesNotBypassTypeValidation(): void
    {
        ['organization' => $organization, 'fund' => $fund, 'owner' => $owner, 'target' => $target] =
            $this->makeVoucherPassFixture();

        $data = $this->makeVoucherPassData($owner, $target, [
            'type_names' => ['Unknown'],
            'fund_ids' => [$fund->id],
        ]);

        $before = $this->passStorageCounts();
        $this->assertPassRowRejected($organization, $data, 'type_names.0');
        $this->assertSame($before, $this->passStorageCounts());

        $pass = $this->importVoucherPass($organization, [...$data, 'type_names' => []]);

        $this->assertSame([], $pass->types->modelKeys());
        $this->assertSame([$fund->id], $pass->explicit_funds->modelKeys());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectsForeignOrEmptyFundSelection(): void
    {
        ['organization' => $organization, 'owner' => $owner, 'target' => $target] = $this->makeVoucherPassFixture();
        ['fund' => $foreignFund] = $this->makeVoucherPassFixture();
        $this->makeVoucherPassType($organization, 'Foreign funds', [$foreignFund->id]);
        $this->makeVoucherPassType($organization, 'No funds', []);
        $data = $this->makeVoucherPassData($owner, $target);
        $pass = $this->importVoucherPass($organization, $data);
        $before = $pass->getAttributes();
        $types = $pass->types->modelKeys();
        $counts = $this->passStorageCounts();

        $variants = [
            [['type_names' => [], 'fund_ids' => [$foreignFund->id]], 'fund_ids.0'],
            [['type_names' => ['Foreign funds']], 'type_names.0'],
            [['type_names' => ['No funds']], 'type_names.0'],
            [['type_names' => [], 'fund_ids' => []], 'fund_ids'],
        ];

        foreach ($variants as [$attributes, $field]) {
            $this->assertPassRowRejected($organization, [...$data, ...$attributes], $field);

            $this
                ->apiUpdateVoucherPassRequest($organization, $pass, [
                    ...$data,
                    ...$attributes,
                ], $organization->identity)
                ->assertUnprocessable()
                ->assertJsonValidationErrors($field);

            $this->assertSame($before, $pass->refresh()->getAttributes());
            $this->assertSame($types, $pass->types->modelKeys());
            $this->assertSame([], $pass->explicit_funds->modelKeys());
        }

        $this->assertSame($counts, $this->passStorageCounts());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPassEndpointsRequireManageIdentitiesAndFeatureGates(): void
    {
        ['organization' => $organization, 'owner' => $owner, 'target' => $target] = $this->makeVoucherPassFixture();
        $manager = $this->makeTestEmployeeWithPermissions($organization, [Permission::MANAGE_IDENTITIES])->identity;
        $viewer = $this->makeTestEmployeeWithPermissions($organization, [Permission::VIEW_IDENTITIES])->identity;
        $data = $this->makeVoucherPassData($owner, $target);
        $pass = $this->importVoucherPass($organization, $data);

        $this->assertPassEndpointStatus($organization, $pass, $data, $manager, 200);
        $before = $pass->refresh()->getAttributes();
        $counts = $this->passStorageCounts();
        $rejectedData = [...$data, 'print_label' => 'Unauthorized edit'];
        $this->assertPassEndpointStatus($organization, $pass, $rejectedData, $viewer, 403);

        foreach (['is_sponsor', 'allow_voucher_passes', 'allow_profiles', 'bsn_enabled'] as $flag) {
            $organization->forceFill([$flag => false])->save();

            try {
                $this->assertPassEndpointStatus($organization, $pass, $rejectedData, $manager, 403);
            } finally {
                $organization->forceFill([$flag => true])->save();
            }
        }

        $this->assertSame($before, $pass->refresh()->getAttributes());
        $this->assertSame($counts, $this->passStorageCounts());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPassListingConfigurationAndUpdatesAreOrganizationScoped(): void
    {
        ['organization' => $organization, 'type' => $type, 'owner' => $owner, 'target' => $target] =
            $this->makeVoucherPassFixture();

        ['organization' => $foreign, 'type' => $foreignType, 'owner' => $foreignOwner, 'target' => $foreignTarget] =
            $this->makeVoucherPassFixture();

        $data = $this->makeVoucherPassData($owner, $target);
        $foreignData = $this->makeVoucherPassData($foreignOwner, $foreignTarget);
        $pass = $this->importVoucherPass($organization, $data);
        $foreignPass = $this->importVoucherPass($foreign, $foreignData);
        $before = $foreignPass->getAttributes();

        $this
            ->apiGetVoucherPassesRequest($organization, $organization->identity)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pass->id);

        $this
            ->apiGetVoucherPassConfigurationRequest($organization, $organization->identity)
            ->assertOk()
            ->assertJsonCount(1, 'data.types')
            ->assertJsonPath('data.types.0.id', $type->id);

        $this
            ->apiGetVoucherPassConfigurationRequest($foreign, $foreign->identity)
            ->assertOk()
            ->assertJsonCount(1, 'data.types')
            ->assertJsonPath('data.types.0.id', $foreignType->id);

        $this
            ->apiUpdateVoucherPassRequest($organization, $foreignPass, [
                ...$foreignData,
                'print_label' => 'Must not be saved',
            ], $organization->identity)
            ->assertForbidden();

        $this->apiGetVoucherPassesRequest($foreign, $organization->identity)->assertForbidden();
        $this->apiGetVoucherPassConfigurationRequest($foreign, $organization->identity)->assertForbidden();
        $this->assertSame($before, $foreignPass->refresh()->getAttributes());
        $this->assertSame([$foreignType->id], $foreignPass->types->modelKeys());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPassWriteFailureRollsBackOnlyItsRow(): void
    {
        ['organization' => $organization, 'fund' => $fund, 'owner' => $owner, 'target' => $target] =
            $this->makeVoucherPassFixture();

        $failed = $this->makeVoucherPassData($owner, $target, [
            'external_id' => 'fail-this-row',
            'fund_ids' => [$fund->id],
        ]);

        $valid = [...$failed, 'external_id' => 'keep-this-row'];
        $failedId = null;
        $partialLinksExist = false;
        $connection = DB::connection();
        $dispatcher = $connection->getEventDispatcher();
        $originalLogger = Log::getFacadeRoot();
        $logger = Log::spy();
        Log::shouldReceive('channel')->with('voucher_passes')->andReturnSelf();
        $connection->setEventDispatcher(clone $dispatcher);

        try {
            $connection->getEventDispatcher()->listen(QueryExecuted::class, function (QueryExecuted $event) use (
                $organization,
                &$failedId,
                &$partialLinksExist,
            ) {
                if (!str_starts_with($event->sql, 'insert into `voucher_pass_funds`')) {
                    return;
                }

                $id = $organization->voucher_passes()->where('external_id', 'fail-this-row')->value('id');

                if ($id === null) {
                    return;
                }

                $failedId = $id;
                $partialLinksExist = DB::table('voucher_pass_type_links')->where('voucher_pass_id', $id)->exists() &&
                    DB::table('voucher_pass_funds')->where('voucher_pass_id', $id)->exists();

                throw new RuntimeException('Injected pass write failure.');
            });

            $response = $this
                ->apiStoreVoucherPassesRequest($organization, [$failed, $valid], $organization->identity)
                ->assertOk()
                ->assertJsonCount(2, 'data')
                ->assertJsonPath('data.0.index', 0)
                ->assertJsonPath('data.0.status', 'failed')
                ->assertJsonPath('data.0.code', 'import_failed')
                ->assertJsonPath('data.1.index', 1)
                ->assertJsonPath('data.1.status', 'created');

            $diagnosticId = $response->json('data.0.diagnostic_id');
            $this->assertTrue(Str::isUuid($diagnosticId));
            $this->assertNotNull($failedId);
            $this->assertTrue($partialLinksExist);
            $this->assertDatabaseMissing('voucher_passes', ['id' => $failedId]);
            $this->assertDatabaseMissing('voucher_pass_type_links', ['voucher_pass_id' => $failedId]);
            $this->assertDatabaseMissing('voucher_pass_funds', ['voucher_pass_id' => $failedId]);

            $created = VoucherPass::findOrFail($response->json('data.1.id'));
            $this->assertSame('keep-this-row', $created->external_id);
            $this->assertSame($response->json('data.1.code'), $created->code);
            $this->assertCount(1, $created->types);
            $this->assertSame([$fund->id], $created->explicit_funds->modelKeys());
            $this->assertSame(1, $organization->voucher_passes()->count());

            /** @var VerificationDirector $verification */
            $verification = $logger->shouldHaveReceived('log');

            $verification
                ->withArgs(fn (string $level, string $message, array $context): bool =>
                    $level === 'error' &&
                    $context['diagnostic_id'] === $diagnosticId &&
                    $context['organization_id'] === $organization->id &&
                    $context['row_index'] === 0 &&
                    $context['exception_class'] === RuntimeException::class)
                ->once();
        } finally {
            $connection->setEventDispatcher($dispatcher);
            Log::swap($originalLogger);
        }
    }

    /**
     * @param Organization $organization
     * @param VoucherPass $pass
     * @param array $data
     * @param Identity $identity
     * @param int $status
     * @return void
     */
    private function assertPassEndpointStatus(
        Organization $organization,
        VoucherPass $pass,
        array $data,
        Identity $identity,
        int $status,
    ): void {
        $this->apiGetVoucherPassesRequest($organization, $identity)->assertStatus($status);
        $this->apiGetVoucherPassConfigurationRequest($organization, $identity)->assertStatus($status);
        $this->apiStoreVoucherPassesRequest($organization, [$data], $identity, validate: true)->assertStatus($status);
        $this->apiStoreVoucherPassesRequest($organization, [$data], $identity)->assertStatus($status);
        $this->apiUpdateVoucherPassRequest($organization, $pass, $data, $identity)->assertStatus($status);
    }

    /**
     * @param Organization $organization
     * @param array $data
     * @param string $field
     * @return void
     */
    private function assertPassRowRejected(Organization $organization, array $data, string $field): void
    {
        $this
            ->apiStoreVoucherPassesRequest($organization, [$data], $organization->identity, validate: true)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rows.0.' . $field);

        $response = $this
            ->apiStoreVoucherPassesRequest($organization, [$data], $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.0.index', 0)
            ->assertJsonPath('data.0.status', 'failed')
            ->assertJsonPath('data.0.code', 'validation_failed');

        $this->assertArrayHasKey($field, $response->json('data.0.errors'));
    }

    /**
     * @param Organization $organization
     * @param array $data
     * @return VoucherPass
     */
    private function importVoucherPass(Organization $organization, array $data): VoucherPass
    {
        $response = $this
            ->apiStoreVoucherPassesRequest($organization, [$data], $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'created');

        return VoucherPass::findOrFail($response->json('data.0.id'));
    }

    /**
     * @return array<string, int>
     */
    private function passStorageCounts(): array
    {
        return [
            'passes' => VoucherPass::count(),
            'types' => DB::table('voucher_pass_type_links')->count(),
            'funds' => DB::table('voucher_pass_funds')->count(),
        ];
    }
}
