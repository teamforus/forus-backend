<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use Exception;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Throwable;

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
            'view_funds',
            Permission::MANAGE_FUNDS,
            Permission::MANAGE_PAYOUTS,
            Permission::VIEW_IDENTITIES,
            Permission::MANAGE_IDENTITIES,
            'view_finances',
        ], false);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function store(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, Permission::MANAGE_FUNDS);
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
            Permission::MANAGE_FUNDS, 'view_finances', 'view_funds',
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
    public function viewIdentitiesSponsor(
        Identity $identity,
        Fund $fund,
        Organization $organization
    ): bool {
        if ($fund->organization_id !== $organization->id) {
            return false;
        }

        return $fund->organization->identityCan($identity, [
            Permission::MANAGE_IMPLEMENTATION_NOTIFICATIONS,
            Permission::VIEW_IDENTITIES,
            Permission::MANAGE_IDENTITIES,
        ], false);
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param Organization $organization
     * @param Identity $fundIdentity
     * @return bool
     * @noinspection PhpUnused
     */
    public function showIdentitySponsor(
        Identity $identity,
        Fund $fund,
        Organization $organization,
        Identity $fundIdentity
    ): bool {
        return
            $this->viewIdentitiesSponsor($identity, $fund, $organization) &&
            $fund->activeIdentityQuery()->where('id', $fundIdentity->id)->exists();
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function sendIdentityNotifications(
        Identity $identity,
        Fund $fund,
        Organization $organization
    ): bool {
        if ($fund->organization_id !== $organization->id) {
            return false;
        }

        if (!$fund->organization->allow_custom_fund_notifications) {
            return false;
        }

        return $fund->organization->identityCan($identity, Permission::MANAGE_IMPLEMENTATION_NOTIFICATIONS);
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
            return $this->deny('Bank connection invalid or expired.', 403);
        }

        if ($fund->external) {
            return $this->deny('Top-up not allowed for external funds', 403);
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

        return $fund->organization->identityCan($identity, Permission::MANAGE_FUNDS);
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     */
    public function updateTexts(Identity $identity, Fund $fund, Organization $organization): bool
    {
        if ($fund->organization_id !== $organization->id) {
            return false;
        }

        return $fund->organization->identityCan($identity, Permission::MANAGE_FUND_TEXTS);
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
            !$fund->external &&
            $fund->isConfigured() &&
            $this->update($identity, $fund, $organization);
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @return Response|bool
     */
    public function check(Identity $identity, Fund $fund): Response|bool
    {
        if ($fund->identityRequireBsnConfirmation($identity)) {
            return $this->deny('BSN session expired, please sign-in again.');
        }

        return $fund->isConfigured();
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
            return $this->deny(__('fund.state_' . $fund->state));
        }

        if ($fund->external) {
            return $this->deny(__('fund.type_external'));
        }

        if (!$fund->isConfigured()) {
            return $this->deny(__('fund.not_configured'));
        }

        if ($fund->isBackofficeApiAvailable() && $fund->fund_config->backoffice_check_partner && $identity->bsn) {
            try {
                $response = $fund->getBackofficeApi()->partnerBsn($identity->bsn);

                if (!$response->getLog()->success()) {
                    throw new Exception(implode('', [
                        'Backoffice partner check response error: ',
                        "scope: $logScope, fund_id: $fund->id, identity_address: $identity->address",
                    ]));
                }

                $partnerBsn = $response->getBsn();
                $partner = $partnerBsn ? Identity::findByBsn($partnerBsn) : false;

                if ($partner && $fund->identityHasActiveVoucher($partner)) {
                    return $this->deny(__('fund.taken_by_partner'));
                }
            } catch (Throwable $e) {
                logger()->error('FundPolicy@apply: ' . $e->getMessage());

                return $this->deny(__('fund.backoffice_error'));
            }
        }

        if ($fund->fund_config->hash_partner_deny && $fund->isTakenByPartner($identity)) {
            return $this->deny(__('fund.taken_by_partner'));
        }

        // The same identity can't apply twice to the same fund
        if ($fund->identityHasActiveVoucher($identity)) {
            return $this->deny(__('fund.already_received'));
        }

        // Check criteria
        $hasInvalidCriteria = $fund->criteria
            ->filter(fn (FundCriterion $criterion) => !$fund->checkFundCriteria($identity, $criterion))
            ->isNotEmpty();

        if ($hasInvalidCriteria) {
            return $this->deny(__('fund.unmet_criteria'));
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

        if (!$fund->isConfigured()) {
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

        return
            $organization->identityCan($identity, Permission::MANAGE_FUNDS) &&
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
