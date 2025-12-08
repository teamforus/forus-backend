<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Scopes\Builders\ProductReservationQuery;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class FundProviderPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @param Fund|null $fund
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAnySponsor(
        Identity $identity,
        Organization $organization,
        Fund $fund = null
    ): bool {
        if ($fund && ($fund->organization_id != $organization->id)) {
            return false;
        }

        if ($fund && $fund->public) {
            return true;
        }

        return $organization->identityCan($identity, [
            Permission::VIEW_FINANCES, Permission::MANAGE_PROVIDERS,
        ], false);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAnyProvider(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, [
            Permission::MANAGE_PROVIDER_FUNDS, Permission::SCAN_VOUCHERS,
        ], false);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function storeSponsor(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, Permission::MANAGE_PROVIDERS);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function storeProvider(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, Permission::MANAGE_PROVIDER_FUNDS);
    }

    /**
     * @param Identity $identity
     * @param FundProvider $fundProvider
     * @param Organization $organization
     * @param Fund $fund
     * @return bool
     * @noinspection PhpUnused
     */
    public function showSponsor(
        Identity $identity,
        FundProvider $fundProvider,
        Organization $organization,
        Fund $fund
    ): bool {
        if ($organization->id != $fundProvider->fund->organization_id) {
            return false;
        }

        if ($fund->id != $fundProvider->fund_id) {
            return false;
        }

        if ($fund->public) {
            return true;
        }

        return $organization->identityCan($identity, [
            Permission::VIEW_FINANCES, Permission::MANAGE_PROVIDERS,
        ], false);
    }

    /**
     * @param Identity $identity
     * @param FundProvider $organizationFund
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function showProvider(
        Identity $identity,
        FundProvider $organizationFund,
        Organization $organization
    ): bool {
        if ($organization->id != $organizationFund->organization_id) {
            return false;
        }

        return $organizationFund->organization->identityCan($identity, Permission::MANAGE_PROVIDER_FUNDS);
    }

    /**
     * @param Identity $identity
     * @param FundProvider $fundProvider
     * @param Organization $organization
     * @param Fund $fund
     * @return bool
     * @noinspection PhpUnused
     */
    public function updateSponsor(
        Identity $identity,
        FundProvider $fundProvider,
        Organization $organization,
        Fund $fund
    ): bool {
        if ($organization->id != $fundProvider->fund->organization_id) {
            return false;
        }

        if ($fund->id != $fundProvider->fund_id) {
            return false;
        }

        return !$fund->isArchived() && $fund->organization->identityCan($identity, [
            Permission::VIEW_FINANCES, Permission::MANAGE_PROVIDERS,
        ], false);
    }

    /**
     * @param Identity $identity
     * @param FundProvider $organizationFund
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function updateProvider(
        Identity $identity,
        FundProvider $organizationFund,
        Organization $organization
    ): bool {
        if ($organization->id != $organizationFund->organization_id) {
            return false;
        }

        return $organizationFund->organization->identityCan($identity, Permission::MANAGE_PROVIDER_FUNDS);
    }

    /**
     * @param Identity $identity
     * @param FundProvider $fundProvider
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function deleteProvider(
        Identity $identity,
        FundProvider $fundProvider,
        Organization $organization
    ): bool {
        $isPending = !$fundProvider->isApproved();
        $hasPermission = $this->updateProvider($identity, $fundProvider, $organization);
        $doesntHaveTransactions = !$fundProvider->hasTransactions();

        return $isPending && $hasPermission && $doesntHaveTransactions;
    }

    /**
     * @param Identity $identity
     * @param FundProvider $fundProvider
     * @param Organization $organization
     * @return true|Response
     */
    public function unsubscribeProvider(
        Identity $identity,
        FundProvider $fundProvider,
        Organization $organization
    ): true|Response {
        if (!$this->updateProvider($identity, $fundProvider, $organization)) {
            return $this->deny(__('policies.fund_providers.unauthorized_action'));
        }

        $vouchersQuery = VoucherQuery::whereNotInUseQuery(
            $fundProvider->fund
                ->product_vouchers()
                ->whereIn('product_id', $organization->products()->select('id'))
                ->whereNull('product_reservation_id')
                ->where('state', Voucher::STATE_PENDING)
        );

        if ($vouchersQuery->count()) {
            return $this->deny(__('policies.fund_providers.not_resolved_vouchers'));
        }

        $reservationsQuery = ProductReservationQuery::wherePendingOrExtraPaymentCanBeRefunded(
            ProductReservationQuery::whereProviderFilter(
                ProductReservation::query()->whereIn('voucher_id', $fundProvider->fund->vouchers()->select('id')),
                $organization->id
            )
        );

        if ($reservationsQuery->count()) {
            return $this->deny(__('policies.fund_providers.not_resolved_reservations'));
        }

        return true;
    }
}
