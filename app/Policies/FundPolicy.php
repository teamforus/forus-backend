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
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        $identity_address,
        Organization $organization = null
    ) {
        return $this->store($identity_address, $organization);
    }

    /**
     * @param $identity_address
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        $identity_address,
        Organization $organization = null
    ) {
        if ($organization) {
            authorize('update', $organization);
        }

        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        $identity_address,
        Fund $fund,
        Organization $organization = null
    ) {
        return $this->update($identity_address, $fund, $organization);
    }

    /**
     * @param $identity_address
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        $identity_address,
        Fund $fund,
        Organization $organization = null
    ) {
        if ($organization) {
            authorize('update', $organization);

            if ($fund->organization_id != $organization->id) {
                return false;
            }
        }

        return strcmp(
                $fund->organization->identity_address, $identity_address) == 0;
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
        if (empty($identity_address) && ($fund->state != 'active')) {
            return false;
        }

        if (!$fund->getFundFormula()) {
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
                $fund, auth()->id(), $criterion->record_type_key
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
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showFinances(
        $identity_address,
        Fund $fund,
        Organization $organization = null
    ) {
        return $this->update($identity_address, $fund, $organization);
    }
}
