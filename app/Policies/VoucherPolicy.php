<?php

namespace App\Policies;

use App\Exceptions\AuthorizationJsonException;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\Voucher;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Builder;

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
    public function index(
        string $identity_address
    ) {
        return !empty($identity_address);
    }

    /**
     * @param string $identity_address
     * @param Organization $organization
     * @return mixed
     */
    public function indexSponsor(
        string $identity_address,
        Organization $organization
    ) {
        return $organization->identityCan($identity_address, [
            'manage_vouchers'
        ]);
    }

    /**
     * @param string $identity_address
     * @param Organization $organization
     * @param Fund $fund
     * @return mixed
     * @throws AuthorizationJsonException
     */
    public function storeSponsor(
        string $identity_address,
        Organization $organization,
        Fund $fund
    ) {
        if (!($this->indexSponsor($identity_address, $organization) &&
            $fund->organization_id == $organization->id)) {
            $this->deny('no_permission_to_make_vouchers');
        }

        if (!$organization->identityCan(
            $identity_address, [
            'manage_vouchers'
        ])) {
            $this->deny('no_manage_vouchers_permission');
        }

        if ($organization->employees()->where([
            'identity_address' => $identity_address
            ])->count() === 0) {
            $this->deny('has_to_be_employee');
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
    ) {
        return is_null($voucher->parent_id) && $organization->identityCan(
            $identity_address, [
            'manage_vouchers'
        ]) && ($voucher->fund->organization_id == $organization->id);
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
    ) {
        return $organization->identityCan($identity_address, [
            'manage_vouchers'
        ]) && (
            $voucher->fund->organization_id == $organization->id
        ) && !$voucher->is_granted;
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @param Organization $organization
     * @return bool
     */
    public function sendByEmailSponsor(
        string $identity_address,
        Voucher $voucher,
        Organization $organization
    ) {
        return $this->assignSponsor($identity_address, $voucher, $organization);
    }

    /**
     * @param string $identity_address
     * @return bool
     */
    public function store(
        string $identity_address
    ) {
        return !empty($identity_address);
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @return bool
     */
    public function show(
        string $identity_address,
        Voucher $voucher
    ) {
        return strcmp($identity_address, $voucher->identity_address) == 0;
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @return bool
     * @throws AuthorizationJsonException
     */
    public function useAsProvider(
        string $identity_address,
        Voucher $voucher
    ) {
        $fund = $voucher->fund;
        $id = 'organizations.id';

        if ($voucher->expire_at->isPast()) {
            $this->deny('voucher_expired');
        }

        if ($voucher->fund->state != Fund::STATE_ACTIVE) {
            $this->deny('fund_not_active');
        }

        $providersApproved = $fund->provider_organizations_approved()
            ->pluck($id);
        $providersDeclined = $fund->provider_organizations_declined()
            ->pluck($id);
        $providersPending = $fund->provider_organizations_pending()
            ->pluck($id);
        $providersApplied = $fund->provider_organizations()
            ->pluck($id);

        $providers = Organization::queryByIdentityPermissions(
            $identity_address, 'scan_vouchers'
        )->pluck('id');

        // None of identity organizations applied to the fund
        if ($providers->intersect($providersApplied)->count() == 0) {
            $this->deny('provider_not_applied');
        }

        // No approved identity organizations but have pending
        if ($providers->intersect($providersApproved)->count() == 0 &&
            $providers->intersect($providersPending)->count() > 0 ) {
            $this->deny('provider_pending');
        }

        // No approved identity organizations but have declined
        if ($providers->intersect($providersApproved)->count() == 0 &&
            $providers->intersect($providersDeclined)->count() > 0 ) {
            $this->deny('provider_declined');
        }

        // No approved identity organizations but hav pending
        if ($voucher->type == 'regular') {
            return $providers->intersect($providersApproved)->count() > 0;
        } else if ($voucher->type == 'product') {
            // Product vouchers can have no more than 1 transaction
            if ($voucher->transactions->count() > 0) {
                $this->deny('product_voucher_used');
            }

            // The identity should be allowed to scan voucher for
            // the provider organization
            return $voucher->product->organization->identityCan(
                $identity_address, 'scan_vouchers'
            );
        }

        return false;
    }

    /**
     * @param string $identity_address
     * @param Voucher $voucher
     * @return bool
     */
    public function destroy(
        string $identity_address,
        Voucher $voucher
    ) {
        return $this->show($identity_address, $voucher) &&
            $voucher->parent_id != null &&
            $voucher->transactions->count() == 0;
    }

    /**
     * @param string $error
     * @throws AuthorizationJsonException
     */
    protected function deny(string $error)
    {
        $message = trans("validation.voucher.{$error}");

        throw new AuthorizationJsonException(json_encode(
            compact('error', 'message')
        ), 403);
    }
}
