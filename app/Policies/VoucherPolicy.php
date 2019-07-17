<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\Voucher;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\HandlesAuthorization;

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
     * @return bool
     */
    public function storeSponsor(
        string $identity_address,
        Organization $organization,
        Fund $fund
    ) {
        return $this->indexSponsor($identity_address, $organization) &&
            $fund->organization_id == $fund->id;
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
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function useAsProvider(
        string $identity_address,
        Voucher $voucher
    ) {
        if ($voucher->expire_at->isPast()) {
            throw new AuthorizationException(trans(
                'validation.voucher.expired'
            ));
        }

        if ($voucher->fund->state != Fund::STATE_ACTIVE) {
            throw new AuthorizationException(trans(
                'validation.voucher.fund_not_active'
            ));
        }

        if ($voucher->type == 'regular') {
            $organizations = $voucher->fund->provider_organizations_approved;
            $identityOrganizations = Organization::queryByIdentityPermissions(
                $identity_address, 'scan_vouchers'
            )->pluck('id');

            return $identityOrganizations->intersect(
                $organizations->pluck('id')
                )->count() > 0;
        } else if ($voucher->type == 'product') {
            // Product vouchers can have no more than 1 transaction
            if ($voucher->transactions->count() > 0) {
                throw new AuthorizationException(trans(
                    'validation.voucher.product_voucher_used'
                ));
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
}
