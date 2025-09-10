<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\PhysicalCard;
use App\Models\PhysicalCardType;
use App\Models\Voucher;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PhysicalCardPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function viewAny(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, [Permission::VIEW_VOUCHERS, Permission::MANAGE_VOUCHERS], false);
    }

    /**
     * Determine whether the user can create physical cards.
     *
     * @param Identity $identity
     * @param Voucher $voucher
     * @param PhysicalCardType $physicalCardType
     * @return Response|bool
     */
    public function create(Identity $identity, PhysicalCardType $physicalCardType, Voucher $voucher): Response|bool
    {
        if (($result = $this->baseCreatePolicy($physicalCardType, $voucher)) !== true) {
            return $result;
        }

        return $identity->id === $voucher->identity_id;
    }

    /**
     * Determine whether the user can create physical cards.
     *
     * @param Identity $identity
     * @param PhysicalCardType $physicalCardType
     * @param Voucher $voucher
     * @param Organization $organization
     * @return Response|bool
     */
    public function createSponsor(
        Identity $identity,
        PhysicalCardType $physicalCardType,
        Voucher $voucher,
        Organization $organization
    ): Response|bool {
        if (($result = $this->baseCreatePolicy($physicalCardType, $voucher)) !== true) {
            return $result;
        }

        return
            $voucher->fund->organization_id == $organization->id &&
            $organization->identityCan($identity, Permission::MANAGE_VOUCHERS);
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
        $fund_physical_card_type = $voucher->fund->fund_physical_card_types
            ->where('fund_id', $voucher->fund_id)
            ->where('physical_card_type_id', $physicalCard->physical_card_type_id)
            ->first();

        return
            $fund_physical_card_type?->allow_physical_card_deactivation &&
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
            $organization->identityCan($identity, Permission::MANAGE_VOUCHERS);
    }

    /**
     * @param PhysicalCardType $physicalCardType
     * @param Voucher $voucher
     * @return Response|bool
     */
    protected function baseCreatePolicy(PhysicalCardType $physicalCardType, Voucher $voucher): Response|bool
    {
        if (!$voucher->fund->fund_config->allow_physical_cards) {
            return $this->deny(__('policies.physical_cards.not_allowed'));
        }

        if ($voucher->physical_cards()->exists()) {
            return $this->deny(__('policies.physical_cards.already_attached'));
        }

        if (!$voucher->isBudgetType()) {
            return $this->deny(__('policies.physical_cards.only_budget_vouchers'));
        }

        if (!$voucher->findFundPhysicalCardTypeForType($physicalCardType)?->allow_physical_card_linking) {
            return $this->deny(__('policies.physical_cards.linking_not_allowed'));
        }

        if (!$voucher->isPending() && !$voucher->activated) {
            return $this->deny($voucher->state);
        }

        return true;
    }
}
