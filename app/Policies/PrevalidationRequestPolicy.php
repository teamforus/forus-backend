<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\PrevalidationRequest;

class PrevalidationRequestPolicy extends BasePolicy
{
    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function viewAny(Identity $identity, Organization $organization): bool
    {
        return $this->viewAsSponsor($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function create(Identity $identity, Organization $organization): bool
    {
        return $this->viewAsSponsor($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param PrevalidationRequest $request
     * @param Organization $organization
     * @return bool
     */
    public function resubmit(Identity $identity, PrevalidationRequest $request, Organization $organization): bool
    {
        return
            $organization->id === $request->organization_id &&
            $this->viewAsSponsor($identity, $organization) &&
            $request->state == PrevalidationRequest::STATE_FAIL;
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function resubmitFailed(Identity $identity, Organization $organization): bool
    {
        return $this->viewAsSponsor($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param PrevalidationRequest $request
     * @param Organization $organization
     * @return bool
     */
    public function destroy(Identity $identity, PrevalidationRequest $request, Organization $organization): bool
    {
        return
            $organization->id === $request->organization_id &&
            $this->viewAsSponsor($identity, $organization) &&
            $request->state == PrevalidationRequest::STATE_FAIL;
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    protected function viewAsSponsor(Identity $identity, Organization $organization): bool
    {
        return
            $organization->allow_prevalidation_requests &&
            $organization->identityCan($identity, [
                Permission::VALIDATE_RECORDS,
                Permission::MANAGE_ORGANIZATION,
            ], false);
    }

    /**
     * @return string
     */
    protected function getPolicyKey(): string
    {
        return 'prevalidation_requests';
    }
}
