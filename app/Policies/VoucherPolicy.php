<?php

namespace App\Policies;

use App\Http\Responses\AuthorizationJsonResponse;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\FundProviderProduct;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\PhysicalCard;
use App\Models\Product;
use App\Models\Voucher;
use App\Scopes\Builders\FundProviderProductQuery;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\OrganizationQuery;
use App\Scopes\Builders\ProductQuery;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;

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
        return $organization->identityCan($identity, 'manage_vouchers');
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
            !$this->viewAnySponsor($identity, $organization)) {
            return $this->deny('no_permission_to_make_vouchers');
        }

        if ($fund->isExternal()) {
            return $this->deny("External funds can't have vouchers.");
        }

        if (!$fund->isConfigured()) {
            return $this->deny('Fund not configured.');
        }

        if (!$organization->identityCan($identity, 'manage_vouchers')) {
            return $this->deny('no_manage_vouchers_permission');
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
            $organization->identityCan($identity, 'manage_vouchers');
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
            $organization->identityCan($identity, 'manage_vouchers') &&
            $voucher->fund->organization_id === $organization->id &&
            $voucher->fund->isConfigured() &&
            $voucher->fund->isInternal() &&
            $voucher->isInternal() &&
            !$voucher->deactivated &&
            !$voucher->is_granted;
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
            $organization->identityCan($identity, 'manage_vouchers') &&
            $voucher->fund->organization_id === $organization->id &&
            $voucher->fund->isConfigured() &&
            $voucher->fund->isInternal() &&
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
            $organization->identityCan($identity, 'manage_vouchers') &&
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
            $organization->identityCan($identity, 'manage_vouchers') &&
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
            $identity->address === $voucher->identity_address &&
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
            $voucher->fund->isInternal() &&
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
            $voucher->fund->isInternal() &&
            $voucher->activation_code &&
            !$voucher->identity_address &&
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
    public function show(Identity $identity, Voucher $voucher) : bool
    {
        return $identity->address === $voucher->identity_address;
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @return bool
     * @noinspection PhpUnused
     */
    public function sendEmail(Identity $identity, Voucher $voucher): bool
    {
        return $this->shareVoucher($identity, $voucher) && $voucher->identity->email;
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
            $voucher->fund->isInternal() &&
            $voucher->isInternal() &&
            !$voucher->deactivated &&
            !$voucher->expired;
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @return bool
     * @noinspection PhpUnused
     */
    public function requestPhysicalCard(Identity $identity, Voucher $voucher): bool
    {
        return
            $this->show($identity, $voucher) &&
            Gate::allows('create', [PhysicalCard::class, $voucher]) &&
            $voucher->fund->isConfigured() &&
            $voucher->fund->isInternal() &&
            $voucher->isInternal() &&
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
    public function requestPhysicalCardAsSponsor(
        Identity $identity,
        Voucher $voucher,
        Organization $organization
    ): bool {
        return
            $organization->identityCan($identity, 'manage_vouchers') &&
            $voucher->fund->isConfigured() &&
            $voucher->fund->isInternal() &&
            $voucher->fund->fund_config->allow_physical_cards &&
            $voucher->fund->organization_id === $organization->id &&
            $voucher->isInternal() &&
            !$voucher->deactivated &&
            !$voucher->expired;
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

        if (!$voucher->fund->isInternal()) {
            return $this->deny('External fund.');
        }

        // fund should not be expired
        if ($voucher->expired) {
            return $this->deny('expired');
        }

        // fund should not be expired
        if (!$voucher->isActivated()) {
            return $this->deny($voucher->isPending() ? 'pending' : [
                'message' => trans('validation.voucher.deactivated', [
                    'deactivation_date' => format_date_locale($voucher->deactivationDate())
                ])
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
        if ($voucher->product_reservation && $voucher->product_reservation->hasExpired()) {
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
                'message' => trans(
                    "validation.product_reservation.reservation_not_pending",
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
        if ($voucher->fund->isTypeBudget()) {
            if ($voucher->isBudgetType()) {
                $approved = FundProviderQuery::whereApprovedForFundsFilter(
                    FundProvider::query(),
                    $voucher->fund->id,
                    $product_id ? 'product' : 'budget',
                    $product_id
                )->pluck('organization_id');

                $declined = $voucher->fund->providers()->where(function(Builder $builder) {
                    $builder->where('allow_budget', false);
                    $builder->orWhere('state', FundProvider::STATE_REJECTED);
                })->pluck('organization_id');

                $pending = $voucher->fund->providers()->where(function(Builder $builder) {
                    $builder->where('state', FundProvider::STATE_PENDING);
                    $builder->orWhere(function(Builder $builder) {
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
                    'product',
                    $voucher->product_id
                )->pluck('organization_id');

                $declined = $voucher->fund->providers()
                    ->where('state', FundProvider::STATE_REJECTED)
                    ->pluck('organization_id')->diff($approved)->values();

                $pending = $voucher->fund->providers()
                    ->where('state', FundProvider::STATE_PENDING)
                    ->pluck('organization_id')->diff($approved)->values();
            }
        } elseif ($voucher->fund->isTypeSubsidy()) {
            $approved = FundProviderQuery::whereApprovedForFundsFilter(
                FundProvider::query(),
                $voucher->fund_id,
                'subsidy',
                $voucher->product_id
            )->pluck('organization_id');

            $declined = $voucher->fund->providers()
                ->where('state', FundProvider::STATE_REJECTED)
                ->pluck('organization_id')->diff($approved)->values();

            $pending = $voucher->fund->providers()
                ->where('state', FundProvider::STATE_PENDING)
                ->pluck('organization_id')->diff($approved)->values();
        } else {
            return $this->deny('unknown_fund_type');
        }

        return compact('approved', 'declined', 'pending');
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
        $providers = Organization::queryByIdentityPermissions($identity->address, 'scan_vouchers');
        $providers = $providers->pluck('id');
        extract($this->getVoucherProvidersLists($voucher, $product_id));

        // None of identity organizations applied to the fund
        if ($providers->intersect($applied)->count() === 0) {
            return $this->deny('provider_not_applied');
        }

        // No approved identity organizations but have pending
        if ($providers->intersect($approved)->count() === 0 &&
            $providers->intersect($pending)->count() > 0 ) {
            return $this->deny('provider_pending');
        }

        // No approved identity organizations but have declines
        if ($providers->intersect($approved)->count() === 0 &&
            $providers->intersect($declined)->count() > 0 ) {
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
            return $voucher->product->organization->identityCan($identity, 'scan_vouchers');
        }

        return false;
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
            'message' => trans("validation.voucher.throttled", compact('hardLimit')),
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
     * Allows to make voucher transaction as sponsor for the given voucher and provider
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
        $hasPermission = $voucher->fund->organization->identityCan($identity, 'make_direct_payments');

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
        // Check basic voucher availability by state and relations
        if (($voucherAvailable = $this->checkBaseVoucherProviderAvailability($voucher)) !== true) {
            return $voucherAvailable;
        }

        if (VoucherQuery::whereProductVouchersCanBeScannedForFundBy(
            $voucher->product_vouchers()->getQuery(),
            $identity->address,
            $voucher->fund_id
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
        if ($voucher->fund->isTypeBudget()) {
            $products = Product::whereHas('organization', function(Builder $builder) use ($identity) {
                OrganizationQuery::whereHasPermissions($builder, $identity->address, 'scan_vouchers');
            });

            if (ProductQuery::whereAvailableForVoucher($products, $voucher, null, false)->doesntExist()) {
                return $this->deny('no_available_products');
            }
        }

        // Identity is allowed to make transactions with at least one product
        if ($voucher->fund->isTypeSubsidy()) {
            $organizationsQuery = Organization::queryByIdentityPermissions($identity->address, 'scan_vouchers');

            $providerProducts = FundProviderProductQuery::whereAvailableForSubsidyVoucher(
                FundProviderProduct::query(),
                $voucher,
                $organizationsQuery->select('organizations.id')
            );

            if ($providerProducts->doesntExist()) {
                return $this->deny('no_available_voucher_products');
            }
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
     * @param mixed $message
     * @param int $code
     * @return Response
     */
    protected function deny(mixed $message, int $code = 403): Response
    {
        return AuthorizationJsonResponse::deny(is_array($message) ? $message : [
            'key' => $message,
            'error' => $message,
            'message' => trans("validation.voucher.$message"),
        ], $code);
    }
}
