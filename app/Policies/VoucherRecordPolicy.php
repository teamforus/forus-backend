<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\Voucher;
use App\Models\VoucherRecord;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class VoucherRecordPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param Identity $identity
     * @param Organization $organization
     * @param Voucher $voucher
     * @return Response|bool
     */
    public function viewAny(
        Identity $identity,
        Voucher $voucher,
        Organization $organization
    ): Response|bool {
        return $this->validateEndpoint($voucher, $organization) &&
            $organization->identityCan($identity, ['manage_vouchers', 'view_vouchers'], false);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param Identity $identity
     * @param Organization $organization
     * @param Voucher $voucher
     * @param VoucherRecord $voucherRecord
     * @return Response|bool
     */
    public function view(
        Identity $identity,
        VoucherRecord $voucherRecord,
        Voucher $voucher,
        Organization $organization
    ): Response|bool {
        return $this->update($identity, $voucherRecord, $voucher, $organization);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param Identity $identity
     * @param Organization $organization
     * @param Voucher $voucher
     * @return Response|bool
     */
    public function create(
        Identity $identity,
        Voucher $voucher,
        Organization $organization
    ): Response|bool {
        return $this->validateEndpoint($voucher, $organization) &&
            $organization->identityCan($identity, 'manage_vouchers');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param Identity $identity
     * @param Organization $organization
     * @param Voucher $voucher
     * @param VoucherRecord $voucherRecord
     * @return Response|bool
     */
    public function update(
        Identity $identity,
        VoucherRecord $voucherRecord,
        Voucher $voucher,
        Organization $organization
    ): Response|bool {
        if (!$this->validateEndpoint($voucher, $organization)) {
            return false;
        }

        if (!$organization->identityCan($identity, 'manage_vouchers')) {
            return $this->deny('no_permission');
        }

        return $voucherRecord->voucher_id == $voucher->id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param Identity $identity
     * @param Organization $organization
     * @param Voucher $voucher
     * @param VoucherRecord $voucherRecord
     * @return Response|bool
     */
    public function delete(
        Identity $identity,
        VoucherRecord $voucherRecord,
        Voucher $voucher,
        Organization $organization
    ): Response|bool {
        return $this->update($identity, $voucherRecord, $voucher, $organization);
    }

    /**
     * @param Organization $organization
     * @param Voucher $voucher
     * @return bool
     */
    protected function validateEndpoint(Voucher $voucher, Organization $organization,): bool
    {
        $belongsToOrganization = $voucher->fund->organization_id === $organization->id;

        return $voucher->fund?->fund_config?->allow_voucher_records && $belongsToOrganization;
    }
}
