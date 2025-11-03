<?php

namespace App\Policies;

use App\Http\Responses\AuthorizationJsonResponse;
use App\Models\Fund;
use App\Models\FundPhysicalCardType;
use App\Models\FundProvider;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Voucher;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\OrganizationQuery;
use App\Scopes\Builders\ProductQuery;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;

class VoucherPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAny(Identity $identity): bool
    {
        return $identity->exists;
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAnySponsor(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, [Permission::MANAGE_VOUCHERS, Permission::VIEW_VOUCHERS], false);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function export(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, Permission::MANAGE_VOUCHERS);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @param Fund $fund
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function storeSponsor(
        Identity $identity,
        Organization $organization,
        Fund $fund
    ): Response|bool {
        if (($fund->organization_id !== $organization->id) ||
            !$organization->identityCan($identity, Permission::MANAGE_VOUCHERS)) {
            return $this->deny('no_permission_to_make_vouchers');
        }

        if ($fund->external) {
            return $this->deny("External funds can't have vouchers.");
        }

        if (!$fund->isConfigured()) {
            return $this->deny('Fund not configured.');
        }

        return true;
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function showSponsor(
        Identity $identity,
        Voucher $voucher,
        Organization $organization
    ): bool {
        $isBudgetType = $voucher->isBudgetType();
        $isProductTypeMadeByEmployee = $voucher->isProductType() && $voucher->employee_id;
        $employeeCanSeeProductVouchers = $voucher->fund->fund_config->employee_can_see_product_vouchers;

        return
            ($isBudgetType || $isProductTypeMadeByEmployee || $employeeCanSeeProductVouchers) &&
            ($voucher->fund->organization_id === $organization->id) &&
            $organization->identityCan($identity, [Permission::MANAGE_VOUCHERS, Permission::VIEW_VOUCHERS], false);
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function assignSponsor(
        Identity $identity,
        Voucher $voucher,
        Organization $organization
    ): bool {
        return
            $organization->identityCan($identity, Permission::MANAGE_VOUCHERS) &&
            $voucher->fund->organization_id === $organization->id &&
            $voucher->fund->isConfigured() &&
            !$voucher->fund->external &&
            !$voucher->deactivated &&
            !$voucher->granted;
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function activateSponsor(
        Identity $identity,
        Voucher $voucher,
        Organization $organization
    ): bool {
        return
            $organization->identityCan($identity, Permission::MANAGE_VOUCHERS) &&
            $voucher->fund->organization_id === $organization->id &&
            $voucher->fund->isConfigured() &&
            !$voucher->fund->external &&
            !$voucher->activated &&
            !$voucher->expired;
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function deactivateSponsor(
        Identity $identity,
        Voucher $voucher,
        Organization $organization
    ): bool {
        return
            $organization->identityCan($identity, Permission::MANAGE_VOUCHERS) &&
            $voucher->fund->organization_id === $organization->id &&
            !$voucher->deactivated &&
            !$voucher->expired;
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function update(Identity $identity, Voucher $voucher, Organization $organization): bool
    {
        return
            $organization->identityCan($identity, Permission::MANAGE_VOUCHERS) &&
            $voucher->fund->organization_id === $organization->id;
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @return bool
     * @noinspection PhpUnused
     */
    public function deactivateRequester(Identity $identity, Voucher $voucher): bool
    {
        return
            $voucher->identity_id === $identity->id &&
            $voucher->fund->fund_config->allow_blocking_vouchers &&
            !$voucher->deactivated &&
            !$voucher->expired;
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function makeActivationCodeSponsor(
        Identity $identity,
        Voucher $voucher,
        Organization $organization
    ): bool {
        return
            $this->assignSponsor($identity, $voucher, $organization) &&
            $voucher->fund->isConfigured() &&
            !$voucher->fund->external &&
            $voucher->isInternal() &&
            !$voucher->activation_code &&
            !$voucher->deactivated &&
            !$voucher->expired;
    }

    /**
     * Voucher can be redeemed using an activation code.
     * @param Identity $identity
     * @param Voucher $voucher
     * @return bool
     * @noinspection PhpUnused
     */
    public function redeem(Identity $identity, Voucher $voucher): bool
    {
        return
            $identity->exists &&
            $voucher->exists &&
            $voucher->fund->isConfigured() &&
            !$voucher->fund->external &&
            $voucher->activation_code &&
            !$voucher->identity_id &&
            !$voucher->deactivated;
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function sendByEmailSponsor(
        Identity $identity,
        Voucher $voucher,
        Organization $organization
    ): bool {
        return $this->assignSponsor($identity, $voucher, $organization);
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @return bool
     * @noinspection PhpUnused
     */
    public function show(Identity $identity, Voucher $voucher): bool
    {
        return
            ($identity->id === $voucher->identity_id) &&
            $voucher->isVoucherType() &&
            !$voucher->product_reservation;
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @return bool
     * @noinspection PhpUnused
     */
    public function sendEmail(Identity $identity, Voucher $voucher): bool
    {
        return
            $this->show($identity, $voucher) &&
            $voucher->fund->isConfigured() &&
            !$voucher->fund->external &&
            $voucher->isInternal() &&
            !$voucher->deactivated &&
            !$voucher->expired &&
            $voucher->identity->email;
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @return bool
     * @noinspection PhpUnused
     */
    public function shareVoucher(Identity $identity, Voucher $voucher): bool
    {
        return
            $this->show($identity, $voucher) &&
            $voucher->fund->isConfigured() &&
            !$voucher->fund->external &&
            $voucher->isProductType() &&
            $voucher->isInternal() &&
            !$voucher->deactivated &&
            !$voucher->expired;
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @param FundPhysicalCardType $fundPhysicalCardType
     * @return bool
     */
    public function requestPhysicalCard(Identity $identity, Voucher $voucher, FundPhysicalCardType $fundPhysicalCardType): bool
    {
        return
            $this->show($identity, $voucher) &&
            $voucher->fund?->fund_config?->allow_physical_cards &&
            $fundPhysicalCardType?->allow_physical_card_requests &&
            $voucher->fund->isConfigured() &&
            !$voucher->fund->external &&
            $voucher->isBudgetType() &&
            $voucher->isActivated() &&
            $voucher->isInternal() &&
            !$voucher->expired;
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function requestPhysicalCardAsSponsor(
        Identity $identity,
        Voucher $voucher,
        Organization $organization
    ): bool {
        return
            $organization->identityCan($identity, Permission::MANAGE_VOUCHERS) &&
            $voucher->fund->isConfigured() &&
            !$voucher->fund->external &&
            $voucher->fund->fund_config->allow_physical_cards &&
            $voucher->fund->organization_id === $organization->id &&
            $voucher->isInternal() &&
            !$voucher->deactivated &&
            !$voucher->expired;
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function makeTransactionThrottle(
        Identity $identity,
        Voucher $voucher,
    ): Response|bool {
        $hardLimit = Config::get('forus.transactions.hard_limit');
        $hasTransaction = $voucher->hasTransactionsWithin($hardLimit);

        return $hasTransaction ? $this->deny([
            'key' => 'throttled',
            'error' => 'throttled',
            'message' => __('validation.voucher.throttled', compact('hardLimit')),
        ]) : $identity->exists();
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function useAsProvider(Identity $identity, Voucher $voucher): Response|bool
    {
        return $this->useAsProviderBase($identity, $voucher);
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @param int $product_id
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function useAsProviderWithProducts(
        Identity $identity,
        Voucher $voucher,
        int $product_id
    ): Response|bool {
        return $this->useAsProviderBase($identity, $voucher, $product_id);
    }

    /**
     * Allows to make voucher transaction as sponsor for the given voucher and provider.
     *
     * @param Identity $identity
     * @param Voucher $voucher
     * @param Organization|null $provider
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function useAsSponsor(
        Identity $identity,
        Voucher $voucher,
        ?Organization $provider = null
    ): Response|bool {
        $hasPermission = $voucher->fund->organization->identityCan($identity, Permission::MAKE_DIRECT_PAYMENTS);

        if (!$hasPermission) {
            return false;
        }

        return !$provider || $this->useAsProviderBase($provider->identity, $voucher);
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @return bool|Response
     * @noinspection PhpUnused
     */
    public function useChildVoucherAsProvider(Identity $identity, Voucher $voucher): Response|bool
    {
        $voucherAvailable = $this->checkBaseVoucherProviderAvailability($voucher);

        // Check basic voucher availability by state and relations
        if ($voucherAvailable !== true) {
            return $voucherAvailable;
        }

        if (VoucherQuery::whereProductVouchersCanBeScannedForFundBy(
            builder: $voucher->product_vouchers()->getQuery(),
            identity_address: $identity->address,
            fund_id: $voucher->fund_id,
            organization_id: null
        )->doesntExist()) {
            return $this->deny('no_available_product_voucher');
        }

        return true;
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @return bool|Response
     * @noinspection PhpUnused
     */
    public function viewRegularVoucherAvailableProductsAsProvider(
        Identity $identity,
        Voucher $voucher
    ): Response|bool {
        // Check basic voucher availability by state and relations
        if (($voucherAvailable = $this->checkBaseVoucherProviderAvailability($voucher)) !== true) {
            return $voucherAvailable;
        }

        // Identity is allowed to make transactions with at least one product
        $products = Product::whereHas('organization', function (Builder $builder) use ($identity) {
            OrganizationQuery::whereHasPermissions($builder, $identity->address, Permission::SCAN_VOUCHERS);
        });

        if (ProductQuery::whereAvailableForVoucher($products, $voucher, null, false)->doesntExist()) {
            return $this->deny('no_available_products');
        }

        return true;
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @return bool
     */
    public function destroy(Identity $identity, Voucher $voucher): bool
    {
        return
            $this->show($identity, $voucher) &&
            $voucher->parent_id !== null &&
            $voucher->transactions->count() === 0 &&
            $voucher->returnable;
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @param int|null $product_id
     * @return Response|bool
     * @noinspection PhpUnused
     */
    protected function useAsProviderBase(
        Identity $identity,
        Voucher $voucher,
        int $product_id = null
    ): Response|bool {
        // Check basic voucher availability by state and relations
        if (($voucherAvailable = $this->checkBaseVoucherProviderAvailability($voucher)) !== true) {
            return $voucherAvailable;
        }

        $applied = $voucher->fund->providers()->pluck('organization_id');
        $providers = OrganizationQuery::queryByIdentityPermissions($identity->address, Permission::SCAN_VOUCHERS);
        $providers = $providers->pluck('id');
        extract($this->getVoucherProvidersLists($voucher, $product_id));

        // None of identity organizations applied to the fund
        if ($providers->intersect($applied)->count() === 0) {
            return $this->deny('provider_not_applied');
        }

        // No approved identity organizations but have pending
        if ($providers->intersect($approved)->count() === 0 &&
            $providers->intersect($pending)->count() > 0) {
            return $this->deny('provider_pending');
        }

        // No approved identity organizations but have declines
        if ($providers->intersect($approved)->count() === 0 &&
            $providers->intersect($declined)->count() > 0) {
            return $this->deny('provider_declined');
        }

        // No approved identity organizations but have pending
        if ($voucher->isBudgetType()) {
            return $providers->intersect($approved)->count() > 0;
        }

        if ($voucher->isProductType()) {
            // Product vouchers should not have transactions
            if ($voucher->transactions()->exists()) {
                return $this->deny('product_voucher_used');
            }

            // The identity should be allowed to scan voucher for the provider organization
            return $voucher->product->organization->identityCan($identity, Permission::SCAN_VOUCHERS);
        }

        return false;
    }

    /**
     * @param mixed $message
     * @param int $code
     * @return Response
     */
    protected function deny(mixed $message, int $code = 403): Response
    {
        return AuthorizationJsonResponse::deny(is_array($message) ? $message : [
            'key' => $message,
            'error' => $message,
            'message' => __("validation.voucher.$message"),
        ], $code);
    }

    /**
     * @param Voucher $voucher
     * @return bool|\Illuminate\Auth\Access\Response
     */
    private function checkBaseVoucherProviderAvailability(Voucher $voucher): Response|bool
    {
        if (!$voucher->fund->isConfigured()) {
            return $this->deny('Fund not configured.');
        }

        if ($voucher->fund->external) {
            return $this->deny('External fund.');
        }

        // fund should not be expired
        if ($voucher->expired) {
            return $this->deny('expired');
        }

        // fund should not be expired
        if (!$voucher->isActivated()) {
            return $this->deny($voucher->isPending() ? 'pending' : [
                'message' => __('validation.voucher.deactivated', [
                    'deactivation_date' => format_date_locale($voucher->deactivationDate()),
                ]),
            ]);
        }

        // fund needs to be active
        if (!$voucher->fund->isActive()) {
            return $this->deny('fund_not_active');
        }

        // reservation used
        if ($voucher->product_reservation && $voucher->product_reservation->isAccepted()) {
            return $this->deny('reservation_used');
        }

        // reservation used
        if ($voucher->product_reservation && $voucher->product_reservation->isExpired()) {
            return $this->deny('reservation_expired');
        }

        // reservation product removed
        if ($voucher->product_reservation && $voucher->product_reservation->product->trashed()) {
            return $this->deny('reservation_product_removed');
        }

        if (!$voucher->isInternal()) {
            return $this->deny('External voucher.');
        }

        // reservation is not pending
        if ($voucher->product_reservation && !$voucher->product_reservation->isPending()) {
            return $this->deny([
                'key' => 'reservation_not_pending',
                'error' => 'reservation_not_pending',
                'message' => __(
                    'validation.product_reservation.reservation_not_pending',
                    $voucher->product_reservation->only('code', 'state')
                ),
            ]);
        }

        return true;
    }

    /**
     * @param Voucher $voucher
     * @param int|null $product_id
     * @return array|Response
     */
    private function getVoucherProvidersLists(Voucher $voucher, int $product_id = null): Response|array
    {
        if ($voucher->isBudgetType()) {
            $approved = FundProviderQuery::whereApprovedForFundsFilter(
                FundProvider::query(),
                $voucher->fund->id,
                $product_id ? 'allow_products' : 'allow_budget',
                $product_id
            )->pluck('organization_id');

            $declined = $voucher->fund->providers()->where(function (Builder $builder) {
                $builder->where('allow_budget', false);
                $builder->orWhere('state', FundProvider::STATE_REJECTED);
            })->pluck('organization_id');

            $pending = $voucher->fund->providers()->where(function (Builder $builder) {
                $builder->where('state', FundProvider::STATE_PENDING);
                $builder->orWhere(function (Builder $builder) {
                    $builder->where('state', FundProvider::STATE_ACCEPTED);
                    $builder->where('allow_budget', false);
                });
            })->pluck('organization_id');
        } else {
            if ($voucher->product->expired) {
                return $this->deny('product_expired');
            }

            if ($voucher->product->trashed()) {
                return $this->deny('product_no_longer_available');
            }

            if (!$voucher->product->unlimited_stock &&
                ($voucher->product->countSold() >= $voucher->product->total_amount)) {
                return $this->deny('product_sold_out');
            }

            $approved = FundProviderQuery::whereApprovedForFundsFilter(
                FundProvider::query(),
                $voucher->fund_id,
                'allow_products',
                $voucher->product_id
            )->pluck('organization_id');

            $declined = $voucher->fund->providers()
                ->where('state', FundProvider::STATE_REJECTED)
                ->pluck('organization_id')->diff($approved)->values();

            $pending = $voucher->fund->providers()
                ->where('state', FundProvider::STATE_PENDING)
                ->pluck('organization_id')->diff($approved)->values();
        }

        return compact('approved', 'declined', 'pending');
    }
}
