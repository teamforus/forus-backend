<?php

namespace App\Policies;

use App\Models\FundRequestClarification;
use App\Models\FundRequestRecord;
use App\Models\Identity;
use App\Models\Permission;
use App\Models\Reimbursement;
use App\Services\FileService\Models\File;
use Illuminate\Auth\Access\HandlesAuthorization;

class FilePolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @return bool
     */
    public function viewAny(Identity $identity): bool
    {
        return $identity->exists && $identity->address;
    }

    /**
     * @param Identity $identity
     * @param File $file
     * @return bool
     */
    public function show(Identity $identity, File $file): bool
    {
        if (!$identity->exists || !$identity->address) {
            return false;
        }

        if ($file->type === 'uploaded_csv_details') {
            return false;
        }

        // is file owner/creator
        if ($file->identity_address === $identity->address) {
            return true;
        }

        // is fund request proof
        if ($file->type === 'reimbursement_proof') {
            $reimbursement = $file->fileable instanceof Reimbursement ? $file->fileable : null;

            // is fund validator
            return $reimbursement && $reimbursement->voucher?->fund?->organization?->identityCan(
                $identity,
                Permission::MANAGE_REIMBURSEMENTS,
            );
        }

        // is fund request proof
        if ($file->type === 'fund_request_record_proof') {
            $fundRequestRecord = $file->fileable instanceof FundRequestRecord ? $file->fileable : null;

            // is fund validator
            return $fundRequestRecord
                ?->fund_request?->fund?->organization
                ?->identityCan($identity, Permission::VALIDATE_RECORDS);
        }

        // is fund request proof
        if ($file->type === 'fund_request_clarification_proof') {
            $clarification = $file->fileable instanceof FundRequestClarification ? $file->fileable : null;

            // is fund validator
            return $clarification
                ?->fund_request_record?->fund_request?->fund?->organization
                ?->identityCan($identity, Permission::VALIDATE_RECORDS);
        }

        return false;
    }

    /**
     * @param Identity $identity
     * @param File $file
     * @return bool
     */
    public function download(Identity $identity, File $file): bool
    {
        return $this->show($identity, $file);
    }

    /**
     * @param Identity $identity
     * @return bool
     */
    public function store(Identity $identity): bool
    {
        return $identity->exists && $identity->address;
    }

    /**
     * @param Identity $identity
     * @param File $file
     * @return bool
     */
    public function destroy(Identity $identity, File $file): bool
    {
        if (($file->fileable instanceof Reimbursement) && !$file->fileable->isDraft()) {
            return false;
        }

        if ($file->fileable && in_array($file->type, [
            'fund_request_record_proof',
            'fund_request_clarification_proof',
            'product_reservation_custom_field',
        ])) {
            return false;
        }

        return $identity->address === $file->identity_address;
    }
}
