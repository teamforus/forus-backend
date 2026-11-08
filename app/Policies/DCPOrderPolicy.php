<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Services\DCPService\Models\DCPOrder;
use Illuminate\Auth\Access\HandlesAuthorization;

class DCPOrderPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAny(Identity $identity, Organization $organization): bool
    {
        return
            $organization->allow_physical_cards &&
            $organization->identityCan($identity, [Permission::MANAGE_VOUCHERS, Permission::VIEW_VOUCHERS], false);
    }

    /**
     * @param Identity $identity
     * @param DCPOrder $order
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function show(Identity $identity, DCPOrder $order, Organization $organization): bool
    {
        return $order->organization_id === $organization->id && $this->viewAny($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param DCPOrder $order
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function retry(Identity $identity, DCPOrder $order, Organization $organization): bool
    {
        return
            $order->organization_id === $organization->id &&
            $order->isRetryable() &&
            $this->canManage($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param DCPOrder $order
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function update(Identity $identity, DCPOrder $order, Organization $organization): bool
    {
        return
            $order->organization_id === $organization->id &&
            $order->isEditable() &&
            $this->canManage($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param DCPOrder $order
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function resetPayload(Identity $identity, DCPOrder $order, Organization $organization): bool
    {
        return $order->payload_edited_at && $this->update($identity, $order, $organization);
    }

    /**
     * @param Identity $identity
     * @param DCPOrder $order
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function setOrderIdDcp(Identity $identity, DCPOrder $order, Organization $organization): bool
    {
        return
            $order->organization_id === $organization->id &&
            $order->isDuplicate() &&
            $this->canManage($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    protected function canManage(Identity $identity, Organization $organization): bool
    {
        return $organization->allow_physical_cards && $organization->identityCan($identity, Permission::MANAGE_VOUCHERS);
    }
}
