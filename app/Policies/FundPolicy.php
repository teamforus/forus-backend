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
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function index(
        $identity_address,
        Organization $organization
    ) {
        return $organization->identityCan(
            $identity_address, [
                'manage_funds', 'view_finances', 'view_funds',
            ], false
        );
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function store(
        $identity_address,
        Organization $organization
    ) {
        return $organization->identityCan(
            $identity_address,
            'manage_funds'
        );
    }

    /**
     * @param $identity_address
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     */
    public function show(
        $identity_address,
        Fund $fund,
        Organization $organization
    ) {
        if ($fund->organization_id != $organization->id) {
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
    public function update(
        $identity_address,
        Fund $fund,
        Organization $organization
    ) {
        if ($fund->organization_id != $organization->id) {
            return false;
        }

        return $fund->organization->identityCan(
            $identity_address,
            'manage_funds'
        );
    }

    /**
     * @param $identity_address
     * @param Fund $fund
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function apply(
        $identity_address,
        Fund $fund
    ) {
        if (empty($identity_address) && $fund->state != Fund::STATE_ACTIVE) {
            return false;
        }

        if (!$fund->fund_formulas()->count() > 0) {
            $this->deny(trans('fund.no_formula'));
        }

        // The same identity can't apply twice to the same fund
        if ($fund->vouchers()->where(
            'identity_address', $identity_address
        )->count()) {
            $this->deny(trans('fund.already_received'));
        }

        // Check criteria
        $invalidCriteria = $fund->criteria->filter(function(
            FundCriterion $criterion
        ) use (
            $identity_address, $fund
        ) {
            $record = Fund::getTrustedRecordOfType(
                $fund, auth()->id(), $criterion->record_type_key, $fund->organization
            );

            return (collect([$record])->where(
                'value', $criterion->operator, $criterion->value
                )->count() == 0);
        });

        if ($invalidCriteria->count() > 0) {
            $this->deny(trans('fund.unmet_criteria'));
        }

        return true;
    }

    /**
     * @param $identity_address
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     */
    public function showFinances(
        $identity_address,
        Fund $fund,
        Organization $organization
    ) {
        if ($fund->organization_id != $organization->id) {
            return false;
        }

        return $fund->public || $fund->organization->identityCan(
            $identity_address,
            'view_finances'
        );
    }

    /**
     * @param $identity_address
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     */
    public function destroy(
        $identity_address,
        Fund $fund,
        Organization $organization
    ) {
        return $organization->identityCan($identity_address, [
            'manage_funds'
        ]) && $fund->state == Fund::STATE_WAITING;
    }

    /**
     * @param $identity_address
     * @param Fund $fund
     * @return bool
     */
    public function idealRequest(
        $identity_address,
        Fund $fund
    ) {
        // identity_address not required
        return $identity_address && $fund->public;
    }
}
