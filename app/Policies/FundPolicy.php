<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\Identity;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class FundPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function viewAny(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, [
            'manage_funds', 'view_finances', 'view_funds',
        ], false);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function store(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, 'manage_funds');
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     */
    public function show(Identity $identity, Fund $fund, Organization $organization): bool
    {
        if ($fund->organization_id !== $organization->id) {
            return false;
        }

        return $fund->public || $fund->organization->identityCan($identity, [
            'manage_funds', 'view_finances', 'view_funds'
        ], false);
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function showIdentities(Identity $identity, Fund $fund, Organization $organization): bool
    {
        if ($fund->organization_id !== $organization->id) {
            return false;
        }

        return $fund->organization->identityCan($identity, 'manage_vouchers');
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function showIdentitiesOverview(Identity $identity, Fund $fund, Organization $organization): bool
    {
        if ($fund->organization_id !== $organization->id) {
            return false;
        }

        return $fund->organization->identityCan($identity, [
            'manage_implementation_notifications', 'manage_vouchers'
        ], false);
    }


    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function sendIdentityNotifications(Identity $identity, Fund $fund, Organization $organization): bool
    {
        if ($fund->organization_id !== $organization->id) {
            return false;
        }

        if (!$fund->organization->allow_custom_fund_notifications) {
            return false;
        }

        return $fund->organization->identityCan($identity, 'manage_implementation_notifications');
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function topUp(Identity $identity, Fund $fund, Organization $organization): Response|bool
    {
        $hasPermission = $this->show($identity, $fund, $organization);
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
     * @param Identity $identity
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     */
    public function update(Identity $identity, Fund $fund, Organization $organization): bool
    {
        if ($fund->organization_id !== $organization->id) {
            return false;
        }

        return $fund->organization->identityCan($identity, 'manage_funds');
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function archive(Identity $identity, Fund $fund, Organization $organization): bool
    {
        return !$fund->isArchived() && $fund->isClosed() && $this->update($identity, $fund, $organization);
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function unarchive(Identity $identity, Fund $fund, Organization $organization): bool
    {
        return $fund->archived && $this->update($identity, $fund, $organization);
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function updateBackoffice(Identity $identity, Fund $fund, Organization $organization): bool
    {
        return $organization->backoffice_available &&
            $fund->isInternal() &&
            $fund->isConfigured() &&
            $this->update($identity, $fund, $organization);
    }

    /**
     *
     * @param Identity $identity
     * @param Fund $fund
     * @param string|null $logScope from where the policy is called
     * @return Response|bool
     */
    public function apply(Identity $identity, Fund $fund, ?string $logScope): Response|bool
    {
        if (!$fund->isActive()) {
            return $this->deny(trans('fund.state_' . $fund->state));
        }

        if (!$fund->isInternal()) {
            return $this->deny(trans('fund.type_external'));
        }

        if (!$fund->isConfigured()) {
            return $this->deny(trans('fund.not_configured'));
        }

        $backofficePartnerCheck = (bool) env('ENABLE_BACKOFFICE_PARTNER_CHECK', false);

        if ($fund->isBackofficeApiAvailable() && $backofficePartnerCheck && $identity->bsn) {
            try {
                $response = $fund->getBackofficeApi()->partnerBsn($identity->bsn);

                if (!$response->getLog()->success()) {
                    throw new \Exception(implode("", [
                        "Backoffice partner check response error: ",
                        "scope: $logScope, fund_id: $fund->id, identity_address: $identity->address",
                    ]));
                }

                $partnerBsn = $response->getBsn();
                $partner = $partnerBsn ? Identity::findByBsn($partnerBsn) : false;

                if ($partner && $fund->identityHasActiveVoucher($partner)) {
                    return $this->deny(trans('fund.taken_by_partner'));
                }
            } catch (\Throwable $e) {
                logger()->error("FundPolicy@apply: " . $e->getMessage());
                return $this->deny(trans('fund.backoffice_error'));
            }
        }

        if ($fund->fund_config->hash_partner_deny && $fund->isTakenByPartner($identity)) {
            return $this->deny(trans('fund.taken_by_partner'));
        }

        // The same identity can't apply twice to the same fund
        if ($fund->identityHasActiveVoucher($identity)) {
            return $this->deny(trans('fund.already_received'));
        }

        // Check criteria
        $invalidCriteria = $fund->criteria->filter(static function(FundCriterion $criterion) use ($identity, $fund) {
            return collect([$fund->getTrustedRecordOfType(
                $identity->address,
                $criterion->record_type_key,
                $criterion
            )])->where('value', $criterion->operator, $criterion->value)->count() === 0;
        });

        if ($invalidCriteria->count() > 0) {
            return $this->deny(trans('fund.unmet_criteria'));
        }

        return true;
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function showFinances(Identity $identity, Fund $fund, Organization $organization): bool
    {
        if ($fund->organization_id !== $organization->id) {
            return false;
        }

        if (!$fund->isConfigured()) {
            return false;
        }

        return $fund->public ||
            $fund->organization->identityCan($identity, 'view_finances');
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function manageVouchers(Identity $identity, Fund $fund, Organization $organization): bool
    {
        if ($fund->organization_id !== $organization->id) {
            return false;
        }

        if ($fund->isConfigured()) {
            return false;
        }

        return $fund->organization->identityCan($identity, 'manage_vouchers');
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     */
    public function destroy(Identity $identity, Fund $fund, Organization $organization): bool
    {
        if ($fund->organization_id !== $organization->id) {
            return false;
        }

        return $organization->identityCan($identity, 'manage_funds') &&
            $fund->state === Fund::STATE_WAITING;
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @return bool
     * @noinspection PhpUnused
     */
    public function idealRequest(Identity $identity, Fund $fund): bool
    {
        return $identity->exists && $fund->public;
    }
}
