<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\PrevalidationRequest;
use App\Models\PrevalidationRequestRecord;
use Illuminate\Support\Facades\Gate;

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
            Gate::forUser($identity)->allows('view', [$request, $organization]) &&
            $request->state !== $request::STATE_SUCCESS;
    }

    /**
     * @return string
     */
    protected function getPolicyKey(): string
    {
        return 'prevalidation_request_records';
    }
}
