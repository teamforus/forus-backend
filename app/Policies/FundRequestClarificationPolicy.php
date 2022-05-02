<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestClarification;
use App\Models\Organization;
use App\Scopes\Builders\OrganizationQuery;
use Illuminate\Auth\Access\HandlesAuthorization;

class FundRequestClarificationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view FundRequestRecords.
     *
     * @param string $identity_address
     * @param FundRequest $request
     * @param Fund $fund
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function viewAnyRequester(
        string $identity_address,
        FundRequest $request,
        Fund $fund
    ) {
        if (!$this->checkIntegrityRequester($fund, $request)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        // only fund requester is allowed to see records
        if ($request->identity_address !== $identity_address) {
            return $this->deny('fund_requests.not_requester');
        }

        return true;
    }

    /**
     * Determine whether the user can view the fundRequestClarification.
     *
     * @param string $identity_address
     * @param FundRequestClarification $requestClarification
     * @param FundRequest $request
     * @param Fund $fund
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function viewRequester(
        string $identity_address,
        FundRequestClarification $requestClarification,
        FundRequest $request,
        Fund $fund
    ) {
        if (!$this->checkIntegrityRequester($fund, $request, $requestClarification)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        // only fund requester is allowed to see records
        if ($request->identity_address !== $identity_address) {
            return $this->deny('fund_requests.not_requester');
        }

        return true;
    }

    /**
     * Determine whether the user can update the fundRequestClarification.
     *
     * @param string $identity_address
     * @param FundRequestClarification $requestClarification
     * @param FundRequest $request
     * @param Fund $fund
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function update(
        string $identity_address,
        FundRequestClarification $requestClarification,
        FundRequest $request,
        Fund $fund
    ) {
        if (!$this->checkIntegrityRequester($fund, $request, $requestClarification)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        // only fund requester is allowed to see records
        if ($request->identity_address !== $identity_address) {
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
     * @param string $identity_address
     * @param FundRequest $request
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function viewAnyValidator(
        string $identity_address,
        FundRequest $request,
        Organization $organization
    ) {
        return $this->create($identity_address, $request, $organization);
    }

    /**
     * Determine whether the user can view the fundRequestClarification.
     *
     * @param string $identity_address
     * @param FundRequestClarification $requestClarification
     * @param FundRequest $request
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     * @noinspection PhpUnused
     */
    public function viewValidator(
        string $identity_address,
        FundRequestClarification $requestClarification,
        FundRequest $request,
        Organization $organization
    ) {
        if (!$this->checkIntegrityValidator($organization, $request, $requestClarification)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        if (!$organization->identityCan($identity_address, 'validate_records')) {
            return $this->deny('fund_requests.invalid_validator');
        }

        return true;
    }

    /**
     * Determine whether the user can create fundRequestClarifications.
     *
     * @param string $identity_address
     * @param FundRequest $request
     * @param Organization|null $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function create(
        string $identity_address,
        FundRequest $request,
        Organization $organization
    ) {
        if (!$this->checkIntegrityValidator($organization, $request)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        if (!$organization->identityCan($identity_address, 'validate_records')) {
            return $this->deny('fund_requests.invalid_validator');
        }

        return true;
    }

    /**
     * @param Fund $fund
     * @param FundRequest $request
     * @param FundRequestClarification|null $fundRequestClarification
     * @return bool
     */
    private function checkIntegrityRequester(
        Fund $fund,
        FundRequest $request,
        FundRequestClarification $fundRequestClarification = null
    ): bool {
        if ($request->fund_id !== $fund->id) {
            return false;
        }

        if ($fundRequestClarification && (
            $fundRequestClarification->fund_request_record->fund_request_id !== $request->id)) {
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
