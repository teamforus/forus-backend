<?php

namespace App\Modules\RequesterImport\Services;

use App\Models\Employee;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Profile;
use App\Modules\RequesterImport\Exceptions\RequesterImportErrorCode;
use App\Modules\RequesterImport\Exceptions\RequesterImportException;
use App\Modules\RequesterImport\Models\RequesterImportRow;
use App\Services\IdentityBsnService\Exceptions\IdentityBsnException;
use App\Services\IdentityBsnService\IdentityBsnService;

class RequesterImportProfilesService
{
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
