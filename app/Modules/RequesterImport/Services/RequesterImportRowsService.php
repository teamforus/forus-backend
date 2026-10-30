<?php

namespace App\Modules\RequesterImport\Services;

use App\Models\Employee;
use App\Models\Organization;
use App\Modules\RequesterImport\Exceptions\RequesterImportErrorCode;
use App\Modules\RequesterImport\Exceptions\RequesterImportException;
use App\Modules\RequesterImport\Models\RequesterImportRecord;
use App\Modules\RequesterImport\Models\RequesterImportRow;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class RequesterImportRowsService
{
    /**
     * @param RequesterImportRow $row
     * @param array $values
     * @param Employee|null $employee
     * @throws RequesterImportException
     * @throws Throwable
     * @return RequesterImportRow
     */
    public function updateRow(RequesterImportRow $row, array $values, ?Employee $employee): RequesterImportRow
    {
        try {
            return DB::transaction(function () use ($row, $values, $employee): RequesterImportRow {
                $row = RequesterImportRow::where('organization_id', $row->organization_id)
                    ->lockForUpdate()->findOrFail($row->id);

                if (array_key_exists('client_uuid', $values)) {
                    $currentUuid = $row->records()->where('record_type_key', 'client_uuid')->first();

                    if ($currentUuid && $currentUuid->value !== $values['client_uuid']) {
                        throw new RequesterImportException(RequesterImportErrorCode::CLIENT_UUID_IMMUTABLE);
                    }

                    if (!$currentUuid && $values['client_uuid'] !== null &&
                        $this->clientUuidExists($row->organization_id, $values['client_uuid'])) {
                        throw new RequesterImportException(RequesterImportErrorCode::CLIENT_UUID_CONFLICT);
                    }
                }

                $this->updateRecords($row, $values, $employee);

                return $row;
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw new RequesterImportException(RequesterImportErrorCode::CLIENT_UUID_CONFLICT, $exception);
        }
    }

    /**
     * @param Organization $organization
     * @param array $rows Validated rows with canonical BSNs.
     * @param Employee|null $employee
     * @return array
     */
    public function importBatch(Organization $organization, array $rows, ?Employee $employee): array
    {
        $results = [];
        $logger = resolve(RequesterImportLogService::class);

        foreach ($rows as $index => $row) {
            $context = [
                'organization_id' => $organization->id, 'employee_id' => $employee?->id, 'row_index' => $index,
            ];

            try {
                $result = DB::transaction(function () use ($organization, $row, $employee, &$context): array {
                    $importRow = $this->findMatchingRow(
                        $organization->id,
                        $row['bsn'],
                        $row['client_uuid'] ?? null,
                        lock: true,
                    );

                    $created = $importRow === null;
                    $context['row_id'] = $importRow?->id;

                    if ($created) {
                        $validator = $this->makeRequiredRecordsValidator($row);

                        if ($validator->fails()) {
                            return [
                                'status' => 'failed', 'code' => 'validation_failed',
                                'errors' => $validator->errors()->toArray(),
                            ];
                        }

                        $importRow = RequesterImportRow::create([
                            'organization_id' => $organization->id,
                            'bsn' => $row['bsn'],
                        ]);
                    }

                    $changed = $this->updateRecords(
                        $importRow,
                        $created ? $row : Arr::except($row, ['client_uuid']),
                        $employee,
                    );

                    return [
                        'status' => $created ? 'created' : ($changed ? 'updated' : 'unchanged'),
                        'id' => $importRow->id,
                    ];
                });

                $results[] = ['index' => $index, ...$result];
            } catch (Throwable $exception) {
                $results[] = [
                    'index' => $index,
                    'status' => 'failed',
                    'code' => match (true) {
                        $exception instanceof RequesterImportException => 'validation_failed',
                        $exception instanceof UniqueConstraintViolationException => 'duplicate_identifier',
                        default => 'import_failed',
                    },
                    ...$exception instanceof RequesterImportException ? ['errors' => $exception->errors()] : [],
                    'diagnostic_id' => $logger->failure(
                        RequesterImportLogService::OPERATION_IMPORT,
                        $exception,
                        $context,
                        RequesterImportErrorCode::IMPORT_FAILED,
                    ),
                ];
            }
        }

        return $results;
    }

    /**
     * @param int $organizationId
     * @param string $bsn
     * @param string|null $clientUuid
     * @param bool $lock
     * @throws RequesterImportException
     * @return RequesterImportRow|null
     */
    public function findMatchingRow(
        int $organizationId,
        string $bsn,
        ?string $clientUuid,
        bool $lock = false,
    ): ?RequesterImportRow {
        $query = RequesterImportRow::withTrashed()
            ->where('organization_id', $organizationId)
            ->where('bsn', $bsn);

        if ($lock) {
            $query->lockForUpdate();
        }

        $row = $query->first();

        if ($row) {
            if ($row->trashed()) {
                throw new RequesterImportException(RequesterImportErrorCode::ROW_UNAVAILABLE, rowId: $row->id);
            }

            if ($clientUuid === null) {
                throw new RequesterImportException(RequesterImportErrorCode::CLIENT_UUID_REQUIRED, rowId: $row->id);
            }

            if (!$row->records()->where('current_client_uuid', $clientUuid)->exists()) {
                throw new RequesterImportException(RequesterImportErrorCode::CLIENT_UUID_MISMATCH, rowId: $row->id);
            }

            return $row;
        }

        if ($clientUuid !== null && $this->clientUuidExists($organizationId, $clientUuid)) {
            throw new RequesterImportException(RequesterImportErrorCode::CLIENT_UUID_CONFLICT);
        }

        return null;
    }

    /**
     * @param array $row
     * @return ValidatorContract
     */
    public function makeRequiredRecordsValidator(array $row): ValidatorContract
    {
        return Validator::make($row, [
            'given_name' => 'required',
            'family_name' => 'required',
            'birth_date' => 'required',
        ]);
    }

    /**
     * @param RequesterImportRow $row
     * @param array $values
     * @param Employee|null $employee
     * @return bool
     */
    protected function updateRecords(RequesterImportRow $row, array $values, ?Employee $employee): bool
    {
        $changed = false;

        foreach (Arr::except($values, ['bsn']) as $key => $value) {
            if ($key === 'client_uuid' && $value === null) {
                continue;
            }

            $value = $value ?? '';
            $record = $row->records()->where('record_type_key', $key)->first();

            if ($record && $record->value === $value) {
                continue;
            }

            $record?->delete();

            $row->records()->create([
                'organization_id' => $row->organization_id,
                'employee_id' => $employee?->id,
                'record_type_key' => $key,
                'value' => $value,
            ]);

            $changed = true;
        }

        if ($changed && !$row->wasRecentlyCreated) {
            $row->touch();
        }

        return $changed;
    }

    /**
     * @param int $organizationId
     * @param string $clientUuid
     * @return bool
     */
    private function clientUuidExists(int $organizationId, string $clientUuid): bool
    {
        return RequesterImportRecord::where('organization_id', $organizationId)
            ->where('current_client_uuid', $clientUuid)->exists();
    }
}
