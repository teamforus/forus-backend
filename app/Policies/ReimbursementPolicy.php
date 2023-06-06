<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Note;
use App\Models\Organization;
use App\Models\Reimbursement;
use App\Scopes\Builders\VoucherQuery;
use App\Traits\Policies\DeniesWithMeta;
use Illuminate\Auth\Access\Response;

class ReimbursementPolicy
{
    use DeniesWithMeta;

    /**
     * Determine whether the user can view any models.
     *
     * @param Identity $identity
     * @return bool
     */
    public function viewAny(Identity $identity): bool
    {
        return $identity->exists();
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function viewAnyAsSponsor(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, 'manage_reimbursements');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\Identity  $identity
     * @param  \App\Models\Reimbursement  $reimbursement
     * @return bool
     */
    public function view(Identity $identity, Reimbursement $reimbursement): bool
    {
        return $reimbursement->voucher->identity_address === $identity->address;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param Identity $identity
     * @param Reimbursement $reimbursement
     * @param Organization $organization
     * @return bool
     */
    public function viewAsSponsor(Identity $identity, Reimbursement $reimbursement, Organization $organization): bool
    {
        return
            !$reimbursement->isDraft() &&
            $organization->identityCan($identity, 'manage_reimbursements') &&
            $reimbursement->voucher->fund->organization_id === $organization->id;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param Identity $identity
     * @return bool
     */
    public function create(Identity $identity): bool
    {
        $vouchersQuery = $identity->vouchers()->whereRelation('fund.fund_config', [
            'allow_reimbursements' => true,
        ]);

        return VoucherQuery::whereNotExpiredAndActive($vouchersQuery)->exists();
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param Identity $identity
     * @param Reimbursement $reimbursement
     * @return bool
     */
    public function update(Identity $identity, Reimbursement $reimbursement): bool
    {
        return
            !$reimbursement->expired &&
            $reimbursement->isDraft() &&
            $reimbursement->voucher->identity_address === $identity->address;
    }

    /**
     * Determine whether the sponsor can update the model.
     *
     * @param Identity $identity
     * @param Reimbursement $reimbursement
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function updateAsSponsor(
        Identity $identity,
        Reimbursement $reimbursement,
        Organization $organization
    ): Response|bool {
        if (!$this->checkIntegrity($reimbursement, $organization)) {
            return $this->deny('Ongeldig eindpunt');
        }

        if (!$organization->identityCan($identity, 'manage_reimbursements')) {
            return false;
        }

        if (!$reimbursement->employee ||
            $reimbursement->employee_id !== $organization->findEmployee($identity->address)?->id) {
            return $this->deny('Niet toegewezen');
        }

        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param Identity $identity
     * @param Reimbursement $reimbursement
     * @param Organization $organization
     * @return Response|bool
     */
    public function resolve(
        Identity $identity,
        Reimbursement $reimbursement,
        Organization $organization
    ): Response|bool {
        if (!$this->checkIntegrity($reimbursement, $organization)) {
            return $this->deny('Ongeldig eindpunt');
        }

        if (!$organization->identityCan($identity, 'manage_reimbursements')) {
            return false;
        }

        if (!$reimbursement->isPending()) {
            return $this->deny('Niet in behandeling');
        }

        if (!$reimbursement->employee ||
            $reimbursement->employee_id !== $organization->findEmployee($identity->address)?->id) {
            return $this->deny('Niet toegewezen');
        }

        if ($reimbursement->voucher->isDeactivated()) {
            return $this->deny('Tegoed gedeactiveerd');
        }

        if ($reimbursement->expired) {
            return $this->deny('Declaratie(voucher of fonds) verlopen');
        }

        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param Identity $identity
     * @param Reimbursement $reimbursement
     * @param Organization $organization
     * @return Response|bool
     */
    public function assign(
        Identity $identity,
        Reimbursement $reimbursement,
        Organization $organization
    ): Response|bool {
        if (!$this->checkIntegrity($reimbursement, $organization)) {
            return $this->deny('invalid_endpoint');
        }

        if (!$organization->identityCan($identity, 'manage_reimbursements')) {
            return false;
        }

        if (!$reimbursement->isPending()) {
            return $this->deny('not_pending');
        }

        if ($reimbursement->employee) {
            return $this->deny('already_assigned');
        }

        if ($reimbursement->expired) {
            return $this->deny('expired');
        }

        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param Identity $identity
     * @param Reimbursement $reimbursement
     * @param Organization $organization
     * @return Response|bool
     */
    public function resign(
        Identity $identity,
        Reimbursement $reimbursement,
        Organization $organization
    ): Response|bool {
        if (!$this->checkIntegrity($reimbursement, $organization)) {
            return $this->deny('invalid_endpoint');
        }

        if (!$organization->identityCan($identity, 'manage_reimbursements')) {
            return false;
        }

        if (!$reimbursement->isPending()) {
            return $this->deny('not_pending');
        }

        if ($reimbursement->employee_id !== $organization->findEmployee($identity->address)?->id) {
            return $this->deny('not_assigned');
        }

        if (!$reimbursement->employee) {
            return $this->deny('not_assigned');
        }

        return true;
    }

    /**
     * Determine whether the user can view reimbursement notes.
     *
     * @param Identity $identity
     * @param Reimbursement $reimbursement
     * @param Organization $organization
     * @return Response|bool
     */
    public function viewAnyNoteAsSponsor(
        Identity $identity,
        Reimbursement $reimbursement,
        Organization $organization
    ): Response|bool {
        if (!$this->checkIntegrity($reimbursement, $organization)) {
            return $this->deny('invalid_endpoint');
        }

        if (!$organization->identityCan($identity, 'manage_reimbursements')) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can store reimbursement note.
     *
     * @param Identity $identity
     * @param Reimbursement $reimbursement
     * @param Organization $organization
     * @return Response|bool
     */
    public function storeNoteAsSponsor(
        Identity $identity,
        Reimbursement $reimbursement,
        Organization $organization
    ): Response|bool {

        if (!$this->checkIntegrity($reimbursement, $organization)) {
            return $this->deny('invalid_endpoint');
        }

        if (!$organization->identityCan($identity, 'manage_reimbursements')) {
            return false;
        }

        if ($reimbursement->employee?->identity_address !== $identity->address) {
            return $this->deny('not_assigned');
        }

        return true;
    }

    /**
     * Determine whether the user can delete reimbursement note.
     *
     * @param Identity $identity
     * @param Reimbursement $reimbursement
     * @param Organization $organization
     * @param Note $note
     * @return Response|bool
     */
    public function destroyNoteAsSponsor(
        Identity $identity,
        Reimbursement $reimbursement,
        Organization $organization,
        Note $note
    ): Response|bool {
        if (!$this->checkIntegrity($reimbursement, $organization)) {
            return $this->deny('invalid_endpoint');
        }

        if (!$organization->identityCan($identity, 'manage_reimbursements')) {
            return false;
        }

        if ($note->employee?->identity_address !== $identity->address) {
            return $this->deny('not_author');
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param Identity $identity
     * @param Reimbursement $reimbursement
     * @return Response|bool
     */
    public function delete(Identity $identity, Reimbursement $reimbursement): Response|bool
    {
        if (!$reimbursement->isDraft()) {
            return $this->deny('Only draft requests can be canceled.');
        }

        return $reimbursement->voucher->identity_address === $identity->address;
    }

    /**
     * @param Reimbursement $reimbursement
     * @param Organization $organization
     * @return bool
     */
    private function checkIntegrity(Reimbursement $reimbursement, Organization $organization): bool
    {
        return $reimbursement->voucher->fund->organization_id === $organization->id;
    }
}
