<?php

namespace App\Modules\VoucherPass\Services;

use App\Models\Employee;
use App\Models\Fund;
use App\Models\Organization;
use App\Modules\RequesterImport\Models\RequesterImportRow;
use App\Modules\VoucherPass\Data\VoucherPassChange;
use App\Modules\VoucherPass\Data\VoucherPassData;
use App\Modules\VoucherPass\Data\VoucherPassPreview;
use App\Modules\VoucherPass\Models\VoucherPass;
use App\Modules\VoucherPass\Models\VoucherPassType;
use App\Modules\VoucherPass\Rules\VoucherPassRowsRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class VoucherPassService
{
    public const int BATCH_SIZE = 100;

    /**
     * @param Organization $organization
     * @param array $rows Validated batch rows.
     * @throws ValidationException
     * @return array<int, VoucherPassPreview>
     */
    public function previewBatch(Organization $organization, array $rows): array
    {
        $results = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                $row = VoucherPassRowsRule::normalize($row);
                $pass = $this->findByExternalId($organization, $row['external_id']);
                $data = $this->prepareRow($organization, $row, $pass);
                $changes = $this->calculateChanges($this->rowValues($pass), $data->values);

                $results[] = new VoucherPassPreview(
                    index: $index,
                    id: $pass?->id,
                    code: $pass?->code,
                    action: $pass === null ? 'create' : ($changes === [] ? 'unchanged' : 'update'),
                    changes: $changes,
                );
            } catch (ValidationException $exception) {
                foreach ($exception->errors() as $field => $messages) {
                    $errors['rows.' . $index . '.' . $field] = $messages;
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $results;
    }

    /**
     * @param Organization $organization
     * @param array $rows Validated batch rows.
     * @param Employee|null $employee
     * @return array
     */
    public function importBatch(Organization $organization, array $rows, ?Employee $employee): array
    {
        abort_unless($employee === null || $employee->organization_id === $organization->id, 403);

        $results = [];

        foreach ($rows as $index => $row) {
            try {
                $result = $this->writeRow($organization, $row, $employee);
                $pass = $result['pass'];

                $results[] = [
                    'index' => $index,
                    'id' => $pass->id,
                    'code' => $pass->code,
                    'status' => $result['status'],
                    'changes' => $result['changes'],
                ];
            } catch (ValidationException $exception) {
                $results[] = [
                    'index' => $index,
                    'status' => 'failed',
                    'code' => 'validation_failed',
                    'message' => trans('validation.header'),
                    'errors' => $exception->errors(),
                ];
            } catch (Throwable $exception) {
                $conflict = $exception instanceof UniqueConstraintViolationException;

                $results[] = [
                    'index' => $index,
                    'status' => 'failed',
                    'code' => $conflict ? 'identifier_conflict' : 'import_failed',
                    'message' => trans($conflict ?
                        'voucher_pass::validation.identifier_conflict' : 'voucher_pass::validation.import_failed'),
                    'errors' => [],
                    'diagnostic_id' => $this->logFailure(
                        $exception,
                        $organization,
                        $employee,
                        'import',
                        rowIndex: $index,
                    ),
                ];
            }
        }

        return $results;
    }

    /**
     * @param Organization $organization
     * @param VoucherPass $pass
     * @param array $row
     * @param Employee|null $employee
     * @throws Throwable
     * @return VoucherPass
     */
    public function update(
        Organization $organization,
        VoucherPass $pass,
        array $row,
        ?Employee $employee,
    ): VoucherPass {
        abort_unless($pass->organization_id === $organization->id, 403);
        abort_unless($employee === null || $employee->organization_id === $organization->id, 403);

        try {
            return $this->writeRow($organization, $row, $employee, $pass->id)['pass'];
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'external_id' => trans('voucher_pass::validation.uid_in_use'),
            ]);
        }
    }

    /**
     * @param Throwable $exception
     * @param Organization $organization
     * @param Employee|null $employee
     * @param string $operation
     * @param int|null $passId
     * @param int|null $rowIndex
     * @return string
     */
    public function logFailure(
        Throwable $exception,
        Organization $organization,
        ?Employee $employee,
        string $operation,
        ?int $passId = null,
        ?int $rowIndex = null,
    ): string {
        $diagnosticId = Str::uuid()->toString();

        try {
            Log::channel('voucher_passes')->log(
                $exception instanceof UniqueConstraintViolationException ? 'warning' : 'error',
                'Voucher pass operation failed.',
                [
                    'diagnostic_id' => $diagnosticId,
                    'operation' => $operation,
                    'organization_id' => $organization->id,
                    'employee_id' => $employee?->id,
                    'pass_id' => $passId,
                    'row_index' => $rowIndex,
                    'exception_class' => $exception::class,
                    'exception_file' => $exception->getFile(),
                    'exception_line' => $exception->getLine(),
                ],
            );
        } catch (Throwable) {
            // Logging failures must not replace the original response.
        }

        return $diagnosticId;
    }

    /**
     * @param Organization $organization
     * @param array $row
     * @param Employee|null $employee
     * @param int|null $passId
     * @throws Throwable
     * @return array{pass: VoucherPass, status: string, changes: array<int, VoucherPassChange>}
     */
    private function writeRow(
        Organization $organization,
        array $row,
        ?Employee $employee,
        ?int $passId = null,
    ): array {
        return DB::transaction(function () use ($organization, $row, $employee, $passId): array {
            $row = VoucherPassRowsRule::normalize($row);

            $pass = $passId !== null ?
                $organization->voucher_passes()->lockForUpdate()->findOrFail($passId) :
                $this->findByExternalId($organization, $row['external_id'], lock: true);

            $data = $this->prepareRow($organization, $row, $pass, lock: true);
            $before = $this->rowValues($pass);
            $changes = $this->calculateChanges($before, $data->values);
            $created = $pass === null;

            if (!$created && $changes === []) {
                return ['pass' => $pass, 'status' => 'unchanged', 'changes' => []];
            }

            $attributes = [
                'external_id' => $data->values['external_id'],
                'owner_identity_id' => $data->ownerIdentityId,
                'target_identity_id' => $data->targetIdentityId,
                'print_label' => $data->values['print_label'],
                'state' => $data->values['state'],
                'print_requested' => $data->values['print_requested'],
                'employee_id' => $employee?->id,
            ];

            if ($created) {
                $pass = VoucherPass::create([
                    ...$attributes,
                    'organization_id' => $organization->id,
                    'code' => VoucherPass::generateCode(),
                ]);
            } else {
                $pass->fill($attributes);
                $pass->updated_at = $pass->freshTimestamp();
                $pass->save();
            }

            $pass->types()->sync($data->typeIds);
            $pass->explicit_funds()->sync($data->values['fund_ids']);
            $pass->refresh();

            return [
                'pass' => $pass,
                'status' => $created ? 'created' : 'updated',
                'changes' => $this->calculateChanges($before, $this->rowValues($pass)),
            ];
        });
    }

    /**
     * @param Organization $organization
     * @param string|null $externalId
     * @param bool $lock
     * @return VoucherPass|null
     */
    private function findByExternalId(
        Organization $organization,
        ?string $externalId,
        bool $lock = false,
    ): ?VoucherPass {
        if ($externalId === null) {
            return null;
        }

        $query = $organization->voucher_passes()->where('external_id', $externalId);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /**
     * @param Organization $organization
     * @param array $row
     * @param VoucherPass|null $pass
     * @param bool $lock
     * @throws ValidationException
     * @return VoucherPassData
     */
    private function prepareRow(
        Organization $organization,
        array $row,
        ?VoucherPass $pass,
        bool $lock = false,
    ): VoucherPassData {
        $row = VoucherPassRowsRule::normalize($row);
        Validator::make($row, VoucherPassRowsRule::rules())->validate();

        $submittedTypeNames = $row['type_names'];
        $submittedFundIds = array_map('intval', $row['fund_ids']);

        $row['type_names'] = array_values(array_unique($row['type_names']));
        $row['fund_ids'] = array_values(array_unique($submittedFundIds));
        $row['print_requested'] = (bool) $row['print_requested'];

        sort($row['type_names'], SORT_STRING);
        sort($row['fund_ids'], SORT_NUMERIC);

        $importsQuery = RequesterImportRow::query()->where('organization_id', $organization->id)
            ->whereIn('bsn', [$row['owner_bsn'], $row['target_bsn']])->orderBy('id');

        $typesQuery = $organization->voucher_pass_types()->whereIn('name', $row['type_names'])->orderBy('id');
        $fundsQuery = $organization->funds()->whereKey($row['fund_ids'])->orderBy('id');

        if ($lock) {
            $importsQuery->lockForUpdate();
            $typesQuery->lockForUpdate();
            $fundsQuery->lockForUpdate();
        }

        /** @var Collection<array-key, RequesterImportRow> $imports */
        $imports = $importsQuery->with(['profile' => function (BelongsTo $query) use ($lock) {
            if ($lock) {
                $query->orderBy('id')->lockForUpdate();
            }
        }])->get()->keyBy('bsn');

        /** @var Collection<array-key, VoucherPassType> $types */
        $types = $typesQuery->with(['funds' => function (BelongsToMany $query) use ($lock) {
            if ($lock) {
                $query->orderBy('funds.id')->lockForUpdate();
            }
        }])->get()->keyBy('name');

        $funds = $fundsQuery->get();
        $errors = [];

        foreach (['owner_bsn', 'target_bsn'] as $field) {
            if ($imports->get($row[$field])?->profile?->organization_id !== $organization->id) {
                $errors[$field][] = trans('voucher_pass::validation.import_identity_required');
            }
        }

        foreach ($submittedTypeNames as $index => $name) {
            $type = $types->get($name);

            if (!$type) {
                $errors['type_names.' . $index][] = trans('voucher_pass::validation.type_unknown', ['name' => $name]);

                continue;
            }

            if ($type->funds->isEmpty() || $type->funds->contains(fn (Fund $fund) =>
                $fund->organization_id !== $organization->id)) {
                $errors['type_names.' . $index][] = trans('voucher_pass::validation.type_funds_invalid', [
                    'name' => $name,
                ]);
            }
        }

        foreach ($submittedFundIds as $index => $fundId) {
            if (!$funds->contains('id', $fundId)) {
                $errors['fund_ids.' . $index][] = trans('voucher_pass::validation.fund_unavailable');
            }
        }

        if ($row['type_names'] === [] && $row['fund_ids'] === []) {
            $errors['fund_ids'][] = trans('voucher_pass::validation.fund_selection_required');
        }

        $ownerId = $imports->get($row['owner_bsn'])?->profile?->identity_id;
        $targetId = $imports->get($row['target_bsn'])?->profile?->identity_id;

        if ($pass !== null) {
            if ($ownerId !== null && $ownerId !== $pass->owner_identity_id) {
                $errors['owner_bsn'][] = trans('voucher_pass::validation.owner_immutable');
            }

            if ($pass->external_id !== null && $pass->external_id !== $row['external_id']) {
                $errors['external_id'][] = trans('voucher_pass::validation.external_id_immutable');
            }

            if ($row['external_id'] !== null && $organization->voucher_passes()
                ->where('external_id', $row['external_id'])->whereKeyNot($pass->id)->exists()) {
                $errors['external_id'][] = trans('voucher_pass::validation.uid_in_use');
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return new VoucherPassData($row, $ownerId, $targetId, $types->modelKeys());
    }

    /**
     * @param VoucherPass|null $pass
     * @return array
     */
    private function rowValues(?VoucherPass $pass): array
    {
        if ($pass === null) {
            return array_fill_keys(VoucherPassRowsRule::FIELDS, null);
        }

        $pass->loadMissing('types', 'explicit_funds', 'owner_identity.record_bsn', 'target_identity.record_bsn');

        $names = $pass->types->pluck('name')->all();
        $fundIds = $pass->explicit_funds->modelKeys();

        sort($names, SORT_STRING);
        sort($fundIds, SORT_NUMERIC);

        return [
            'external_id' => $pass->external_id,
            'owner_bsn' => $pass->owner_identity->bsn,
            'target_bsn' => $pass->target_identity->bsn,
            'type_names' => $names,
            'fund_ids' => $fundIds,
            'print_label' => $pass->print_label,
            'state' => $pass->state,
            'print_requested' => $pass->print_requested,
        ];
    }

    /**
     * @param array $previous
     * @param array $values
     * @return array<int, VoucherPassChange>
     */
    private function calculateChanges(array $previous, array $values): array
    {
        $changes = [];

        foreach (VoucherPassRowsRule::FIELDS as $field) {
            if ($previous[$field] !== $values[$field]) {
                $changes[] = new VoucherPassChange($field, $previous[$field], $values[$field]);
            }
        }

        return $changes;
    }
}
