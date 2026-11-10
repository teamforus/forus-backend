<?php

namespace App\Modules\RequesterImport\Services;

use App\Models\Employee;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Profile;
use App\Models\ProfileRecord;
use App\Models\RecordType;
use App\Modules\RequesterImport\Data\RequesterImportProfileRecordChange;
use App\Modules\RequesterImport\Data\RequesterImportSyncPreview;
use App\Modules\RequesterImport\Exceptions\RequesterImportErrorCode;
use App\Modules\RequesterImport\Exceptions\RequesterImportException;
use App\Modules\RequesterImport\Models\RequesterImportRecord;
use App\Modules\RequesterImport\Models\RequesterImportRow;
use App\Modules\RequesterImport\Rules\RequesterImportRecordTypesRule;
use App\Services\IdentityBsnService\Exceptions\IdentityBsnException;
use App\Services\IdentityBsnService\IdentityBsnService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class RequesterImportProfileSyncService
{
    public const int BATCH_SIZE = 100;

    /**
     * @param Organization $organization
     * @return array<int, int>
     */
    public function candidateIds(Organization $organization): array
    {
        return $this->organizationRowsQuery($organization)->pluck('id')->all();
    }

    /**
     * @param Organization $organization
     * @param Employee $employee
     * @param bool $updateAll
     * @param array<int, int>|null $rowIds
     * @return array<int, RequesterImportSyncPreview>
     */
    public function previewOrganization(
        Organization $organization,
        Employee $employee,
        bool $updateAll = false,
        ?array $rowIds = null,
    ): array {
        abort_unless($employee->organization_id === $organization->id, 403);

        $results = [];

        foreach ($this->organizationRows($organization, $rowIds) as $row) {
            try {
                $results[] = $this->previewRow($row, $updateAll);
            } catch (Throwable $exception) {
                $expected = $exception instanceof RequesterImportException || $exception instanceof ValidationException;

                $results[] = new RequesterImportSyncPreview(
                    id: $row->id,
                    bsn: $row->bsn,
                    profileId: null,
                    action: 'blocked',
                    errors: match (true) {
                        $exception instanceof RequesterImportException,
                        $exception instanceof ValidationException => $exception->errors(),
                        default => [],
                    },
                    code: RequesterImportErrorCode::fromException(
                        $exception,
                        RequesterImportErrorCode::SYNC_PREVIEW_FAILED,
                    ),
                    diagnosticId: $expected ? null : resolve(RequesterImportLogService::class)->failure(
                        RequesterImportLogService::OPERATION_SYNC_PREVIEW,
                        $exception,
                        ['organization_id' => $organization->id, 'employee_id' => $employee->id, 'row_id' => $row->id],
                        RequesterImportErrorCode::SYNC_PREVIEW_FAILED,
                    ),
                );
            }
        }

        return $results;
    }

    /**
     * @param Organization $organization
     * @param Employee $employee
     * @param bool $updateAll
     * @param array<int, int>|null $rowIds
     * @return array
     */
    public function syncOrganization(
        Organization $organization,
        Employee $employee,
        bool $updateAll = false,
        ?array $rowIds = null,
    ): array {
        abort_unless($employee->organization_id === $organization->id, 403);

        $logger = resolve(RequesterImportLogService::class);
        $results = [];

        foreach ($this->organizationRows($organization, $rowIds) as $row) {
            try {
                $results[] = $this->syncRow($row, $employee, $updateAll);
            } catch (Throwable $exception) {
                $results[] = [
                    'id' => $row->id,
                    'bsn' => $row->bsn,
                    'status' => 'failed',
                    'code' => RequesterImportErrorCode::fromException(
                        $exception,
                        RequesterImportErrorCode::SYNC_FAILED,
                    ),
                    'errors' => match (true) {
                        $exception instanceof RequesterImportException,
                        $exception instanceof ValidationException => $exception->errors(),
                        default => [],
                    },
                    'diagnostic_id' => $logger->failure(
                        RequesterImportLogService::OPERATION_SYNC,
                        $exception,
                        [
                            'organization_id' => $organization->id,
                            'employee_id' => $employee->id,
                            'row_id' => $row->id,
                        ],
                        RequesterImportErrorCode::SYNC_FAILED,
                    ),
                ];
            }
        }

        return $results;
    }

    /**
     * @param RequesterImportRow $row
     * @param Employee $employee
     * @param bool $updateAll
     * @throws Throwable
     * @return array{
     *     id: int, bsn: string, profile_id: int, identity_id: int, status: string,
     *     records_synced: int, records_updated: int,
     * }
     */
    public function syncRow(RequesterImportRow $row, Employee $employee, bool $updateAll = false): array
    {
        return DB::transaction(function () use ($row, $employee, $updateAll): array {
            $row = RequesterImportRow::query()
                ->where('organization_id', $row->organization_id)
                ->lockForUpdate()
                ->findOrFail($row->id);

            abort_unless($row->organization_id === $employee->organization_id, 403);

            $records = $this->selectSyncRecords($row, $updateAll, lock: true);
            $values = $this->syncRecordValues($records);

            $profile = $this->resolveProfile($row, $employee);

            $this->assertProfileLink($row, $profile);

            $created = $profile->updateRecords($values, $employee, ProfileRecord::SOURCE_REQUESTER_IMPORT);

            foreach ($created as $key => $profileRecord) {
                $profileRecord->origin()->associate($records->get($key))->save();
            }

            $needsProfileLink = $row->profile_id === null;

            if ($needsProfileLink) {
                RequesterImportRow::withoutTimestamps(fn () => $row->update(['profile_id' => $profile->id]));
            }

            RequesterImportRecord::withoutTimestamps(fn () => $row->records()->whereKey($records->modelKeys())
                ->update(['synced_at' => now()]));

            return [
                'id' => $row->id,
                'bsn' => $row->bsn,
                'profile_id' => $profile->id,
                'identity_id' => $profile->identity_id,
                'status' => $profile->wasRecentlyCreated ?
                    'created' : ($needsProfileLink || $created->isNotEmpty() ? 'updated' : 'unchanged'),
                'records_synced' => $records->count(),
                'records_updated' => $created->count(),
            ];
        });
    }

    /**
     * @param RequesterImportRow $row
     * @param Employee $employee
     * @throws Throwable
     * @return Profile
     */
    public function resolveProfile(RequesterImportRow $row, Employee $employee): Profile
    {
        abort_unless($row->organization_id === $employee->organization_id, 403);

        $service = resolve(IdentityBsnService::class);

        try {
            return $service->transactionWithBsnLock($row->bsn, function () use ($row, $employee, $service) {
                $identity = $service->findIdentityByBsnForUpdate($row->bsn);

                if (!$identity) {
                    $identity = Identity::build(
                        type: Identity::TYPE_PROFILE,
                        employeeId: $employee->id,
                        organizationId: $row->organization_id,
                    );

                    $identity->setBsnRecord($row->bsn);
                }

                return Organization::findOrFail($row->organization_id)->findOrMakeProfile($identity);
            });
        } catch (IdentityBsnException $exception) {
            throw $this->mapRowIdentityException($row, $exception);
        }
    }

    /**
     * @param RequesterImportRow $row
     * @param bool $updateAll
     * @param bool $lock
     * @throws ValidationException
     * @return Collection<string, RequesterImportRecord>
     */
    private function selectSyncRecords(RequesterImportRow $row, bool $updateAll, bool $lock): Collection
    {
        $recordTypes = RecordType::searchRecordTypes(
            organizationId: $row->organization_id,
            profileRecords: true,
            requesterImports: true,
        );

        $query = $row->records()->whereIn('record_type_key', $recordTypes->keys());

        if (!$updateAll) {
            $query->whereNull('synced_at');
        }

        if ($lock) {
            $query->lockForUpdate();
        }

        /** @var Collection<string, RequesterImportRecord> $records */
        $records = $query->get()->keyBy('record_type_key');

        Validator::make(['records' => $this->syncRecordValues($records)], [
            'records' => [new RequesterImportRecordTypesRule($recordTypes, batch: false)],
        ])->validate();

        return $records;
    }

    /**
     * @param Collection<string, RequesterImportRecord> $records
     * @return array<string, string|null>
     */
    private function syncRecordValues(Collection $records): array
    {
        return $records->mapWithKeys(fn (RequesterImportRecord $record) => [
            $record->record_type_key => $record->value === '' ? null : $record->value,
        ])->all();
    }

    /**
     * @param Organization $organization
     * @param array<int, int>|null $rowIds
     * @return Collection<int, RequesterImportRow>
     */
    private function organizationRows(Organization $organization, ?array $rowIds): Collection
    {
        $query = $this->organizationRowsQuery($organization);

        if ($rowIds !== null) {
            $query->whereKey($rowIds);
        }

        return $query->get(['id', 'organization_id', 'bsn', 'profile_id']);
    }

    /**
     * @param Organization $organization
     * @return Builder<RequesterImportRow>
     */
    private function organizationRowsQuery(Organization $organization): Builder
    {
        return RequesterImportRow::query()
            ->where('organization_id', $organization->id)
            ->orderBy('id');
    }

    /**
     * @param RequesterImportRow $row
     * @param bool $updateAll
     * @throws ValidationException
     * @throws RequesterImportException
     * @return RequesterImportSyncPreview
     */
    private function previewRow(RequesterImportRow $row, bool $updateAll): RequesterImportSyncPreview
    {
        $records = $this->selectSyncRecords($row, $updateAll, lock: false);

        try {
            $identity = resolve(IdentityBsnService::class)->findIdentityByBsn($row->bsn);
        } catch (IdentityBsnException $exception) {
            throw $this->mapRowIdentityException($row, $exception);
        }

        $profile = $identity?->profiles()->where('organization_id', $row->organization_id)->first();

        $this->assertProfileLink($row, $profile);

        $currentRecords = $profile?->profile_records()->whereNull('deleted_at')->get()
            ->unique('record_type_id')->keyBy('record_type_id');

        $changes = [];

        foreach ($this->syncRecordValues($records) as $key => $value) {
            $recordType = RecordType::findByKey($key);

            /** @var ProfileRecord|null $current */
            $current = $currentRecords?->get($recordType?->id);

            if (!Profile::shouldUpdateRecord($current, $value, ProfileRecord::SOURCE_REQUESTER_IMPORT)) {
                continue;
            }

            $value = trim($value ?? '');

            $changes[] = new RequesterImportProfileRecordChange(
                recordTypeKey: $key,
                currentValue: $current?->value,
                value: $value,
                action: match (true) {
                    $current === null => 'add',
                    trim($current->value ?? '') === $value => 'confirm_source',
                    $value === '' => 'clear',
                    default => 'update',
                },
            );
        }

        return new RequesterImportSyncPreview(
            id: $row->id,
            bsn: $row->bsn,
            profileId: $profile?->id,
            action: match (true) {
                $profile === null => 'create',
                $row->profile_id === null || $changes !== [] => 'update',
                default => 'unchanged',
            },
            changes: $changes,
        );
    }

    /**
     * @param RequesterImportRow $row
     * @param IdentityBsnException $exception
     * @return IdentityBsnException|RequesterImportException
     */
    private function mapRowIdentityException(
        RequesterImportRow $row,
        IdentityBsnException $exception,
    ): IdentityBsnException|RequesterImportException {
        return $exception->errorCode === IdentityBsnException::AMBIGUOUS ?
            new RequesterImportException(RequesterImportErrorCode::IDENTITY_AMBIGUOUS, $exception, $row->id) :
            $exception;
    }

    /**
     * @param RequesterImportRow $row
     * @param Profile|null $profile
     * @throws RequesterImportException
     * @return void
     */
    private function assertProfileLink(RequesterImportRow $row, ?Profile $profile): void
    {
        if ($row->profile_id !== null && $row->profile_id !== $profile?->id) {
            throw new RequesterImportException(RequesterImportErrorCode::PROFILE_MISMATCH, rowId: $row->id);
        }
    }
}
