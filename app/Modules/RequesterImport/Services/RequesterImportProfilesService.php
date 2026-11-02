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

class RequesterImportProfilesService
{
    /**
     * @param RequesterImportRow $row
     * @param Employee $employee
     * @return Profile
     */
    public function syncRow(RequesterImportRow $row, Employee $employee): Profile
    {
        return DB::transaction(function () use ($row, $employee): Profile {
            $row = RequesterImportRow::where('organization_id', $row->organization_id)
                ->lockForUpdate()->findOrFail($row->id);

            abort_unless($row->organization_id === $employee->organization_id, 403);

            $search = new RecordTypeSearch([
                'requester_imports' => true,
                'organization_id' => $row->organization_id,
            ], RecordType::query()->where('key', '!=', RequesterImportRecord::CLIENT_UUID));

            $recordTypes = $search->query()->with('record_type_options.translations')->get()->keyBy('key');
            $records = $row->records()->whereIn('record_type_key', $recordTypes->keys())
                ->whereNull('synced_at')->lockForUpdate()->get()->keyBy('record_type_key');

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

            return $profile;
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
