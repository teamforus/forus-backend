<?php

namespace App\Policies;

use App\Models\FundRequest;
use App\Models\FundRequestClarification;
use App\Models\FundRequestRecord;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class FundRequestClarificationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the fundRequestClarification.
     *
     * @param Identity $identity
     * @param FundRequestClarification $requestClarification
     * @param FundRequest $fundRequest
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function update(
        Identity $identity,
        FundRequestClarification $requestClarification,
        FundRequest $fundRequest
    ): Response|bool {
        if (!$this->checkIntegrityRequester($fundRequest, $requestClarification)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        // only fund requester is allowed to see records
        if ($fundRequest->identity_id !== $identity->id) {
            return $this->deny('fund_requests.not_requester');
        }

        if ($requestClarification->state !== $requestClarification::STATE_PENDING) {
            return $this->deny('fund_requests.already_resolved');
        }

        return true;
    }

    /**
     * Determine whether the user can view fundRequestRecords.
     *
     * @param Identity $identity
     * @param FundRequest $request
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function viewAnyValidator(
        Identity $identity,
        FundRequest $request,
        Organization $organization
    ): Response|bool {
        return $this->validateValidatorAccess($identity, $organization, $request);
    }

    /**
     * Determine whether the user can view the fundRequestClarification.
     *
     * @param Identity $identity
     * @param FundRequestClarification $requestClarification
     * @param FundRequest $request
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function viewValidator(
        Identity $identity,
        FundRequestClarification $requestClarification,
        FundRequest $request,
        Organization $organization
    ): Response|bool {
        if (!$this->checkIntegrityValidator($organization, $request, $requestClarification)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        if (!$organization->identityCan($identity, Permission::VALIDATE_RECORDS)) {
            return $this->deny('fund_requests.invalid_validator');
        }

        return true;
    }

    /**
     * Determine whether the user can create fundRequestClarifications.
     *
     * @param Identity $identity
     * @param FundRequest $request
     * @param FundRequestRecord $record
     * @param Organization $organization
     * @return Response|bool
     */
    public function create(
        Identity $identity,
        FundRequest $request,
        FundRequestRecord $record,
        Organization $organization
    ): Response|bool {
        $access = $this->validateValidatorAccess($identity, $organization, $request);

        if ($access !== true) {
            return $access;
        }

        if (!$request->identity->email) {
            return $this->deny(__('policies.fund_requests.request_identity_has_no_email'));
        }

        if (!$record->fund_criterion || !$request->fund->criteria()->where('id', $record->fund_criterion_id)->exists()) {
            return $this->deny(__('policies.fund_requests.record_not_part_fund_criteria'));
        }

        return true;
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @param FundRequest $request
     * @param FundRequestClarification|null $requestClarification
     * @return Response|bool
     */
    private function validateValidatorAccess(
        Identity $identity,
        Organization $organization,
        FundRequest $request,
        FundRequestClarification $requestClarification = null,
    ): Response|bool {
        if (!$this->checkIntegrityValidator($organization, $request, $requestClarification)) {
            return $this->deny(__('policies.fund_requests.invalid_endpoint'));
        }

        if (!$organization->identityCan($identity, Permission::VALIDATE_RECORDS)) {
            return $this->deny(__('policies.fund_requests.invalid_validator'));
        }

        return true;
    }

    /**
     * @param FundRequest $request
     * @param FundRequestClarification|null $fundRequestClarification
     * @return bool
     * @noinspection PhpUnused
     */
    private function checkIntegrityRequester(
        FundRequest $request,
        FundRequestClarification $fundRequestClarification = null,
    ): bool {
        if ($fundRequestClarification->fund_request_record->fund_request_id !== $request->id) {
            return false;
        }

        return true;
    }

    /**
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param FundRequestClarification|null $fundRequestClarification
     * @return bool
     */
    private function checkIntegrityValidator(
        Organization $organization,
        FundRequest $fundRequest,
        FundRequestClarification $fundRequestClarification = null
    ): bool {
        if ($fundRequest->fund->organization_id !== $organization->id) {
            return false;
        }

        return !$fundRequestClarification ||
            $fundRequestClarification->fund_request_record->fund_request_id === $fundRequest->id;
    }
}
