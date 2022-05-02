<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\FundRequestClarification;
use App\Models\FundRequestRecord;
use App\Scopes\Builders\EmployeeQuery;
use App\Services\FileService\Models\File;
use Illuminate\Auth\Access\HandlesAuthorization;

class FilePolicy
{
    use HandlesAuthorization;

    /**
     * @param $identity_address
     * @return mixed
     */
    public function viewAny($identity_address): bool
    {
        return !empty($identity_address);
    }

    /**
     * @param string $identity_address
     * @param File $file
     * @return bool
     */
    public function show(string $identity_address, File $file): bool
    {
        if (empty($identity_address)) {
            return false;
        }

        // is file owner/creator
        if (strcmp($file->identity_address, $identity_address) === 0) {
            return true;
        }

        // is fund request proof
        if (strcmp($file->type, 'fund_request_record_proof')  === 0) {
            // is fund validator
            return ($file->fileable instanceof FundRequestRecord) && EmployeeQuery::whereCanValidateRecords(
                Employee::whereIdentityAddress($identity_address),
                (array) $file->fileable->id
            )->exists();
        }

        // is fund request proof
        if (strcmp($file->type, 'fund_request_clarification_proof')  === 0) {
            // is fund validator
            return ($file->fileable instanceof FundRequestClarification) && EmployeeQuery::whereCanValidateRecords(
                Employee::whereIdentityAddress($identity_address),
                $file->fileable->fund_request_record()->select('fund_request_records.id')->getQuery()
            )->exists();
        }

        return false;
    }

    /**
     * @param string $identity_address
     * @param File $file
     * @return bool
     */
    public function download(string $identity_address, File $file): bool
    {
        return $this->show($identity_address, $file);
    }

    /**
     * @param string $identity_address
     * @return bool
     */
    public function store(string $identity_address): bool
    {
        return !empty($identity_address);
    }

    /**
     * @param string $identity_address
     * @param File $file
     * @return bool
     */
    public function destroy(string $identity_address, File $file): bool
    {
        return (strcmp($file->identity_address, $identity_address) === 0);
    }
}
