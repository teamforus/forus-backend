<?php

namespace App\Policies;

use App\Exceptions\AuthorizationJsonException;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Models\PhysicalCard;
use App\Models\Voucher;
use App\Scopes\Builders\FundProviderQuery;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

/**
 * Class VoucherPolicy
 * @package App\Policies
 */
class VoucherPolicy
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
     * @param string $identity_address
     * @return bool
     */
    public function viewAny(
        string $identity_address
    ): bool {
        return !empty($identity_address);
    }

    /**
     * @param string $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function viewAnySponsor(
        string $identity_address,
        Organization $organization
    ): bool{
        return $organization->identityCan($identity_address, [
            'manage_vouchers'
        ]);
    }

    /**
     * @param string $identity_address
     * @param Organization $organization
     * @param Fund $fund
     * @return bool
     * @throws AuthorizationJsonException
     */
    public function storeSponsor(
        string $identity_address,
        Organization $organization,
        Fund $fund
    ): bool {
        if (($fund->organization_id !== $organization->id) ||
            !$this->viewAnySponsor($identity_address, $organization)) {
            $this->deny('no_permission_to_make_vouchers');
        }

        if (!$organization->identityCan($identity_address, 'manage_vouchers')) {
            $this->deny('no_manage_vouchers_permission');
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
        return is_null($voucher->parent_id) && $organization->identityCan(
            $identity_address, [
            'manage_vouchers'
        ]) && ($voucher->fund->organization_id === $organization->id);
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
        return $organization->identityCan($identity_address, [
            'manage_vouchers'
        ]) && ($voucher->fund->organization_id === $organization->id) && !$voucher->is_granted;
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
        return $this->assignSponsor($identity_address, $voucher, $organization) &&
            !$voucher->is_granted && !$voucher->expired;
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
        return $organization->identityCan($identity_address, [
                'manage_vouchers'
            ]) && ($voucher->fund->organization_id === $organization->id) &&
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
            !$voucher->activation_code && !$voucher->expired;
    }

    /**
     * Voucher can be redeemed using an activation code.
     * @param string $identity_address
     * @param Voucher $voucher
     * @return bool
     */
    public function redeem(string $identity_address, Voucher $voucher): bool
    {
        return ($identity_address && $voucher) &&
            (bool) $voucher->activation_code && !$voucher->identity_address;
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
     * @return bool
     */
    public function store(string $identity_address): bool
    {
        return !empty($identity_address);
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
        return $this->show($identity_address, $voucher) && !$voucher->expired;
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
        return Gate::forUser($identity_address)->allows('create', [PhysicalCard::class, $voucher]);
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @return bool
     * @throws AuthorizationJsonException
     */
    public function useAsProvider(string $identity_address, Voucher $voucher): bool
    {
        $fund = $voucher->fund;

        // fund should not be expired
        if ($voucher->expired) {
            $this->deny('expired');
        }

        // fund should not be expired
        if ($voucher->state !== $voucher::STATE_ACTIVE) {
            $this->deny('pending');
        }

        // fund needs to be active
        if ($voucher->fund->state !== $voucher->fund::STATE_ACTIVE) {
            $this->deny('fund_not_active');
        }

        if ($voucher->fund->isTypeBudget()) {
            if ($voucher->isBudgetType()) {
                $providersApproved = $fund->providers()->where([
                    'allow_budget' => true,
                ])->pluck('organization_id');

                $providersDeclined = $fund->providers()->where([
                    'allow_budget' => false,
                    'dismissed' => true,
                ])->pluck('organization_id');

                $providersPending = $fund->providers()->where([
                    'allow_budget' => false,
                    'dismissed' => false,
                ])->pluck('organization_id');
            } else {
                if ($voucher->product->expired) {
                    $this->deny('product_expired');
                }

                if (!$voucher->product->unlimited_stock &&
                    ($voucher->product->countSold() >= $voucher->product->total_amount)) {
                    $this->deny('product_sold_out');
                }

                $providersApproved = FundProviderQuery::whereApprovedForFundsFilter(
                    FundProvider::query(),
                    $voucher->fund_id,
                    'product',
                    $voucher->product_id
                )->pluck('organization_id');

                $providersDeclined = $fund->providers()->where([
                    'dismissed' => true,
                ])->pluck('organization_id')->diff($providersApproved)->values();

                $providersPending = $fund->providers()->where([
                    'dismissed' => false,
                ])->pluck('organization_id')->diff($providersApproved)->values();
            }
        } elseif ($voucher->fund->isTypeSubsidy()) {
            $providersApproved = FundProviderQuery::whereApprovedForFundsFilter(
                FundProvider::query(),
                $voucher->fund_id,
                'subsidy',
                $voucher->product_id
            )->pluck('organization_id');

            $providersDeclined = $fund->providers()->where([
                'dismissed' => true,
            ])->pluck('organization_id')->diff($providersApproved)->values();

            $providersPending = $fund->providers()->where([
                'dismissed' => false,
            ])->pluck('organization_id')->diff($providersApproved)->values();
        } else {
            $this->deny('unknown_fund_type');
            return false;
        }

        $providersApplied = $fund->providers()->pluck('organization_id');

        $providers = Organization::queryByIdentityPermissions($identity_address, [
            'scan_vouchers'
        ])->pluck('id');

        // None of identity organizations applied to the fund
        if ($providers->intersect($providersApplied)->count() === 0) {
            $this->deny('provider_not_applied');
        }

        // No approved identity organizations but have pending
        if ($providers->intersect($providersApproved)->count() === 0 &&
            $providers->intersect($providersPending)->count() > 0 ) {
            $this->deny('provider_pending');
        }

        // No approved identity organizations but have declines
        if ($providers->intersect($providersApproved)->count() === 0 &&
            $providers->intersect($providersDeclined)->count() > 0 ) {
            $this->deny('provider_declined');
        }

        // No approved identity organizations but have pending
        if ($voucher->isBudgetType()) {
            return $providers->intersect($providersApproved)->count() > 0;
        }

        if ($voucher->isProductType()) {
            // Product vouchers should have not transactions
            if ($voucher->transactions()->exists()) {
                $this->deny('product_voucher_used');
            }

            // The identity should be allowed to scan voucher for
            // the provider organization
            return $voucher->product->organization->identityCan($identity_address, [
                'scan_vouchers'
            ]);
        }

        return false;
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @return bool
     */
    public function destroy(string $identity_address, Voucher $voucher): bool {
        return $this->show($identity_address, $voucher) &&
            $voucher->parent_id !== null &&
            $voucher->transactions->count() === 0 &&
            $voucher->returnable;
    }

    /**
     * @param string $message
     * @throws AuthorizationJsonException
     */
    protected function deny(string $message): void {
        $key = $message;
        $message = trans("validation.voucher.{$message}");

        throw new AuthorizationJsonException(json_encode(
            compact('error', 'message', 'key')
        ), 403);
    }
}
