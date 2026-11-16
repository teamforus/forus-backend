<?php

namespace App\Modules\RequesterImport\Services;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\RecordType;
use App\Modules\RequesterImport\Data\RequesterImportRowPreview;
use App\Modules\RequesterImport\Data\RequesterImportRowRecordChange;
use App\Modules\RequesterImport\Exceptions\RequesterImportErrorCode;
use App\Modules\RequesterImport\Exceptions\RequesterImportException;
use App\Modules\RequesterImport\Models\RequesterImportRecord;
use App\Modules\RequesterImport\Models\RequesterImportRow;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class RequesterImportRowsService
{
    /**
     * @param Organization $organization
     * @throws RequesterImportException
     * @return array{
     *     key: string,
     *     columns: array<string, array{
     *         column: string, required?: bool, normalize?: string, date_format?: string, values?: array,
     *     }>,
     * }
     */
    public function getConfiguration(Organization $organization): array
    {
        $preset = $this->getMappingPreset($organization->requester_import_mapping_key);

        $recordTypes = RecordType::searchRecordTypes(
            organizationId: $organization->id,
            requesterImports: true,
        );

        $allowedKeys = [RecordType::KEY_BSN, ...$recordTypes->keys()->all()];
        $unavailableKeys = array_diff(array_keys($preset['fields']), $allowedKeys);

        if ($unavailableKeys !== []) {
            throw new RequesterImportException(RequesterImportErrorCode::MAPPING_RECORDS_UNAVAILABLE);
        }

        $columns = $preset['fields'];
        $headers = array_column($columns, 'column');

        if (count($headers) !== count(array_unique($headers))) {
            throw new RequesterImportException(RequesterImportErrorCode::MAPPING_COLUMNS_AMBIGUOUS);
        }

        return ['key' => $organization->requester_import_mapping_key, 'columns' => $columns];
    }

    /**
     * @param Organization $organization
     * @param array<int, array<string, string|null>> $rows Validated rows with canonical BSNs.
     * @throws ValidationException
     * @return array<int, RequesterImportRowPreview>
     */
    public function previewBatch(Organization $organization, array $rows): array
    {
        $results = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                $importRow = $this->findMatchingRow($organization->id, $row['bsn'], $row['client_number'] ?? null);
                $values = $row;
                $records = new Collection();

                if ($importRow) {
                    $values = Arr::except($row, ['client_number']);

                    $records = $importRow->records->keyBy('record_type_key');
                }

                $this->makeRequiredRecordsValidator($organization, $row, $importRow)->validate();

                $changes = $this->calculateRecordChanges($values, $records);

                $results[] = new RequesterImportRowPreview(
                    index: $index,
                    id: $importRow?->id,
                    action: $importRow === null ? 'create' : ($changes->isNotEmpty() ? 'update' : 'unchanged'),
                    changes: $changes->all(),
                );
            } catch (RequesterImportException|ValidationException $exception) {
                foreach ($exception->errors() as $key => $messages) {
                    $errors['rows.' . $index . '.' . $key] = $messages;
                }
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $results;
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
                        $row['client_number'] ?? null,
                        lock: true,
                    );

                    $created = $importRow === null;
                    $context['row_id'] = $importRow?->id;

                    $validator = $this->makeRequiredRecordsValidator($organization, $row, $importRow);

                    if ($validator->fails()) {
                        return [
                            'status' => 'failed', 'code' => 'validation_failed',
                            'errors' => $validator->errors()->toArray(),
                        ];
                    }

                    if ($created) {
                        $importRow = RequesterImportRow::create([
                            'organization_id' => $organization->id,
                            'bsn' => $row['bsn'],
                        ]);
                    }

                    $changes = $this->updateRecords(
                        $importRow,
                        $created ? $row : Arr::except($row, ['client_number']),
                        $employee,
                    );

                    return [
                        'status' => $created ? 'created' : ($changes->isNotEmpty() ? 'updated' : 'unchanged'),
                        'id' => $importRow->id,
                        'changes' => $changes->all(),
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
     * @param string|null $clientNumber
     * @param bool $lock
     * @throws RequesterImportException
     * @return RequesterImportRow|null
     */
    public function findMatchingRow(
        int $organizationId,
        string $bsn,
        ?string $clientNumber,
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

            if ($clientNumber === null) {
                throw new RequesterImportException(RequesterImportErrorCode::CLIENT_NUMBER_REQUIRED, rowId: $row->id);
            }

            if (!$row->records()->where('current_client_number', $clientNumber)->exists()) {
                throw new RequesterImportException(RequesterImportErrorCode::CLIENT_NUMBER_MISMATCH, rowId: $row->id);
            }

            return $row;
        }

        if ($clientNumber !== null && $this->clientNumberExists($organizationId, $clientNumber)) {
            throw new RequesterImportException(RequesterImportErrorCode::CLIENT_NUMBER_CONFLICT);
        }

        return null;
    }

    /**
     * @param Organization $organization
     * @param array $row
     * @param RequesterImportRow|null $importRow
     * @throws RequesterImportException
     * @return ValidatorContract
     */
    public function makeRequiredRecordsValidator(
        Organization $organization,
        array $row,
        ?RequesterImportRow $importRow = null,
    ): ValidatorContract {
        $preset = $this->getMappingPreset($organization->requester_import_mapping_key);
        $requiredKeys = array_keys(array_filter(
            $preset['fields'],
            fn (array $field) => $field['required'] ?? false,
        ));

        $values = $importRow ? [
            ...$importRow->records->pluck('value', 'record_type_key')->all(),
            'bsn' => $importRow->bsn,
            ...$row,
        ] : $row;

        return Validator::make($values, array_fill_keys($requiredKeys, 'required'));
    }

    /**
     * @param array<string, string|null> $values
     * @param Collection<string, RequesterImportRecord> $records Active records keyed by record type key.
     * @return Collection<int, RequesterImportRowRecordChange>
     */
    public function calculateRecordChanges(array $values, Collection $records): Collection
    {
        $changes = new Collection();

        foreach (Arr::except($values, ['bsn']) as $key => $value) {
            if ($key === 'client_number' && $value === null) {
                continue;
            }

            $value = $value ?? '';
            $record = $records->get($key);

            if ($record && $record->value === $value) {
                continue;
            }

            $changes->push(new RequesterImportRowRecordChange($key, $record?->value, $value));
        }

        return $changes;
    }

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

                if (array_key_exists('client_number', $values)) {
                    $currentClientNumber = $row->records()->where('record_type_key', 'client_number')->first();

                    if ($currentClientNumber && $currentClientNumber->value !== $values['client_number']) {
                        throw new RequesterImportException(RequesterImportErrorCode::CLIENT_NUMBER_IMMUTABLE);
                    }

                    if (!$currentClientNumber && $values['client_number'] !== null &&
                        $this->clientNumberExists($row->organization_id, $values['client_number'])) {
                        throw new RequesterImportException(RequesterImportErrorCode::CLIENT_NUMBER_CONFLICT);
                    }
                }

                $this->makeRequiredRecordsValidator($row->organization, $values, $row)->validate();
                $this->updateRecords($row, $values, $employee);

                return $row;
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw new RequesterImportException(RequesterImportErrorCode::CLIENT_NUMBER_CONFLICT, $exception);
        }
    }

    /**
     * @param RequesterImportRow $row
     * @param array<string, string|null> $values
     * @param Employee|null $employee
     * @return Collection<int, RequesterImportRowRecordChange>
     */
    protected function updateRecords(RequesterImportRow $row, array $values, ?Employee $employee): Collection
    {
        $records = $row->records()->whereIn('record_type_key', array_keys($values))->get()->keyBy('record_type_key');
        $changes = $this->calculateRecordChanges($values, $records);

        foreach ($changes as $change) {
            $records->get($change->recordTypeKey)?->delete();

            $row->records()->create([
                'organization_id' => $row->organization_id,
                'employee_id' => $employee?->id,
                'record_type_key' => $change->recordTypeKey,
                'value' => $change->value,
            ]);
        }

        if ($changes->isNotEmpty() && !$row->wasRecentlyCreated) {
            $row->touch();
        }

        return $changes;
    }

    /**
     * @param string|null $key
     * @throws RequesterImportException
     * @return array{fields: array<string, array>}
     */
    private function getMappingPreset(?string $key): array
    {
        $preset = Config::get('requester_import.mappings', [])[$key] ?? null;

        if (!is_array($preset) || $preset === []) {
            throw new RequesterImportException(RequesterImportErrorCode::MAPPING_UNAVAILABLE);
        }

        return $preset;
    }

    /**
     * @param int $organizationId
     * @param string $clientNumber
     * @return bool
     */
    private function clientNumberExists(int $organizationId, string $clientNumber): bool
    {
        return RequesterImportRecord::query()
            ->where('organization_id', $organizationId)
            ->where('current_client_number', $clientNumber)->exists();
    }
}
