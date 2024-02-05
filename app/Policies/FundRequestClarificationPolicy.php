<?php

namespace App\Policies;

use App\Models\FundRequest;
use App\Models\FundRequestClarification;
use App\Models\Identity;
use App\Models\Organization;
use App\Scopes\Builders\OrganizationQuery;
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
        if ($fundRequest->identity_address !== $identity->address) {
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
        return $this->create($identity, $request, $organization);
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

        if (!$organization->identityCan($identity, 'validate_records')) {
            return $this->deny('fund_requests.invalid_validator');
        }

        return true;
    }

    /**
     * Determine whether the user can create fundRequestClarifications.
     *
     * @param Identity $identity
     * @param FundRequest $request
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function create(
        Identity $identity,
        FundRequest $request,
        Organization $organization
    ): Response|bool {
        if (!$this->checkIntegrityValidator($organization, $request)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        if (!$organization->identityCan($identity, 'validate_records')) {
            return $this->deny('fund_requests.invalid_validator');
        }

        if (!$request->identity->email) {
            return $this->deny('fund_requests.request_identity_has_no_email');
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
        $externalValidators = OrganizationQuery::whereIsExternalValidator(
            Organization::query(),
            $fundRequest->fund
        )->pluck('organizations.id')->toArray();

        if (($fundRequest->fund->organization_id !== $organization->id) &&
            !in_array($organization->id, $externalValidators, true)) {
            return false;
        }

        return !$fundRequestClarification ||
            $fundRequestClarification->fund_request_record->fund_request_id === $fundRequest->id;
    }
}
