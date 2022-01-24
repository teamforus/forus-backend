<?php

namespace App\Policies;

use App\Http\Responses\AuthorizationJsonResponse;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\FundProviderProduct;
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
use Illuminate\Support\Facades\Gate;

/**
 * Class VoucherPolicy
 * @package App\Policies
 */
class VoucherPolicy
{
    use HandlesAuthorization;

    /**
     * @param string $identity_address
     * @return bool
     */
    public function viewAny(string $identity_address): bool
    {
        return !empty($identity_address);
    }

    /**
     * @param string $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function viewAnySponsor(string $identity_address, Organization $organization): bool
    {
        return $organization->identityCan($identity_address, 'manage_vouchers');
    }

    /**
     * @param string $identity_address
     * @param Organization $organization
     * @param Fund $fund
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function storeSponsor(string $identity_address, Organization $organization, Fund $fund)
    {
        if (($fund->organization_id !== $organization->id) ||
            !$this->viewAnySponsor($identity_address, $organization)) {
            return $this->deny('no_permission_to_make_vouchers');
        }

        if ($fund->isExternal()) {
            return $this->deny("External funds can't have vouchers.");
        }

        if (!$fund->isConfigured()) {
            return $this->deny('Fund not configured.');
        }

        if (!$organization->identityCan($identity_address, 'manage_vouchers')) {
            return $this->deny('no_manage_vouchers_permission');
        }

        return true;
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @param Organization $organization
     * @return bool
     */
    public function showSponsor(
        string $identity_address,
        Voucher $voucher,
        Organization $organization
    ): bool {
        return ($voucher->isBudgetType() || ($voucher->isProductType() && $voucher->employee_id)) &&
            ($voucher->fund->organization_id === $organization->id) &&
            $organization->identityCan($identity_address, 'manage_vouchers');
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @param Organization $organization
     * @return bool
     */
    public function assignSponsor(
        string $identity_address,
        Voucher $voucher,
        Organization $organization
    ): bool {
        return $organization->identityCan($identity_address, 'manage_vouchers') &&
            ($voucher->fund->organization_id === $organization->id) &&
            ($voucher->fund->isConfigured()) &&
            ($voucher->fund->isInternal()) &&
            !$voucher->deactivated &&
            !$voucher->is_granted;
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @param Organization $organization
     * @return bool
     */
    public function activateSponsor(
        string $identity_address,
        Voucher $voucher,
        Organization $organization
    ): bool {
        return $organization->identityCan($identity_address, 'manage_vouchers') &&
            ($voucher->fund->organization_id === $organization->id) &&
            ($voucher->fund->isConfigured()) &&
            ($voucher->fund->isInternal()) &&
            !$voucher->activated &&
            !$voucher->expired;
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @param Organization $organization
     * @return bool
     */
    public function deactivateSponsor(
        string $identity_address,
        Voucher $voucher,
        Organization $organization
    ): bool {
        return $organization->identityCan($identity_address, 'manage_vouchers') &&
            ($voucher->fund->organization_id === $organization->id) &&
            !$voucher->deactivated &&
            !$voucher->expired;
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @return bool
     */
    public function deactivateRequester(
        string $identity_address,
        Voucher $voucher
    ): bool {
        return (strcmp($identity_address, $voucher->identity_address) === 0) &&
            $voucher->fund->fund_config->allow_blocking_vouchers &&
            !$voucher->deactivated &&
            !$voucher->expired;
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @param Organization $organization
     * @return bool
     */
    public function makeActivationCodeSponsor(
        string $identity_address,
        Voucher $voucher,
        Organization $organization
    ): bool {
        return $this->assignSponsor($identity_address, $voucher, $organization) &&
            $voucher->fund->isConfigured() &&
            $voucher->fund->isInternal() &&
            !$voucher->activation_code &&
            !$voucher->deactivated &&
            !$voucher->expired;
    }

    /**
     * Voucher can be redeemed using an activation code.
     * @param string $identity_address
     * @param Voucher $voucher
     * @return bool
     */
    public function redeem(string $identity_address, Voucher $voucher): bool
    {
        return ($identity_address && $voucher->exists) &&
            $voucher->fund->isConfigured() &&
            $voucher->fund->isInternal() &&
            $voucher->activation_code &&
            !$voucher->identity_address &&
            !$voucher->deactivated;
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function sendByEmailSponsor(
        string $identity_address,
        Voucher $voucher,
        Organization $organization
    ): bool {
        return $this->assignSponsor($identity_address, $voucher, $organization);
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @return bool
     */
    public function show(string $identity_address, Voucher $voucher) : bool
    {
        return strcmp($identity_address, $voucher->identity_address) === 0;
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @return bool
     */
    public function sendEmail(string $identity_address, Voucher $voucher): bool
    {
        return $this->show($identity_address, $voucher) &&
            $voucher->fund->isConfigured() &&
            $voucher->fund->isInternal() &&
            !$voucher->deactivated &&
            !$voucher->expired;
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @return bool
     */
    public function shareVoucher(string $identity_address, Voucher $voucher): bool
    {
        return $this->sendEmail($identity_address, $voucher);
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @return bool
     */
    public function requestPhysicalCard(string $identity_address, Voucher $voucher): bool
    {
        return $this->show($identity_address, $voucher) &&
            Gate::allows('create', [PhysicalCard::class, $voucher]) &&
            $voucher->fund->isConfigured() &&
            $voucher->fund->isInternal() &&
            !$voucher->deactivated &&
            !$voucher->expired;
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @param Organization $organization
     * @return bool
     */
    public function requestPhysicalCardAsSponsor(
        string $identity_address,
        Voucher $voucher,
        Organization $organization
    ): bool {
        return $organization->identityCan($identity_address, 'manage_vouchers') &&
            $voucher->fund->isConfigured() &&
            $voucher->fund->isInternal() &&
            $voucher->fund->fund_config->allow_physical_cards &&
            $voucher->fund->organization_id == $organization->id &&
            !$voucher->deactivated &&
            !$voucher->expired;
    }

    /**
     * @param Voucher $voucher
     * @return bool|\Illuminate\Auth\Access\Response
     */
    private function checkBaseVoucherProviderAvailability(Voucher $voucher)
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
     * @param int|null $product_id = null
     * @return array|Response
     */
    private function getVoucherProvidersLists(Voucher $voucher, int $product_id = null): array
    {
        if ($voucher->fund->isTypeBudget()) {
            if ($voucher->isBudgetType()) {
                $approved = FundProviderQuery::whereApprovedForFundsFilter(
                    FundProvider::query(),
                    $voucher->fund->id,
                    $product_id ? 'product' : 'budget',
                    $product_id
                )->pluck('organization_id');

                $declined = $voucher->fund->providers()->where([
                    'allow_budget' => false,
                    'dismissed' => true,
                ])->pluck('organization_id');

                $pending = $voucher->fund->providers()->where([
                    'allow_budget' => false,
                    'dismissed' => false,
                ])->pluck('organization_id');
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

                $declined = $voucher->fund->providers()->where([
                    'dismissed' => true,
                ])->pluck('organization_id')->diff($approved)->values();

                $pending = $voucher->fund->providers()->where([
                    'dismissed' => false,
                ])->pluck('organization_id')->diff($approved)->values();
            }
        } elseif ($voucher->fund->isTypeSubsidy()) {
            $approved = FundProviderQuery::whereApprovedForFundsFilter(
                FundProvider::query(),
                $voucher->fund_id,
                'subsidy',
                $voucher->product_id
            )->pluck('organization_id');

            $declined = $voucher->fund->providers()->where([
                'dismissed' => true,
            ])->pluck('organization_id')->diff($approved)->values();

            $pending = $voucher->fund->providers()->where([
                'dismissed' => false,
            ])->pluck('organization_id')->diff($approved)->values();
        } else {
            return $this->deny('unknown_fund_type');
        }

        return compact('approved', 'declined', 'pending');
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @param int|null $product_id
     * @return bool|Response
     */
    protected function useAsProviderBase(
        string $identity_address,
        Voucher $voucher,
        int $product_id = null
    ) {
        // Check basic voucher availability by state and relations
        if (($voucherAvailable = $this->checkBaseVoucherProviderAvailability($voucher)) !== true) {
            return $voucherAvailable;
        }

        $applied = $voucher->fund->providers()->pluck('organization_id');
        $providers = Organization::queryByIdentityPermissions($identity_address, 'scan_vouchers');
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
            // Product vouchers should have not transactions
            if ($voucher->transactions()->exists()) {
                return $this->deny('product_voucher_used');
            }

            // The identity should be allowed to scan voucher for the provider organization
            return $voucher->product->organization->identityCan($identity_address, 'scan_vouchers');
        }

        return false;
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function useAsProvider(string $identity_address, Voucher $voucher)
    {
        return $this->useAsProviderBase($identity_address, $voucher);
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @param int $product_id
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function useAsProviderWithProducts(string $identity_address, Voucher $voucher, int $product_id)
    {
        return $this->useAsProviderBase($identity_address, $voucher, $product_id);
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function useChildVoucherAsProvider(string $identity_address, Voucher $voucher)
    {
        // Check basic voucher availability by state and relations
        if (($voucherAvailable = $this->checkBaseVoucherProviderAvailability($voucher)) !== true) {
            return $voucherAvailable;
        }

        if (VoucherQuery::whereProductVouchersCanBeScannedForFundBy(
            $voucher->product_vouchers()->getQuery(),
            $identity_address,
            $voucher->fund_id
        )->doesntExist()) {
            return $this->deny('no_available_product_voucher');
        }

        return true;
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @return bool|\Illuminate\Auth\Access\Response
     * @noinspection PhpUnused
     */
    public function viewRegularVoucherAvailableProductsAsProvider(string $identity_address, Voucher $voucher)
    {
        // Check basic voucher availability by state and relations
        if (($voucherAvailable = $this->checkBaseVoucherProviderAvailability($voucher)) !== true) {
            return $voucherAvailable;
        }

        // Identity is allowed to make transactions with at least one product
        if ($voucher->fund->isTypeBudget()) {
            $products = Product::whereHas('organization', function(Builder $builder) use ($identity_address) {
                OrganizationQuery::whereHasPermissions($builder, $identity_address, 'scan_vouchers');
            });

            if (ProductQuery::whereAvailableForVoucher($products, $voucher, null, false)->doesntExist()) {
                return $this->deny('no_available_products');
            }
        }

        // Identity is allowed to make transactions with at least one product
        if ($voucher->fund->isTypeSubsidy()) {
            $organizationsQuery = Organization::queryByIdentityPermissions($identity_address, 'scan_vouchers');

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
     * @param string $identity_address
     * @param Voucher $voucher
     * @return bool
     */
    public function destroy(string $identity_address, Voucher $voucher): bool
    {
        return $this->show($identity_address, $voucher) &&
            $voucher->parent_id !== null &&
            $voucher->transactions->count() === 0 &&
            $voucher->returnable;
    }

    /**
     * @param $message
     * @param $code
     * @return Response
     */
    protected function deny($message, $code = 403): Response
    {
        return AuthorizationJsonResponse::deny(is_array($message) ? $message : [
            'key' => $message,
            'error' => $message,
            'message' => trans("validation.voucher.$message"),
        ], $code);
    }
}
