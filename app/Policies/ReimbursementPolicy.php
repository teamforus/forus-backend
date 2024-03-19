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
     *
     * @return Response|true
     */
    protected function validate2FAFeatureRestriction(Identity $identity, bool $auth2FAConfirmed = false): bool|Response|bool
    {
        if ($identity->load('funds')->isFeature2FARestricted('reimbursements') && !$auth2FAConfirmed) {
            return $this->deny('Invalid 2FA state.');
        }

        return true;
    }
}
