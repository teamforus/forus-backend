<?php

namespace Tests\Feature;

use App\Enums\RecordValidationKey;
use App\Models\Employee;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\ProfileRecord;
use App\Models\RecordType;
use App\Modules\RequesterImport\Models\RequesterImportRecord;
use App\Modules\RequesterImport\Models\RequesterImportRow;
use App\Modules\RequesterImport\Services\RequesterImportProfileSyncService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\MakesTestRecords;
use Tests\Traits\MakesTestRequesterImports;
use Throwable;

class RequesterImportProfileSyncTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestRecords;
    use MakesTestRequesterImports;

    /**
     * @throws Throwable
     * @return void
     */
    public function testSyncPreviewDoesNotCreateIdentityProfileOrReceipts(): void
    {
        ['organization' => $organization, 'name' => $name] = $this->makeProfileSyncFixture();
        $data = $this->makeRequesterImportData($name);
        $row = $this->makeRequesterImportRow($organization, $data);
        $identities = Identity::count();
        $profiles = Profile::count();
        $history = ProfileRecord::count();
        $before = $row->records()->get()->toArray();

        $response = $this
            ->apiSyncRequesterImportRowsRequest($organization, [
                'row_ids' => [$row->id],
            ], $organization->identity, preview: true)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $row->id)->assertJsonPath('data.0.bsn', $row->bsn)
            ->assertJsonPath('data.0.action', 'create')->assertJsonPath('data.0.profile_id', null)
            ->assertJsonCount(2, 'data.0.changes')->assertJsonPath('data.0.errors', []);

        $changes = collect($response->json('data.0.changes'))->keyBy('record_type_key');

        $this->assertSame([
            'record_type_key' => $name->key, 'current_value' => null, 'value' => 'Taylor', 'action' => 'add',
        ], $changes[$name->key]);

        $this->assertSame($data['client_number'], $changes['client_number']['value']);
        $this->assertSame($identities, Identity::count());
        $this->assertSame($profiles, Profile::count());
        $this->assertSame($history, ProfileRecord::count());
        $this->assertNull($row->refresh()->profile_id);
        $this->assertSame($before, $row->records()->get()->toArray());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testImportedRowSynchronizesToNewIdentityAndProfile(): void
    {
        $this->freezeTime();
        ['organization' => $organization, 'name' => $name] = $this->makeProfileSyncFixture();
        $data = $this->makeRequesterImportData($name, ['bsn' => $this->makeUniqueBsn(leadingZero: true)]);

        $import = $this
            ->apiStoreRequesterImportRowsRequest($organization, [$data], $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'created');

        $row = RequesterImportRow::findOrFail($import->json('data.0.id'));
        $identities = Identity::count();
        $profiles = Profile::count();

        $response = $this
            ->apiSyncRequesterImportRowsRequest($organization, [
                'row_ids' => [$row->id],
            ], $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'created')
            ->assertJsonPath('data.0.id', $row->id)->assertJsonPath('data.0.records_synced', 2)
            ->assertJsonPath('data.0.records_updated', 2);

        $profile = $row->refresh()->profile;

        $this->assertNotNull($profile);
        $this->assertSame($organization->id, $profile->organization_id);
        $this->assertSame($profile->id, $response->json('data.0.profile_id'));
        $this->assertSame($profile->identity_id, $response->json('data.0.identity_id'));
        $this->assertSame($data['bsn'], $profile->identity->bsn);
        $this->assertSame(Identity::TYPE_PROFILE, $profile->identity->type);
        $this->assertSame($identities + 1, Identity::count());
        $this->assertSame($profiles + 1, Profile::count());

        foreach ($row->records as $record) {
            $profileRecord = $profile->profile_records()->where('record_type_id', $record->record_type->id)->sole();

            $this->assertImportedProfileRecord(
                $profileRecord,
                $record,
                $organization->findEmployee($organization->identity),
            );

            $this->assertSame(now()->toDateTimeString(), $record->synced_at->toDateTimeString());
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSyncReusesExistingIdentityAndOrganizationProfile(): void
    {
        ['organization' => $organization, 'name' => $name] = $this->makeProfileSyncFixture();
        $row = $this->makeRequesterImportRow($organization, $this->makeRequesterImportData($name));
        $identity = $this->makeIdentity($this->makeUniqueEmail(), $row->bsn);
        $profile = $organization->findOrMakeProfile($identity);
        $identities = Identity::count();
        $profiles = Profile::count();

        $this->apiSyncRequesterImportRowsRequest($organization, ['row_ids' => [$row->id]], $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'updated')
            ->assertJsonPath('data.0.profile_id', $profile->id)->assertJsonPath('data.0.identity_id', $identity->id)
            ->assertJsonPath('data.0.records_updated', 2);

        $this->assertSame($profile->id, $row->refresh()->profile_id);
        $this->assertSame($identities, Identity::count());
        $this->assertSame($profiles, Profile::count());
        $this->assertSame('Taylor', $profile->profile_records()->where('record_type_id', $name->id)->sole()->value);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testDefaultSyncCopiesOnlyUnsynchronizedVersions(): void
    {
        $this->freezeTime();
        ['organization' => $organization, 'name' => $name, 'address' => $address] = $this->makeProfileSyncFixture();
        $row = $this->makeRequesterImportRow($organization, $this->makeRequesterImportData($name));
        $profile = $this->makeLinkedImportProfile($row);
        $oldReceipt = now()->toDateTimeString();
        $row->records()->update(['synced_at' => now()]);
        $this->travel(1)->minutes();
        $edited = $this->makeProfileRecord($profile, $name, 'Profile edit');

        $new = $row->records()->create([
            'organization_id' => $organization->id, 'record_type_key' => $address->key, 'value' => 'New street',
        ]);

        $recordDates = $row->records()->pluck('updated_at', 'id')->all();
        $rowDate = $row->updated_at;
        $this->travel(1)->minutes();

        $this
            ->apiSyncRequesterImportRowsRequest($organization, ['row_ids' => [$row->id]], $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.0.records_synced', 1)
            ->assertJsonPath('data.0.records_updated', 1);

        $this->assertSame(
            [$edited->id],
            $profile->profile_records()->where('record_type_id', $name->id)->pluck('id')->all(),
        );

        $this->assertSame('Profile edit', $edited->refresh()->value);

        $this->assertSame(
            'New street',
            $profile->profile_records()->where('record_type_id', $address->id)->sole()->value,
        );

        $this->assertEquals($recordDates, $row->records()->pluck('updated_at', 'id')->all());
        $this->assertEquals($rowDate, $row->refresh()->updated_at);
        $this->assertSame(now()->toDateTimeString(), $new->refresh()->synced_at->toDateTimeString());

        foreach ($row->records()->where('id', '!=', $new->id)->get() as $record) {
            $this->assertSame($oldReceipt, $record->synced_at->toDateTimeString());
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUpdateAllOverwritesProfileEditsAndAppliesExplicitClears(): void
    {
        $this->freezeTime();
        ['organization' => $organization, 'name' => $name, 'address' => $address] = $this->makeProfileSyncFixture();
        $row = $this->makeRequesterImportRow($organization, $this->makeRequesterImportData($name, [$address->key => '']));

        $profile = $this->makeLinkedImportProfile($row);
        $this->makeProfileRecord($profile, $name, 'Profile edit');
        $this->makeProfileRecord($profile, $address, 'Old address');
        $row->records()->update(['synced_at' => now()]);
        $recordDates = $row->records()->pluck('updated_at', 'id')->all();
        $rowDate = $row->updated_at;
        $this->travel(1)->minutes();

        $this->apiSyncRequesterImportRowsRequest($organization, [
            'row_ids' => [$row->id], 'update_all' => true,
        ], $organization->identity)->assertOk()->assertJsonPath('data.0.records_synced', 3)
            ->assertJsonPath('data.0.records_updated', 3);

        $this->assertSame(['Taylor', 'Profile edit'], $profile->profile_records()
            ->where('record_type_id', $name->id)->pluck('value')->all());

        $this->assertSame(['', 'Old address'], $profile->profile_records()
            ->where('record_type_id', $address->id)->pluck('value')->all());

        $this->assertEquals($recordDates, $row->records()->pluck('updated_at', 'id')->all());
        $this->assertEquals($rowDate, $row->refresh()->updated_at);

        foreach ($row->records as $record) {
            $current = $profile->profile_records()->where('record_type_id', $record->record_type->id)->firstOrFail();

            $this->assertImportedProfileRecord($current, $record, $organization->findEmployee($organization->identity));
            $this->assertSame(now()->toDateTimeString(), $record->synced_at->toDateTimeString());
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testImportConfirmsEqualValueFromAnotherSourceOnce(): void
    {
        $this->freezeTime();
        ['organization' => $organization, 'name' => $name] = $this->makeProfileSyncFixture();
        $row = $this->makeRequesterImportRow($organization, ['bsn' => $this->makeUniqueBsn(), $name->key => 'Taylor']);
        $profile = $this->makeLinkedImportProfile($row);
        $old = $this->makeProfileRecord($profile, $name, 'Taylor');
        $this->travel(1)->seconds();
        $payload = ['row_ids' => [$row->id], 'update_all' => true];

        $this
            ->apiSyncRequesterImportRowsRequest($organization, $payload, $organization->identity, preview: true)
            ->assertOk()
            ->assertJsonPath('data.0.changes', [[
                'record_type_key' => $name->key, 'current_value' => 'Taylor',
                'value' => 'Taylor', 'action' => 'confirm_source',
            ]]);

        $this->assertSame([$old->id], $profile->profile_records()->pluck('id')->all());

        $this
            ->apiSyncRequesterImportRowsRequest($organization, $payload, $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.0.records_updated', 1);

        $current = $profile->profile_records()->firstOrFail();
        $employee = $organization->findEmployee($organization->identity);

        $this->assertImportedProfileRecord($current, $row->records()->sole(), $employee);
        $this->assertSame(2, $profile->profile_records()->count());

        $this
            ->apiSyncRequesterImportRowsRequest($organization, $payload, $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'unchanged')
            ->assertJsonPath('data.0.records_updated', 0);

        $this->assertSame([$current->id, $old->id], $profile->profile_records()->pluck('id')->all());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testEqualNonImportEditDoesNotReplaceAttribution(): void
    {
        ['organization' => $organization, 'name' => $name] = $this->makeProfileSyncFixture();
        $row = $this->makeRequesterImportRow($organization, ['bsn' => $this->makeUniqueBsn(), $name->key => 'Taylor']);
        $profile = $this->makeLinkedImportProfile($row);
        $origin = $row->records()->sole();
        $employee = $organization->findEmployee($organization->identity);

        $record = $this->makeProfileRecord($profile, $name, 'Taylor', [
            'source' => ProfileRecord::SOURCE_REQUESTER_IMPORT, 'employee_id' => $employee->id,
            'origin_type' => $origin->getMorphClass(), 'origin_id' => $origin->id,
        ]);

        $before = $record->refresh()->getAttributes();

        $this->assertTrue($profile->updateRecords([$name->key => 'Taylor'])->isEmpty());
        $this->assertTrue($profile->updateRecords([$name->key => 'Taylor'], $employee)->isEmpty());
        $this->assertSame([$record->id], $profile->profile_records()->pluck('id')->all());
        $this->assertSame($before, $record->refresh()->getAttributes());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSyncCopiesOnlyTypesEnabledForImportsAndProfiles(): void
    {
        ['organization' => $organization, 'name' => $name, 'address' => $address] = $this->makeProfileSyncFixture();

        $row = $this->makeRequesterImportRow(
            $organization,
            $this->makeRequesterImportData($name, [$address->key => 'Street']),
        );

        $profile = $this->makeLinkedImportProfile($row);
        $oldName = $this->makeProfileRecord($profile, $name, 'Keep name');
        $oldAddress = $this->makeProfileRecord($profile, $address, 'Keep street');
        $name->update(['profile_records' => false]);
        $address->update(['requester_imports' => false]);

        $this
            ->apiSyncRequesterImportRowsRequest($organization, ['row_ids' => [$row->id]], $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.0.records_synced', 1)
            ->assertJsonPath('data.0.records_updated', 1);

        $client = $row->records()->where('record_type_key', 'client_number')->sole();

        $this->assertSame($client->value, $profile->profile_records()
            ->where('record_type_id', $client->record_type->id)->sole()->value);

        $this->assertNotNull($client->synced_at);

        $this->assertSame(
            [$oldName->id],
            $profile->profile_records()->where('record_type_id', $name->id)->pluck('id')->all(),
        );

        $this->assertSame(
            [$oldAddress->id],
            $profile->profile_records()->where('record_type_id', $address->id)->pluck('id')->all(),
        );

        $this->assertSame(
            0,
            $row->records()->where('record_type_key', '!=', 'client_number')->whereNotNull('synced_at')->count(),
        );

        $this->assertFalse($profile->profile_records()
            ->where('record_type_id', RecordType::findByKey(RecordType::KEY_BSN)->id)->exists());

        $this->assertSame($row->bsn, $profile->identity->bsn);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSyncRevalidatesRulesChangedAfterUpload(): void
    {
        ['organization' => $organization, 'name' => $name, 'address' => $address] = $this->makeProfileSyncFixture();

        $invalid = $this->makeRequesterImportRow(
            $organization,
            $this->makeRequesterImportData($name, [$address->key => 'A']),
        );

        $valid = $this->makeRequesterImportRow(
            $organization,
            $this->makeRequesterImportData($name, [$address->key => 'Street']),
        );

        $address->update(['validation_key' => RecordValidationKey::PERSON_NAME]);
        $payload = ['row_ids' => [$invalid->id, $valid->id]];

        $preview = $this
            ->apiSyncRequesterImportRowsRequest(
                $organization,
                $payload,
                $organization->identity,
                preview: true,
            )->assertOk()
            ->assertJsonPath('data.0.id', $invalid->id)
            ->assertJsonPath('data.0.action', 'blocked')
            ->assertJsonPath('data.0.code', 'validation_failed')
            ->assertJsonPath('data.1.action', 'create');

        $this->assertArrayHasKey('records.' . $address->key, $preview->json('data.0.errors'));

        $response = $this
            ->apiSyncRequesterImportRowsRequest($organization, $payload, $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.0.id', $invalid->id)
            ->assertJsonPath('data.0.status', 'failed')
            ->assertJsonPath('data.0.code', 'validation_failed')->assertJsonPath('data.1.id', $valid->id)
            ->assertJsonPath('data.1.status', 'created');

        $this->assertArrayHasKey('records.' . $address->key, $response->json('data.0.errors'));
        $this->assertNull($invalid->refresh()->profile_id);
        $this->assertNull(Identity::findByBsn($invalid->bsn));
        $this->assertSame(0, $invalid->records()->whereNotNull('synced_at')->count());
        $this->assertNotNull($valid->refresh()->profile_id);
        $this->assertSame(3, $valid->profile->profile_records()->count());
        $this->assertSame(3, $valid->records()->whereNotNull('synced_at')->count());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSyncRejectsConflictingProfileLink(): void
    {
        ['organization' => $organization, 'name' => $name] = $this->makeProfileSyncFixture();
        $row = $this->makeRequesterImportRow($organization, $this->makeRequesterImportData($name));
        $other = $this->makeIdentity($this->makeUniqueEmail(), $this->makeUniqueBsn());
        $wrongProfile = $organization->findOrMakeProfile($other);
        $this->makeProfileRecord($wrongProfile, $name, 'Keep this');
        $row->update(['profile_id' => $wrongProfile->id]);
        $identities = Identity::count();
        $profiles = Profile::count();
        $before = $wrongProfile->profile_records()->get()->toArray();

        foreach ([true, false] as $preview) {
            $this
                ->apiSyncRequesterImportRowsRequest(
                    $organization,
                    ['row_ids' => [$row->id]],
                    $organization->identity,
                    $preview,
                )->assertOk()
                ->assertJsonPath('data.0.' . ($preview ? 'action' : 'status'), $preview ? 'blocked' : 'failed')
                ->assertJsonPath('data.0.code', 'profile_mismatch');
        }

        $this->assertSame($identities, Identity::count());
        $this->assertSame($profiles, Profile::count());
        $this->assertSame($wrongProfile->id, $row->refresh()->profile_id);
        $this->assertSame($before, $wrongProfile->profile_records()->get()->toArray());
        $this->assertSame(0, $row->records()->whereNotNull('synced_at')->count());
        $this->assertNull(Identity::findByBsn($row->bsn));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSyncRejectsAmbiguousBsnOwnership(): void
    {
        ['organization' => $organization, 'name' => $name] = $this->makeProfileSyncFixture();
        $row = $this->makeRequesterImportRow($organization, $this->makeRequesterImportData($name));
        $owner = $this->makeIdentity($this->makeUniqueEmail(), $row->bsn);
        $other = $this->makeIdentity($this->makeUniqueEmail());
        $other->records()->create(['record_type_id' => $owner->record_bsn->record_type_id, 'value' => $row->bsn]);
        $profiles = Profile::count();

        foreach ([true, false] as $preview) {
            $this
                ->apiSyncRequesterImportRowsRequest(
                    $organization,
                    ['row_ids' => [$row->id]],
                    $organization->identity,
                    $preview,
                )
                ->assertOk()
                ->assertJsonPath('data.0.' . ($preview ? 'action' : 'status'), $preview ? 'blocked' : 'failed')
                ->assertJsonPath('data.0.code', 'identity_ambiguous');
        }

        $this->assertNull($row->refresh()->profile_id);
        $this->assertSame($profiles, Profile::count());
        $this->assertSame(0, $row->records()->whereNotNull('synced_at')->count());
        $this->assertSame($row->bsn, $owner->refresh()->bsn);
        $this->assertSame($row->bsn, $other->refresh()->bsn);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSyncWriteFailureRollsBackOnlyItsRow(): void
    {
        ['organization' => $organization, 'name' => $name] = $this->makeProfileSyncFixture();
        $failed = $this->makeRequesterImportRow($organization, $this->makeRequesterImportData($name));
        $valid = $this->makeRequesterImportRow($organization, $this->makeRequesterImportData($name));
        $identities = Identity::count();
        $profiles = Profile::count();
        $history = ProfileRecord::count();
        $clientTypeId = RecordType::findByKey('client_number')->id;
        $createdIdentityId = null;
        $createdProfileId = null;
        $createdRecordId = null;
        $dispatcher = ProfileRecord::getEventDispatcher();

        ProfileRecord::setEventDispatcher(clone $dispatcher);

        try {
            ProfileRecord::creating(function (ProfileRecord $record) use (
                $failed,
                $clientTypeId,
                &$createdIdentityId,
                &$createdProfileId,
                &$createdRecordId,
            ) {
                if ($record->record_type_id !== $clientTypeId || $record->profile->identity->bsn !== $failed->bsn) {
                    return;
                }

                $createdIdentityId = $record->profile->identity_id;
                $createdProfileId = $record->profile_id;
                $createdRecordId = $record->profile->profile_records()->sole()->id;

                throw new RuntimeException('Injected profile record failure.');
            });

            $this
                ->apiSyncRequesterImportRowsRequest($organization, [
                    'row_ids' => [$failed->id, $valid->id],
                ], $organization->identity)
                ->assertOk()
                ->assertJsonPath('data.0.id', $failed->id)
                ->assertJsonPath('data.0.status', 'failed')->assertJsonPath('data.0.code', 'sync_failed')
                ->assertJsonPath('data.1.id', $valid->id)->assertJsonPath('data.1.status', 'created')
                ->assertJsonPath('data.1.records_updated', 2);
        } finally {
            ProfileRecord::setEventDispatcher($dispatcher);
        }

        $this->assertNotNull($createdIdentityId);
        $this->assertNotNull($createdProfileId);
        $this->assertNotNull($createdRecordId);
        $this->assertDatabaseMissing('identities', ['id' => $createdIdentityId]);
        $this->assertDatabaseMissing('profiles', ['id' => $createdProfileId]);
        $this->assertDatabaseMissing('profile_records', ['id' => $createdRecordId]);
        $this->assertNull($failed->refresh()->profile_id);
        $this->assertSame(0, $failed->records()->whereNotNull('synced_at')->count());
        $this->assertNull(Identity::findByBsn($failed->bsn));
        $this->assertSame($identities + 1, Identity::count());
        $this->assertSame($profiles + 1, Profile::count());
        $this->assertSame($history + 2, ProfileRecord::count());
        $this->assertSame(2, $valid->records()->whereNotNull('synced_at')->count());
        $this->assertSame($valid->bsn, $valid->refresh()->profile->identity->bsn);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSyncIdSelectionPreservesEmptySubsetAndOmitted(): void
    {
        foreach (['empty', 'subset', 'omitted'] as $variant) {
            ['organization' => $organization, 'name' => $name] = $this->makeProfileSyncFixture();
            ['organization' => $other, 'name' => $otherName] = $this->makeProfileSyncFixture();

            $rows = collect(range(1, 3))->map(fn () =>
                $this->makeRequesterImportRow($organization, $this->makeRequesterImportData($name)));

            $foreign = $this->makeRequesterImportRow($other, $this->makeRequesterImportData($otherName));
            $stale = $this->makeRequesterImportRow($organization, $this->makeRequesterImportData($name));
            $stale->delete();

            $this
                ->apiGetRequesterImportSyncIdsRequest($organization, $organization->identity)
                ->assertOk()
                ->assertExactJson(['data' => $rows->pluck('id')->all()]);

            [$payload, $expectedIds] = match ($variant) {
                'empty' => [['row_ids' => []], []],
                'subset' => [['row_ids' => [$rows[1]->id, $foreign->id, $stale->id]], [$rows[1]->id]],
                'omitted' => [[], $rows->pluck('id')->all()],
            };

            foreach ([true, false] as $preview) {
                $response = $this->apiSyncRequesterImportRowsRequest(
                    $organization,
                    $payload,
                    $organization->identity,
                    $preview,
                )->assertOk();

                $this->assertSame($expectedIds, array_column($response->json('data'), 'id'), $variant);

                $this->assertSame(
                    $preview ? [] : $expectedIds,
                    RequesterImportRow::query()
                        ->where('organization_id', $organization->id)
                        ->whereNotNull('profile_id')
                        ->orderBy('id')
                        ->pluck('id')
                        ->all(),
                    $variant,
                );
            }

            $this->assertNull($foreign->refresh()->profile_id);
            $this->assertNull($stale->refresh()->profile_id);
            $this->assertSame(0, $foreign->records()->whereNotNull('synced_at')->count());
            $this->assertSame(0, $stale->records()->whereNotNull('synced_at')->count());
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSyncRejectsInvalidBatchIdentifiers(): void
    {
        ['organization' => $organization, 'name' => $name] = $this->makeProfileSyncFixture();
        $row = $this->makeRequesterImportRow($organization, $this->makeRequesterImportData($name));
        $identities = Identity::count();
        $profiles = Profile::count();

        foreach ([
            [[$row->id, $row->id], ['row_ids.0', 'row_ids.1']],
            [range(1, RequesterImportProfileSyncService::BATCH_SIZE + 1), ['row_ids']],
        ] as [$ids, $errors]) {
            foreach ([true, false] as $preview) {
                $this
                    ->apiSyncRequesterImportRowsRequest(
                        $organization,
                        ['row_ids' => $ids],
                        $organization->identity,
                        $preview,
                    )
                    ->assertUnprocessable()
                    ->assertJsonValidationErrors($errors);
            }
        }

        $this->assertSame($identities, Identity::count());
        $this->assertSame($profiles, Profile::count());
        $this->assertNull($row->refresh()->profile_id);
        $this->assertSame(0, $row->records()->whereNotNull('synced_at')->count());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSyncEndpointsRequireAuthorizedOrganizationManager(): void
    {
        ['organization' => $organization, 'name' => $name] = $this->makeProfileSyncFixture();
        $viewer = $this->makeTestEmployeeWithPermissions($organization, [Permission::VIEW_IDENTITIES])->identity;
        $other = $this->makeTestOrganization($this->makeIdentity());
        $row = $this->makeRequesterImportRow($organization, $this->makeRequesterImportData($name));
        $identities = Identity::count();
        $profiles = Profile::count();
        $before = $row->records()->get()->toArray();

        $callers = ['viewer' => $viewer, 'foreign' => $other->identity, 'disabled' => $organization->identity];

        foreach ($callers as $variant => $caller) {
            if ($variant === 'disabled') {
                $organization->forceFill(['allow_requester_imports' => false])->save();
            }

            $this->apiGetRequesterImportSyncIdsRequest($organization, $caller)->assertForbidden();

            foreach ([true, false] as $preview) {
                $this
                    ->apiSyncRequesterImportRowsRequest($organization, ['row_ids' => [$row->id]], $caller, $preview)
                    ->assertForbidden();
            }
        }

        $this->assertSame($identities, Identity::count());
        $this->assertSame($profiles, Profile::count());
        $this->assertNull($row->refresh()->profile_id);
        $this->assertSame($before, $row->records()->get()->toArray());
    }

    /**
     * @throws Throwable
     * @return array{
     *     organization: Organization, name: RecordType, address: RecordType, date: RecordType, choice: RecordType
     * }
     */
    private function makeProfileSyncFixture(): array
    {
        /** @var RecordType[] $fixture */
        $fixture = $this->makeRequesterImportFixture();

        foreach (['name', 'address', 'date', 'choice'] as $field) {
            $fixture[$field]->update(['profile_records' => true]);
        }

        RecordType::findByKey('client_number')->update(['profile_records' => true]);

        return $fixture;
    }

    /**
     * @param RequesterImportRow $row
     * @throws Throwable
     * @return Profile
     */
    private function makeLinkedImportProfile(RequesterImportRow $row): Profile
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail(), $row->bsn);
        $profile = $row->organization->findOrMakeProfile($identity);

        $row->update(['profile_id' => $profile->id]);

        return $profile;
    }

    /**
     * @param ProfileRecord $record
     * @param RequesterImportRecord $origin
     * @param Employee $employee
     * @return void
     */
    private function assertImportedProfileRecord(
        ProfileRecord $record,
        RequesterImportRecord $origin,
        Employee $employee,
    ): void {
        $this->assertSame($origin->value, $record->value);
        $this->assertSame(ProfileRecord::SOURCE_REQUESTER_IMPORT, $record->source);
        $this->assertSame($employee->id, $record->employee_id);
        $this->assertSame('requester_import_record', $record->origin_type);
        $this->assertSame($origin->id, $record->origin_id);
    }
}
