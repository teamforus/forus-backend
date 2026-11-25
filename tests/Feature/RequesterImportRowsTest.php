<?php

namespace Tests\Feature;

use App\Models\Identity;
use App\Models\Permission;
use App\Models\Profile;
use App\Modules\RequesterImport\Models\RequesterImportRecord;
use App\Modules\RequesterImport\Models\RequesterImportRow;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\MakesTestRequesterImports;
use Throwable;

class RequesterImportRowsTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestRequesterImports;

    /**
     * @throws Throwable
     * @return void
     */
    public function testPreviewReportsCreateWithoutSavingRows(): void
    {
        ['organization' => $organization, 'name' => $name] = $this->makeRequesterImportFixture();
        $data = $this->makeRequesterImportData($name);
        $rowCount = RequesterImportRow::withTrashed()->count();
        $recordCount = RequesterImportRecord::withTrashed()->count();
        $profileCount = Profile::count();

        $this->apiStoreRequesterImportRowsRequest($organization, [$data], $organization->identity, validate: true)
            ->assertOk()
            ->assertExactJson(['data' => [[
                'index' => 0,
                'id' => null,
                'action' => 'create',
                'changes' => [
                    ['record_type_key' => 'client_number', 'previous_value' => null,
                        'value' => $data['client_number'], 'action' => 'add'],
                    ['record_type_key' => $name->key, 'previous_value' => null,
                        'value' => 'Taylor', 'action' => 'add'],
                ],
            ]]]);

        $this->assertSame($rowCount, RequesterImportRow::withTrashed()->count());
        $this->assertSame($recordCount, RequesterImportRecord::withTrashed()->count());
        $this->assertSame($profileCount, Profile::count());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testImportsRowAndReturnsSavedChanges(): void
    {
        ['organization' => $organization, 'name' => $name] = $this->makeRequesterImportFixture();
        $data = $this->makeRequesterImportData($name);
        $profileCount = Profile::count();

        $response = $this
            ->apiStoreRequesterImportRowsRequest($organization, [$data], $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'created')
            ->assertJsonPath('data.0.index', 0);

        $row = RequesterImportRow::findOrFail($response->json('data.0.id'));
        $this->assertSame($organization->id, $row->organization_id);
        $this->assertSame($data['bsn'], $row->bsn);
        $this->assertNull($row->profile_id);
        $this->assertSame($profileCount, Profile::count());
        $this->assertCount(2, $row->records);
        $this->assertCount(2, $response->json('data.0.changes'));

        foreach ($response->json('data.0.changes') as $change) {
            $this->assertSame('add', $change['action']);
            $this->assertNull($change['previous_value']);
            $this->assertSame($data[$change['record_type_key']], $change['value']);
            $record = $row->records->firstWhere('record_type_key', $change['record_type_key']);

            $this->assertNotNull($record);
            $this->assertSame($change['value'], $record->value);
            $this->assertSame($organization->findEmployee($organization->identity)->id, $record->employee_id);
            $this->assertNull($record->synced_at);
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testMatchingPatchUpdatesExistingRowAndIdenticalPatchIsUnchanged(): void
    {
        ['organization' => $organization, 'name' => $name, 'address' => $address] = $this->makeRequesterImportFixture();
        $data = $this->makeRequesterImportData($name, [$address->key => 'Old street']);
        $row = $this->makeRequesterImportRow($organization, $data);
        $patch = [...$data, $address->key => 'New street'];

        $changes = [[
            'record_type_key' => $address->key,
            'previous_value' => 'Old street',
            'value' => 'New street',
            'action' => 'update',
        ]];

        $this
            ->apiStoreRequesterImportRowsRequest($organization, [$patch], $organization->identity, validate: true)
            ->assertOk()
            ->assertExactJson(['data' => [[
                'index' => 0, 'id' => $row->id, 'action' => 'update', 'changes' => $changes,
            ]]]);

        $this->assertSame('Old street', $row->records()->where('record_type_key', $address->key)->value('value'));

        $this
            ->apiStoreRequesterImportRowsRequest($organization, [$patch], $organization->identity)
            ->assertOk()
            ->assertExactJson(['data' => [[
                'index' => 0, 'id' => $row->id, 'status' => 'updated', 'changes' => $changes,
            ]]]);

        $this->assertSame('New street', $row->records()->where('record_type_key', $address->key)->value('value'));
        $recordIds = $row->records()->withTrashed()->pluck('id')->all();

        $this
            ->apiStoreRequesterImportRowsRequest($organization, [$patch], $organization->identity, validate: true)
            ->assertOk()
            ->assertJsonPath('data.0.action', 'unchanged')->assertJsonPath('data.0.changes', []);

        $this
            ->apiStoreRequesterImportRowsRequest($organization, [$patch], $organization->identity)
            ->assertOk()
            ->assertExactJson(['data' => [[
                'index' => 0, 'id' => $row->id, 'status' => 'unchanged', 'changes' => [],
            ]]]);

        $this->assertSame($recordIds, $row->records()->withTrashed()->pluck('id')->all());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectsExistingBsnWithoutMatchingClientNumber(): void
    {
        ['organization' => $organization, 'name' => $name] = $this->makeRequesterImportFixture();
        $data = $this->makeRequesterImportData($name);
        $row = $this->makeRequesterImportRow($organization, $data);
        $before = $row->records()->withTrashed()->get()->toArray();
        $missing = $data;

        unset($missing['client_number']);

        foreach ([$missing, [...$data, 'client_number' => 'different-client']] as $patch) {
            $this
                ->apiStoreRequesterImportRowsRequest($organization, [$patch], $organization->identity, validate: true)
                ->assertUnprocessable()
                ->assertJsonValidationErrors('rows.0.client_number');
        }

        $this->assertSame($before, $row->records()->withTrashed()->get()->toArray());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testClientNumberUniquenessIsOrganizationScoped(): void
    {
        ['organization' => $organization, 'name' => $name] = $this->makeRequesterImportFixture();
        ['organization' => $other, 'name' => $otherName] = $this->makeRequesterImportFixture();
        $data = $this->makeRequesterImportData($name);
        $this->makeRequesterImportRow($organization, $data);
        $collision = $this->makeRequesterImportData($name, ['client_number' => $data['client_number']]);

        $this
            ->apiStoreRequesterImportRowsRequest($organization, [$collision], $organization->identity, validate: true)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rows.0.client_number');

        $this
            ->apiStoreRequesterImportRowsRequest($organization, [$collision], $organization->identity)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rows.0.client_number');

        $otherData = $this->makeRequesterImportData($otherName, ['client_number' => $data['client_number']]);

        $this
            ->apiStoreRequesterImportRowsRequest($other, [$otherData], $other->identity, validate: true)
            ->assertOk()
            ->assertJsonPath('data.0.action', 'create');

        $response = $this
            ->apiStoreRequesterImportRowsRequest($other, [$otherData], $other->identity)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'created');

        $this->assertDatabaseHas('requester_import_rows', [
            'id' => $response->json('data.0.id'), 'organization_id' => $other->id, 'bsn' => $otherData['bsn'],
        ]);

        $this->assertDatabaseMissing('requester_import_rows', ['bsn' => $collision['bsn']]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectsDuplicateBatchIdentifiersBeforeWriting(): void
    {
        ['organization' => $organization, 'name' => $name] = $this->makeRequesterImportFixture();
        $first = $this->makeRequesterImportData($name);
        $second = $this->makeRequesterImportData($name);

        foreach (['bsn', 'client_number'] as $key) {
            $this
                ->apiStoreRequesterImportRowsRequest($organization, [
                    $first, [...$second, $key => $first[$key]], $this->makeRequesterImportData($name),
                ], $organization->identity)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['rows.0.' . $key, 'rows.1.' . $key]);
        }

        $this->assertSame(0, RequesterImportRow::where('organization_id', $organization->id)->count());
        $this->assertSame(0, RequesterImportRecord::withTrashed()->where('organization_id', $organization->id)->count());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectsUnsupportedAndInvalidImportValues(): void
    {
        [
            'organization' => $organization, 'name' => $name, 'address' => $address,
            'date' => $date, 'choice' => $choice,
        ] = $this->makeRequesterImportFixture();

        ['address' => $foreignType] = $this->makeRequesterImportFixture();

        $address->update(['requester_imports' => false]);

        $variants = [
            'eight-digit BSN' => ['bsn', substr($this->makeUniqueBsn(leadingZero: true), 1)],
            'disabled type' => [$address->key, 'Street'],
            'foreign type' => [$foreignType->key, 'Street'],
            'invalid date' => [$date->key, '2026-02-30'],
            'invalid option' => [$choice->key, 'third'],
        ];

        foreach ($variants as [$key, $value]) {
            $data = $this->makeRequesterImportData($name, [$key => $value]);

            foreach ([true, false] as $validate) {
                $this
                    ->apiStoreRequesterImportRowsRequest($organization, [$data], $organization->identity, $validate)
                    ->assertUnprocessable()
                    ->assertJsonValidationErrors('rows.0.' . $key);
            }
        }

        $this->assertSame(0, RequesterImportRow::where('organization_id', $organization->id)->count());
        $this->assertSame(0, RequesterImportRecord::withTrashed()->where('organization_id', $organization->id)->count());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testCsvPatchPreservesOmittedFieldsAndVersionsChangedFields(): void
    {
        ['organization' => $organization, 'name' => $name, 'address' => $address] = $this->makeRequesterImportFixture();
        $data = $this->makeRequesterImportData($name, [$address->key => 'Old street']);
        $row = $this->makeRequesterImportRow($organization, $data);
        $old = $row->records()->where('record_type_key', $address->key)->sole();
        $nameId = $row->records()->where('record_type_key', $name->key)->sole()->id;

        $old->update(['synced_at' => now()]);

        $this
            ->apiStoreRequesterImportRowsRequest($organization, [[
                'bsn' => $data['bsn'], 'client_number' => $data['client_number'], $address->key => 'New street',
            ]], $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'updated')
            ->assertJsonPath('data.0.changes', [[
                'record_type_key' => $address->key,
                'previous_value' => 'Old street',
                'value' => 'New street',
                'action' => 'update',
            ]]);

        $this->assertSoftDeleted($old);
        $this->assertSame('Old street', $old->refresh()->value);
        $this->assertNotNull($old->synced_at);

        $current = $row->records()->where('record_type_key', $address->key)->sole();

        $this->assertNotSame($old->id, $current->id);
        $this->assertSame('New street', $current->value);
        $this->assertNull($current->synced_at);
        $this->assertSame($organization->findEmployee($organization->identity)->id, $current->employee_id);
        $this->assertSame($nameId, $row->records()->where('record_type_key', $name->key)->sole()->id);
        $this->assertSame('Taylor', $row->records()->where('record_type_key', $name->key)->value('value'));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUiEditUsesTheSameRequiredAndClearRulesAsImport(): void
    {
        ['organization' => $organization, 'name' => $name, 'address' => $address] = $this->makeRequesterImportFixture();
        $data = $this->makeRequesterImportData($name, [$address->key => 'Old street']);
        $row = $this->makeRequesterImportRow($organization, $data);
        $old = $row->records()->where('record_type_key', $address->key)->sole();

        $this
            ->apiUpdateRequesterImportRowRequest($organization, $row, [
                'records' => [$address->key => null],
            ], $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.id', $row->id);

        $this->assertSoftDeleted($old);
        $this->assertSame('', $row->records()->where('record_type_key', $address->key)->sole()->value);
        $this->assertSame('Taylor', $row->records()->where('record_type_key', $name->key)->sole()->value);
        $before = $row->records()->withTrashed()->get()->toArray();

        $this
            ->apiUpdateRequesterImportRowRequest($organization, $row, [
                'records' => [$name->key => null],
            ], $organization->identity)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('records.' . $name->key);

        $this
            ->apiStoreRequesterImportRowsRequest(
                $organization,
                [[...$data, $name->key => null]],
                $organization->identity,
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rows.0.' . $name->key);

        $this->assertSame($before, $row->records()->withTrashed()->get()->toArray());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUiEditCannotChangeBsnOrEstablishedClientNumber(): void
    {
        ['organization' => $organization, 'name' => $name] = $this->makeRequesterImportFixture();
        $data = $this->makeRequesterImportData($name);
        $row = $this->makeRequesterImportRow($organization, $data);
        $before = $row->records()->withTrashed()->get()->toArray();
        $replacementBsn = $this->makeUniqueBsn();

        $variants = [
            [['bsn' => $replacementBsn, 'records' => [$name->key => 'Changed']], 'bsn'],
            [['records' => ['bsn' => $replacementBsn]], 'records.bsn'],
            [['records' => ['client_number' => 'replacement-client']], 'records.client_number'],
            [['records' => ['client_number' => null]], 'records.client_number'],
        ];

        foreach ($variants as [$payload, $field]) {
            $this
                ->apiUpdateRequesterImportRowRequest($organization, $row, $payload, $organization->identity)
                ->assertUnprocessable()
                ->assertJsonValidationErrors($field);
        }

        $this->assertSame($data['bsn'], $row->refresh()->bsn);
        $this->assertSame($before, $row->records()->withTrashed()->get()->toArray());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testWriteFailureRollsBackOnlyItsImportRow(): void
    {
        ['organization' => $organization, 'name' => $name] = $this->makeRequesterImportFixture();
        $failed = $this->makeRequesterImportData($name);
        $valid = $this->makeRequesterImportData($name);
        $failedRowId = null;
        $partialRecordId = null;
        $dispatcher = RequesterImportRecord::getEventDispatcher();
        $originalLogger = Log::getFacadeRoot();
        $logger = Log::spy();

        Log::shouldReceive('channel')->with('requester_imports')->andReturnSelf();
        RequesterImportRecord::setEventDispatcher(clone $dispatcher);

        try {
            RequesterImportRecord::creating(function (RequesterImportRecord $record) use (
                $failed,
                $name,
                &$failedRowId,
                &$partialRecordId,
            ) {
                if ($record->record_type_key !== $name->key || $record->requester_import_row->bsn !== $failed['bsn']) {
                    return;
                }

                $failedRowId = $record->requester_import_row_id;
                $partialRecordId = $record->requester_import_row->records()->where('record_type_key', 'client_number')->sole()->id;

                throw new RuntimeException('Injected import record failure.');
            });

            $response = $this
                ->apiStoreRequesterImportRowsRequest(
                    $organization,
                    [$failed, $valid],
                    $organization->identity,
                )
                ->assertOk()
                ->assertJsonCount(2, 'data')
                ->assertJsonPath('data.0.index', 0)->assertJsonPath('data.0.status', 'failed')
                ->assertJsonPath('data.0.code', 'import_failed')
                ->assertJsonPath('data.1.index', 1)->assertJsonPath('data.1.status', 'created');
        } finally {
            RequesterImportRecord::setEventDispatcher($dispatcher);
            Log::swap($originalLogger);
        }

        $this->assertNotNull($failedRowId);
        $this->assertNotNull($partialRecordId);
        $this->assertDatabaseMissing('requester_import_rows', ['id' => $failedRowId]);
        $this->assertDatabaseMissing('requester_import_records', ['id' => $partialRecordId]);
        $this->assertDatabaseHas('requester_import_rows', [
            'id' => $response->json('data.1.id'), 'bsn' => $valid['bsn'],
        ]);

        $this->assertSame(1, RequesterImportRow::where('organization_id', $organization->id)->count());
        $this->assertSame(
            2,
            RequesterImportRecord::withTrashed()->where('organization_id', $organization->id)->count(),
        );

        $diagnosticId = $response->json('data.0.diagnostic_id');

        $this->assertTrue(Str::isUuid($diagnosticId));

        $logger->shouldHaveReceived('log')->once()->withArgs(function (
            string $level,
            string $message,
            array $context,
        ) use ($organization, $diagnosticId): bool {
            $this->assertSame('error', $level);
            $this->assertSame($diagnosticId, $context['diagnostic_id']);
            $this->assertSame('import', $context['operation']);
            $this->assertSame('import_failed', $context['error_code']);
            $this->assertSame($organization->id, $context['organization_id']);
            $this->assertSame(0, $context['row_index']);
            $this->assertSame(RuntimeException::class, $context['exception_class']);

            return true;
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testImportEndpointsRequireManageIdentities(): void
    {
        ['organization' => $organization, 'name' => $name] = $this->makeRequesterImportFixture();
        $manager = $this->makeTestEmployeeWithPermissions($organization, [Permission::MANAGE_IDENTITIES])->identity;
        $viewer = $this->makeTestEmployeeWithPermissions($organization, [Permission::VIEW_IDENTITIES])->identity;
        $data = $this->makeRequesterImportData($name);
        $row = $this->makeRequesterImportRow($organization, $data);
        $newData = $this->makeRequesterImportData($name);
        $before = $row->records()->withTrashed()->get()->toArray();

        $requests = [
            'list' => fn (Identity|bool $identity) =>
                $this->apiGetRequesterImportRowsRequest($organization, $identity),
            'configuration' => fn (Identity|bool $identity) =>
                $this->apiGetRequesterImportConfigurationRequest($organization, $identity),
            'preview' => fn (Identity|bool $identity) =>
                $this->apiStoreRequesterImportRowsRequest($organization, [$newData], $identity, validate: true),
            'store' => fn (Identity|bool $identity) =>
                $this->apiStoreRequesterImportRowsRequest($organization, [$newData], $identity),
            'edit' => fn (Identity|bool $identity) =>
                $this->apiUpdateRequesterImportRowRequest($organization, $row, ['records' => [$name->key => 'Changed']], $identity),
        ];

        foreach ($requests as $endpoint => $request) {
            $response = $request($viewer)->assertJsonMissingPath('data');
            $this->assertSame(403, $response->status(), $endpoint . ': view-only employee');

            $response = $request(false)->assertJsonMissingPath('data');
            $this->assertSame(401, $response->status(), $endpoint . ': unauthenticated caller');
        }

        $this->assertSame(1, RequesterImportRow::where('organization_id', $organization->id)->count());
        $this->assertSame($before, $row->records()->withTrashed()->get()->toArray());

        foreach ($requests as $endpoint => $request) {
            $this->assertSame(200, $request($manager)->status(), $endpoint . ': manager');
        }

        $this->assertSame(2, RequesterImportRow::where('organization_id', $organization->id)->count());
        $this->assertSame('Changed', $row->records()->where('record_type_key', $name->key)->value('value'));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testImportEndpointsRespectFeaturePrerequisites(): void
    {
        ['organization' => $organization, 'name' => $name] = $this->makeRequesterImportFixture();
        $data = $this->makeRequesterImportData($name);

        foreach (['is_sponsor', 'allow_requester_imports', 'allow_profiles', 'bsn_enabled'] as $flag) {
            $organization->forceFill([$flag => false])->save();

            $this->apiGetRequesterImportRowsRequest($organization, $organization->identity)->assertForbidden();
            $this->apiStoreRequesterImportRowsRequest($organization, [$data], $organization->identity)->assertForbidden();
            $this->assertSame(0, RequesterImportRow::where('organization_id', $organization->id)->count());

            $organization->forceFill([$flag => true])->save();
        }

        $this
            ->apiGetRequesterImportRowsRequest($organization, $organization->identity)
            ->assertOk();

        $this
            ->apiStoreRequesterImportRowsRequest($organization, [$data], $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'created');
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testImportListingAndEditingAreOrganizationScoped(): void
    {
        ['organization' => $organization, 'name' => $name] = $this->makeRequesterImportFixture();
        ['organization' => $other, 'name' => $otherName] = $this->makeRequesterImportFixture();
        $ownRow = $this->makeRequesterImportRow($organization, $this->makeRequesterImportData($name));
        $foreignRow = $this->makeRequesterImportRow($other, $this->makeRequesterImportData($otherName));
        $before = $foreignRow->records()->withTrashed()->get()->toArray();

        $this
            ->apiGetRequesterImportRowsRequest($organization, $organization->identity)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownRow->id);

        $this
            ->apiUpdateRequesterImportRowRequest($organization, $foreignRow, [
                'records' => [$otherName->key => 'Changed'],
            ], $organization->identity)
            ->assertForbidden();

        $this
            ->apiUpdateRequesterImportRowRequest($other, $foreignRow, [
                'records' => [$otherName->key => 'Changed'],
            ], $organization->identity)
            ->assertForbidden();

        $this->assertSame($before, $foreignRow->records()->withTrashed()->get()->toArray());
    }
}
