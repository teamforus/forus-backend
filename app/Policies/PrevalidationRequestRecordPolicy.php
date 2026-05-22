<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\PrevalidationRequest;
use App\Models\PrevalidationRequestRecord;

class PrevalidationRequestRecordPolicy extends BasePolicy
{
    /**
     * @param Identity $identity
     * @param PrevalidationRequestRecord $record
     * @param PrevalidationRequest $request
     * @param Organization $organization
     * @return bool
     */
    public function updateAsSponsor(
        Identity $identity,
        PrevalidationRequestRecord $record,
        PrevalidationRequest $request,
        Organization $organization,
    ): bool {
        return
            $record->prevalidation_request_id === $request->id &&
            $this->view($identity, $request, $organization) &&
            $request->state !== $request::STATE_SUCCESS;
    }

    /**
     * @param Identity $identity
     * @param PrevalidationRequest $request
     * @param Organization $organization
     * @return bool
     */
    public function view(Identity $identity, PrevalidationRequest $request, Organization $organization): bool
    {
        return $organization->id === $request->organization_id && $this->viewAsSponsor($identity, $organization);
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
        return 'prevalidation_request_records';
    }
}
