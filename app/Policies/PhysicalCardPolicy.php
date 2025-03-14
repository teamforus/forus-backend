<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\PhysicalCard;
use App\Models\Voucher;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PhysicalCardPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create physical cards.
     *
     * @param Identity $identity
     * @param Voucher $voucher
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function create(Identity $identity, Voucher $voucher): Response|bool
    {
        if (($result = $this->baseCreatePolicy($voucher)) !== true) {
            return $result;
        }

        return $identity->id === $voucher->identity_id;
    }

    /**
     * Determine whether the user can create physical cards.
     *
     * @param Identity $identity
     * @param Voucher $voucher
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function createSponsor(
        Identity $identity,
        Voucher $voucher,
        Organization $organization
    ): Response|bool {
        if (($result = $this->baseCreatePolicy($voucher)) !== true) {
            return $result;
        }

        return
            $voucher->fund->organization_id == $organization->id &&
            $organization->identityCan($identity, 'manage_vouchers');
    }

    /**
     * Determine whether the user can delete the physical card.
     *
     * @param Identity $identity
     * @param \App\Models\PhysicalCard $physicalCard
     * @param Voucher $voucher
     * @return bool
     * @noinspection PhpUnused
     */
    public function delete(Identity $identity, PhysicalCard $physicalCard, Voucher $voucher): bool
    {
        return
            $physicalCard->voucher_id === $voucher->id &&
            $voucher->identity_id === $identity->id;
    }

    /**
     * Determine whether the user can delete the physical card.
     *
     * @param Identity $identity
     * @param \App\Models\PhysicalCard $physicalCard
     * @param Voucher $voucher
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function deleteSponsor(
        Identity $identity,
        PhysicalCard $physicalCard,
        Voucher $voucher,
        Organization $organization
    ): bool {
        return
            $physicalCard->voucher_id === $voucher->id &&
            $voucher->fund->organization_id === $organization->id &&
            $organization->identityCan($identity, 'manage_vouchers');
    }

    /**
     * @param Voucher $voucher
     * @return Response|bool
     */
    protected function baseCreatePolicy(Voucher $voucher): Response|bool
    {
        if (!$voucher->fund->fund_config->allow_physical_cards) {
            return $this->deny(trans('policies.physical_cards.not_allowed'));
        }

        if ($voucher->physical_cards()->exists()) {
            return $this->deny(trans('policies.physical_cards.already_attached'));
        }

        if (!$voucher->isBudgetType()) {
            return $this->deny(trans('policies.physical_cards.only_budget_vouchers'));
        }

        if (!$voucher->isPending() && !$voucher->activated) {
            return $this->deny($voucher->state);
        }

        return true;
    }
}
