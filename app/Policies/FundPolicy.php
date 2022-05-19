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

        return $fund->public || $fund->organization->identityCan($identity_address, [
            'manage_funds', 'view_finances', 'view_funds'
        ], false);
    }

    /**
     * @param $identity_address
     * @param Fund $fund
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     * @noinspection PhpUnused
     */
    public function topUp($identity_address, Fund $fund, Organization $organization)
    {
        $hasPermission = $this->show($identity_address, $fund, $organization);
        $bankConnection = $organization->bank_connection_active;

        if (!$fund->isConfigured()) {
            return false;
        }

        if (!$bankConnection || ($bankConnection->bank->isBunq() && !$bankConnection->useContext())) {
            return $this->deny("Bank connection invalid or expired.", 403);
        }

        if ($fund->isExternal()) {
            return $this->deny("Top-up not allowed for external funds", 403);
        }

        return $hasPermission;
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
        return $organization->backoffice_available &&
            $fund->isInternal() &&
            $fund->isConfigured() &&
            $this->update($identity_address, $fund, $organization);
    }

    /**
     * @param $identity_address
     * @param Fund $fund
     * @param string|null $logScope from where the policy is called
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function apply($identity_address, Fund $fund, ?string $logScope)
    {
        if (empty($identity_address)) {
            return false;
        }

        if (!$fund->isActive()) {
            return $this->deny(trans('fund.state_' . $fund->state));
        }

        if (!$fund->isInternal()) {
            return $this->deny(trans('fund.type_external'));
        }

        if (!$fund->isConfigured()) {
            return $this->deny(trans('fund.not_configured'));
        }

        if ($fund->isBackofficeApiAvailable() &&
            env('ENABLE_BACKOFFICE_PARTNER_CHECK', false) &&
            $bsn = record_repo()->bsnByAddress($identity_address)) {
            try {
                $response = $fund->getBackofficeApi()->partnerBsn($bsn);

                if (!$response->getLog()->success()) {
                    throw new \Exception(implode("", [
                        "Backoffice partner check response error: ",
                        "scope: $logScope, fund_id: $fund->id, identity_address: $identity_address",
                    ]));
                }

                $partnerBsn = $response->getBsn();
                $partnerAddress = $partnerBsn ? record_repo()->identityAddressByBsn($partnerBsn) : false;

                if ($partnerAddress && $fund->identityHasActiveVoucher($partnerAddress)) {
                    return $this->deny(trans('fund.taken_by_partner'));
                }
            } catch (\Throwable $e) {
                logger()->error("FundPolicy@apply: " . $e->getMessage());
                return $this->deny(trans('fund.backoffice_error'));
            }
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

        if (!$fund->isConfigured()) {
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

        if ($fund->isConfigured()) {
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
