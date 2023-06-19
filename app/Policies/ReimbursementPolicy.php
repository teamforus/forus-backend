<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Note;
use App\Models\Organization;
use App\Models\Reimbursement;
use App\Scopes\Builders\VoucherQuery;
use App\Traits\Policies\DeniesWithMeta;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ReimbursementPolicy
{
    use DeniesWithMeta;
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param bool $auth2FAConfirmed
     * @return Response|bool
     */
    public function viewAny(Identity $identity, bool $auth2FAConfirmed = false): Response|bool
    {
        if (!$identity->exists()) {
            return false;
        }

        return $this->validate2FAFeatureRestriction($identity, $auth2FAConfirmed);
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
     * @param Identity $identity
     * @param Reimbursement $reimbursement
     * @param bool $auth2FAConfirmed
     * @return Response|bool
     */
    public function view(
        Identity $identity,
        Reimbursement $reimbursement,
        bool $auth2FAConfirmed = false,
    ): Response|bool {
        if ($reimbursement->voucher->identity_address !== $identity->address) {
            return false;
        }

        return $this->validate2FAFeatureRestriction($identity, $auth2FAConfirmed);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param Identity $identity
     * @param Reimbursement $reimbursement
     * @param Organization $organization
     * @return bool
     */
    public function viewAsSponsor(
        Identity $identity,
        Reimbursement $reimbursement,
        Organization $organization,
    ): bool {
        return
            !$reimbursement->isDraft() &&
            $organization->identityCan($identity, 'manage_reimbursements') &&
            $reimbursement->voucher->fund->organization_id === $organization->id;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param Identity $identity
     * @param bool $auth2FAConfirmed
     * @return Response|bool
     */
    public function create(Identity $identity, bool $auth2FAConfirmed = false): Response|bool
    {
        $vouchersQuery = $identity->vouchers()->whereRelation('fund.fund_config', [
            'allow_reimbursements' => true,
        ]);

        if (VoucherQuery::whereNotExpiredAndActive($vouchersQuery)->doesntExist()) {
            return false;
        }

        return $this->validate2FAFeatureRestriction($identity, $auth2FAConfirmed);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param Identity $identity
     * @param Reimbursement $reimbursement
     * @param bool $auth2FAConfirmed
     * @return Response|bool
     */
    public function update(
        Identity $identity,
        Reimbursement $reimbursement,
        bool $auth2FAConfirmed = false,
    ): Response|bool {
        if ($reimbursement->isExpired() || !$reimbursement->isDraft()) {
            return false;
        }

        if ($reimbursement->voucher->identity_address !== $identity->address) {
            return false;
        }

        return $this->validate2FAFeatureRestriction($identity, $auth2FAConfirmed);
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
            return $this->denyWithMeta('invalid_endpoint');
        }

        if (!$organization->identityCan($identity, 'manage_reimbursements')) {
            return false;
        }

        if (!$reimbursement->isPending()) {
            return $this->denyWithMeta('not_pending');
        }

        if ($reimbursement->employee) {
            return $this->denyWithMeta('already_assigned');
        }

        if ($reimbursement->expired) {
            return $this->denyWithMeta('expired');
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
            return $this->denyWithMeta('invalid_endpoint');
        }

        if (!$organization->identityCan($identity, 'manage_reimbursements')) {
            return false;
        }

        if (!$reimbursement->isPending()) {
            return $this->denyWithMeta('not_pending');
        }

        if ($reimbursement->employee_id !== $organization->findEmployee($identity->address)?->id) {
            return $this->denyWithMeta('not_assigned');
        }

        if (!$reimbursement->employee) {
            return $this->denyWithMeta('not_assigned');
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
            return $this->denyWithMeta('invalid_endpoint');
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
            return $this->denyWithMeta('invalid_endpoint');
        }

        if (!$organization->identityCan($identity, 'manage_reimbursements')) {
            return false;
        }

        if ($reimbursement->employee?->identity_address !== $identity->address) {
            return $this->denyWithMeta('not_assigned');
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
            return $this->denyWithMeta('invalid_endpoint');
        }

        if (!$organization->identityCan($identity, 'manage_reimbursements')) {
            return false;
        }

        if ($note->employee?->identity_address !== $identity->address) {
            return $this->denyWithMeta('not_author');
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param Identity $identity
     * @param Reimbursement $reimbursement
     * @param bool $auth2FAConfirmed
     * @return Response|bool
     */
    public function delete(
        Identity $identity,
        Reimbursement $reimbursement,
        bool $auth2FAConfirmed = false,
    ): Response|bool {
        if (!$reimbursement->isDraft()) {
            return $this->deny('Only draft requests can be canceled.');
        }

        if ($reimbursement->voucher->identity_address !== $identity->address) {
            return false;
        }

        return $this->validate2FAFeatureRestriction($identity, $auth2FAConfirmed);
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

    /**
     * @param Identity $identity
     * @param bool $auth2FAConfirmed
     * @return Response|bool
     */
    protected function validate2FAFeatureRestriction(Identity $identity, bool $auth2FAConfirmed = false): Response|bool
    {
        if ($identity->isFeature2FARestricted('reimbursements') && !$auth2FAConfirmed) {
            return $this->deny('Invalid 2FA state.');
        }

        return true;
    }
}
