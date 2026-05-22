<?php

namespace App\Policies;

use App\Exceptions\AuthorizationJsonException;
use App\Models\Identity;
use App\Models\Note;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\PrevalidationRequest;
use Illuminate\Auth\Access\Response;

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
    public function view(Identity $identity, PrevalidationRequest $request, Organization $organization): bool
    {
        return $organization->id === $request->organization_id && $this->viewAsSponsor($identity, $organization);
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
     * @param PrevalidationRequest $request
     * @param Organization $organization
     * @return bool
     */
    public function approveMissedRecords(Identity $identity, PrevalidationRequest $request, Organization $organization): bool
    {
        return
            $organization->id === $request->organization_id &&
            $this->viewAsSponsor($identity, $organization) &&
            $request->state == PrevalidationRequest::STATE_MISSING_RECORDS &&
            !$request->missing_records_approved;
    }

    /**
     * @param Identity $identity
     * @param PrevalidationRequest $request
     * @param Organization $organization
     * @throws AuthorizationJsonException
     * @return Response|bool
     */
    public function viewPersonBSNData(
        Identity $identity,
        PrevalidationRequest $request,
        Organization $organization,
    ): Response|bool {
        if (!$organization->hasIConnectApiOin()) {
            return $this->deny(__('policies.identities.person_bsn_api_not_available'));
        }

        return
            $organization->id === $request->organization_id &&
            $this->viewAsSponsor($identity, $organization);
    }

    /**
     * Determine whether the user can view prevalidation request notes.
     *
     * @param Identity $identity
     * @param PrevalidationRequest $request
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function viewAnyNote(
        Identity $identity,
        PrevalidationRequest $request,
        Organization $organization,
    ): Response|bool {
        return $organization->id === $request->organization_id && $this->viewAsSponsor($identity, $organization);
    }

    /**
     * Determine whether the user can store prevalidation request note.
     *
     * @param Identity $identity
     * @param PrevalidationRequest $request
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function storeNote(
        Identity $identity,
        PrevalidationRequest $request,
        Organization $organization
    ): bool {
        return $this->viewAnyNote($identity, $request, $organization);
    }

    /**
     * Determine whether the user can delete prevalidation note.
     *
     * @param Identity $identity
     * @param PrevalidationRequest $request
     * @param Organization $organization
     * @param Note $note
     * @noinspection PhpUnused
     * @throws AuthorizationJsonException
     * @return Response|bool
     */
    public function destroyNote(
        Identity $identity,
        PrevalidationRequest $request,
        Organization $organization,
        Note $note
    ): Response|bool {
        if (!$this->storeNote($identity, $request, $organization)) {
            return $this->deny(__('policies.prevalidation_requests.invalid_endpoint'));
        }

        if ($note->employee?->identity_address !== $identity->address) {
            return $this->deny(__('policies.prevalidation_requests.not_note_author'));
        }

        return true;
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
