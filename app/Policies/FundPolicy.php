<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class FundPolicy
{
    use HandlesAuthorization;

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function viewAny($identity_address, Organization $organization): bool
    {
        return $organization->identityCan($identity_address, [
            'manage_funds', 'view_finances', 'view_funds',
        ], false);
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function store($identity_address, Organization $organization): bool
    {
        return $organization->identityCan($identity_address, 'manage_funds');
    }

    /**
     * @param $identity_address
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     */
    public function show($identity_address, Fund $fund, Organization $organization): bool
    {
        if ($fund->organization_id !== $organization->id) {
            return false;
        }

        return $fund->public || $fund->organization->identityCan(
            $identity_address,
            ['manage_funds', 'view_finances', 'view_funds'],
            false
        );
    }

    /**
     * @param $identity_address
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     */
    public function update($identity_address, Fund $fund, Organization $organization): bool
    {
        if ($fund->organization_id !== $organization->id) {
            return false;
        }

        return $fund->organization->identityCan($identity_address, 'manage_funds');
    }

    /**
     * @param $identity_address
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     */
    public function archive($identity_address, Fund $fund, Organization $organization): bool
    {
        return !$fund->archived &&
            $fund->state == Fund::STATE_CLOSED &&
            $this->update($identity_address, $fund, $organization);
    }

    /**
     * @param $identity_address
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     */
    public function unarchive($identity_address, Fund $fund, Organization $organization): bool
    {
        return $fund->archived && $this->update($identity_address, $fund, $organization);
    }

    /**
     * @param $identity_address
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function updateBackoffice($identity_address, Fund $fund, Organization $organization): bool
    {
        return $organization->backoffice_available && $fund->fund_config &&
            $this->update($identity_address, $fund, $organization);
    }

    /**
     * @param $identity_address
     * @param Fund $fund
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function apply($identity_address, Fund $fund)
    {
        if (empty($identity_address)) {
            return false;
        }

        if ($fund->state !== $fund::STATE_ACTIVE) {
            return $this->deny(trans('fund.state_' . $fund->state));
        }

        if ($fund->fund_config->hash_partner_deny && $fund->isTakenByPartner($identity_address)) {
            return $this->deny(trans('fund.taken_by_partner'));
        }

        // The same identity can't apply twice to the same fund
        if ($fund->identityHasActiveVoucher($identity_address)) {
            return $this->deny(trans('fund.already_received'));
        }

        // Check criteria
        $invalidCriteria = $fund->criteria->filter(static function(
            FundCriterion $criterion
        ) use ($identity_address, $fund) {
            return collect([$fund->getTrustedRecordOfType(
                $identity_address,
                $criterion->record_type_key,
                $criterion
            )])->where('value', $criterion->operator, $criterion->value )->count() === 0;
        });

        if ($invalidCriteria->count() > 0) {
            return $this->deny(trans('fund.unmet_criteria'));
        }

        return true;
    }

    /**
     * @param $identity_address
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     */
    public function showFinances($identity_address, Fund $fund, Organization $organization): bool
    {
        if ($fund->organization_id !== $organization->id) {
            return false;
        }

        return $fund->public ||
            $fund->organization->identityCan($identity_address, 'view_finances');
    }

    /**
     * @param $identity_address
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     */
    public function manageVouchers($identity_address, Fund $fund, Organization $organization): bool
    {
        if ($fund->organization_id !== $organization->id) {
            return false;
        }

        return $fund->organization->identityCan($identity_address, 'manage_vouchers');
    }

    /**
     * @param $identity_address
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     */
    public function destroy($identity_address, Fund $fund, Organization $organization): bool
    {
        if ($fund->organization_id !== $organization->id) {
            return false;
        }

        return $organization->identityCan($identity_address, 'manage_funds') &&
            $fund->state === Fund::STATE_WAITING;
    }

    /**
     * @param $identity_address
     * @param Fund $fund
     * @return bool
     */
    public function idealRequest($identity_address, Fund $fund): bool
    {
        return $identity_address && $fund->public;
    }
}
