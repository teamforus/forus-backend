<?php

namespace App\Policies;

use App\Models\FundRequestClarification;
use App\Models\FundRequestRecord;
use App\Models\Identity;
use App\Models\Permission;
use App\Models\ProductReservationFieldValue;
use App\Models\Reimbursement;
use App\Services\FileService\FilePdfPreviewService;
use App\Services\FileService\Models\File;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class FilePolicy
{
    use HandlesAuthorization;

    public function __construct(protected FilePdfPreviewService $filePdfPreviewService)
    {
    }

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

        if ($file->fileable && $file->type === 'product_reservation_custom_field') {
            return
                $this->canViewOwnProductReservationFile($identity, $file) ||
                $this->canViewProductReservationFile($identity, $file);
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
        if ($this->filePdfPreviewService->usesPdfPreviewPages($file)) {
            return false;
        }

        return $this->show($identity, $file);
    }

    /**
     * @param Identity $identity
     * @param File $file
     * @return bool
     */
    public function downloadArchive(Identity $identity, File $file): bool
    {
        if (
            !$identity->exists ||
            !$identity->address ||
            !$this->filePdfPreviewService->usesPdfPreviewPages($file)
        ) {
            return false;
        }

        if ($file->fileable_id || $file->fileable_type) {
            return $file->fileable && $this->canViewProductReservationFile($identity, $file);
        }

        return $file->identity_address === $identity->address;
    }

    /**
     * @param Identity $identity
     * @param File $file
     * @return bool
     */
    public function downloadPreviewArchive(Identity $identity, File $file): bool
    {
        if (
            !$identity->exists ||
            !$identity->address ||
            !$this->filePdfPreviewService->usesPdfPreviewPages($file)
        ) {
            return false;
        }

        if ($file->fileable_id || $file->fileable_type) {
            return $file->fileable && (
                $this->canViewOwnProductReservationFile($identity, $file) ||
                $this->canViewProductReservationFile($identity, $file)
            );
        }

        return $file->identity_address === $identity->address;
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

        if ($file->fileable && $file->type === 'product_reservation_custom_field') {
            return $this->canUpdateProductReservationFile($identity, $file);
        }

        if ($file->fileable && in_array($file->type, [
            'fund_request_record_proof',
            'fund_request_clarification_proof',
        ])) {
            return false;
        }

        return $identity->address === $file->identity_address;
    }

    /**
     * @param Identity $identity
     * @param File $file
     * @return bool
     */
    protected function canUpdateProductReservationFile(Identity $identity, File $file): bool
    {
        $fieldValue = $file->fileable instanceof ProductReservationFieldValue ? $file->fileable : null;
        $reservation = $fieldValue?->product_reservation;
        $field = $fieldValue?->reservation_field;
        $organization = $reservation?->product?->organization;

        return
            $field &&
            $reservation &&
            $organization &&
            Gate::forUser($identity)->allows('updateCustomField', [$reservation, $organization, $field]);
    }

    /**
     * @param Identity $identity
     * @param File $file
     * @return bool
     */
    protected function canViewProductReservationFile(Identity $identity, File $file): bool
    {
        $fieldValue = $file->fileable instanceof ProductReservationFieldValue ? $file->fileable : null;
        $reservation = $fieldValue?->product_reservation;
        $organization = $reservation?->product?->organization;

        return
            $reservation &&
            $organization &&
            Gate::forUser($identity)->allows('viewProvider', [$reservation, $organization]);
    }

    /**
     * @param Identity $identity
     * @param File $file
     * @return bool
     */
    protected function canViewOwnProductReservationFile(Identity $identity, File $file): bool
    {
        $fieldValue = $file->fileable instanceof ProductReservationFieldValue ? $file->fileable : null;
        $reservation = $fieldValue?->product_reservation;

        return $reservation && $reservation->voucher?->identity_id === $identity->id;
    }
}
