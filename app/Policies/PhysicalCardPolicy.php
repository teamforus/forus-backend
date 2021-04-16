<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\PhysicalCard;
use App\Models\Voucher;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class PhysicalCardPolicy
 * @package App\Policies
 */
class PhysicalCardPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create physical cards.
     *
     * @param string $identity_address
     * @param Voucher $voucher
     * @return mixed
     */
    public function create(
        string $identity_address,
        Voucher $voucher
    ): bool {
        if (!$voucher->fund->fund_config->allow_physical_cards) {
            $this->deny("physical_cards_not_allowed");
        }

        if ($voucher->physical_cards()->exists()) {
            $this->deny("physical_card_already_attached");
        }

        if (!$voucher->isBudgetType()) {
            $this->deny("only_budget_vouchers");
        }

        return strcmp($identity_address, $voucher->identity_address) === 0;
    }

    /**
     * Determine whether the user can create physical cards.
     *
     * @param string $identity_address
     * @param Voucher $voucher
     * @param Organization $organization
     * @return mixed
     * @noinspection PhpUnused
     */
    public function createSponsor(
        string $identity_address,
        Voucher $voucher,
        Organization $organization
    ): bool {
        if (!$voucher->fund->fund_config->allow_physical_cards) {
            $this->deny("physical_cards_not_allowed");
        }

        if ($voucher->physical_cards()->exists()) {
            $this->deny("physical_card_already_attached");
        }

        if (!$voucher->isBudgetType()) {
            $this->deny("only_budget_vouchers");
        }

        return $voucher->fund->organization_id == $organization->id &&
            $organization->identityCan($identity_address, ['manage_vouchers']);
    }

    /**
     * Determine whether the user can delete the physical card.
     *
     * @param string $identity_address
     * @param \App\Models\PhysicalCard $physicalCard
     * @param Voucher $voucher
     * @return mixed
     */
    public function delete(
        string $identity_address,
        PhysicalCard $physicalCard,
        Voucher $voucher
    ): bool {
        return $physicalCard->voucher_id === $voucher->id &&
            $voucher->identity_address === $identity_address;
    }

    /**
     * Determine whether the user can delete the physical card.
     *
     * @param string $identity_address
     * @param \App\Models\PhysicalCard $physicalCard
     * @param Voucher $voucher
     * @param Organization $organization
     * @return mixed
     * @noinspection PhpUnused
     */
    public function deleteSponsor(
        string $identity_address,
        PhysicalCard $physicalCard,
        Voucher $voucher,
        Organization $organization
    ): bool {
        return $physicalCard->voucher_id === $voucher->id &&
            $voucher->fund->organization_id === $organization->id &&
            $organization->identityCan($identity_address, ['manage_vouchers']);
    }
}
