<?php

namespace App\Modules\RequesterImport\Services;

use App\Models\Employee;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Profile;
use App\Models\ProfileRecord;
use App\Models\RecordType;
use App\Modules\RequesterImport\Exceptions\RequesterImportErrorCode;
use App\Modules\RequesterImport\Exceptions\RequesterImportException;
use App\Modules\RequesterImport\Models\RequesterImportRecord;
use App\Modules\RequesterImport\Models\RequesterImportRow;
use App\Modules\RequesterImport\Rules\RequesterImportRecordTypesRule;
use App\Searches\RecordTypeSearch;
use App\Services\IdentityBsnService\Exceptions\IdentityBsnException;
use App\Services\IdentityBsnService\IdentityBsnService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class RequesterImportProfilesService
{
    /**
     * @param Organization $organization
     * @param Employee $employee
     * @param bool $updateAll
     * @return array
     */
    public function syncOrganization(Organization $organization, Employee $employee, bool $updateAll = false): array
    {
        abort_unless($employee->organization_id === $organization->id, 403);

        $rows = RequesterImportRow::where('organization_id', $organization->id)->orderBy('id')
            ->get(['id', 'organization_id']);
        $logger = resolve(RequesterImportLogService::class);
        $results = [];

        foreach ($rows as $row) {
            try {
                $results[] = $this->syncRow($row, $employee, $updateAll);
            } catch (Throwable $exception) {
                $results[] = [
                    'id' => $row->id,
                    'status' => 'failed',
                    'code' => RequesterImportErrorCode::fromException($exception, RequesterImportErrorCode::SYNC_FAILED),
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
     * @return array{
     *     id: int, profile_id: int, status: string,
     *     records_synced: int, records_updated: int,
     * }
     */
    public function syncRow(RequesterImportRow $row, Employee $employee, bool $updateAll = false): array
    {
        return DB::transaction(function () use ($row, $employee, $updateAll): array {
            $row = RequesterImportRow::where('organization_id', $row->organization_id)
                ->lockForUpdate()->findOrFail($row->id);

            abort_unless($row->organization_id === $employee->organization_id, 403);

            $search = new RecordTypeSearch([
                'requester_imports' => true,
                'organization_id' => $row->organization_id,
            ], RecordType::query()->where('key', '!=', RequesterImportRecord::CLIENT_UUID));

            $recordTypes = $search->query()->with('record_type_options.translations')->get()->keyBy('key');
            $recordsQuery = $row->records()->whereIn('record_type_key', $recordTypes->keys());

            if (!$updateAll) {
                $recordsQuery->whereNull('synced_at');
            }

            $records = $recordsQuery->lockForUpdate()->get()->keyBy('record_type_key');

            $values = $records->mapWithKeys(fn (RequesterImportRecord $record) => [
                $record->record_type_key => $record->value === '' ? null : $record->value,
            ])->all();

            Validator::make(['records' => $values], [
                'records' => [new RequesterImportRecordTypesRule($recordTypes, batch: false)],
            ])->validate();

            $profile = $this->resolveProfile($row, $employee);

            if ($row->profile_id !== null && $row->profile_id !== $profile->id) {
                throw new RequesterImportException(RequesterImportErrorCode::PROFILE_MISMATCH, rowId: $row->id);
            }

            $created = $profile->updateRecords($values, $employee, ProfileRecord::SOURCE_REQUESTER_IMPORT);

            foreach ($created as $key => $profileRecord) {
                $profileRecord->update(['requester_import_record_id' => $records->get($key)->id]);
            }

            if ($row->profile_id === null) {
                RequesterImportRow::withoutTimestamps(fn () => $row->update(['profile_id' => $profile->id]));
            }

            RequesterImportRecord::withoutTimestamps(fn () => $row->records()->whereKey($records->modelKeys())
                ->update(['synced_at' => now()]));

            return [
                'id' => $row->id,
                'profile_id' => $profile->id,
                'status' => $profile->wasRecentlyCreated ? 'created' : ($created->isNotEmpty() ? 'updated' : 'unchanged'),
                'records_synced' => $records->count(),
                'records_updated' => $created->count(),
            ];
        });
    }

    /**
     * @param RequesterImportRow $row
     * @param Employee $employee
     * @return Profile
     */
    public function resolveProfile(RequesterImportRow $row, Employee $employee): Profile
    {
        abort_unless($row->organization_id === $employee->organization_id, 403);

        $service = resolve(IdentityBsnService::class);

        try {
            return $service->transaction($row->bsn, function () use ($row, $employee, $service) {
                $identity = $service->find($row->bsn, true);

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
            if ($exception->errorCode !== IdentityBsnException::AMBIGUOUS) {
                throw $exception;
            }

            throw new RequesterImportException(
                RequesterImportErrorCode::IDENTITY_AMBIGUOUS,
                $exception,
                $row->id,
            );
        }
    }
}
