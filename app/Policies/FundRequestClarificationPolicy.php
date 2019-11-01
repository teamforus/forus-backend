<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestClarification;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class FundRequestClarificationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view FundRequestRecords.
     *
     * @param $identity_address
     * @param FundRequest $request
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function indexRequester(
        $identity_address,
        FundRequest $request,
        Fund $fund,
        Organization $organization = null
    ) {
        $this->checkIntegrity($fund, $request, $organization);

        // only fund requester is allowed to see records
        if ($request->identity_address != $identity_address) {
            $this->deny('fund_requests.not_requester');
        }

        return true;
    }

    /**
     * Determine whether the user can view fundRequestRecords.
     *
     * @param $identity_address
     * @param FundRequest $request
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function indexValidator(
        $identity_address,
        FundRequest $request,
        Fund $fund,
        Organization $organization = null
    ) {
        $this->checkIntegrity($fund, $request, $organization);

        // only fund validators are allowed to see records
        if (!in_array($identity_address, $fund->validatorEmployees())) {
            $this->deny('fund_requests.not_validator');
        }

        return true;
    }

    /**
     * Determine whether the user can view the fundRequestClarification.
     *
     * @param $identity_address
     * @param FundRequestClarification $requestClarification
     * @param FundRequest $request
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function viewRequester(
        $identity_address,
        FundRequestClarification $requestClarification,
        FundRequest $request,
        Fund $fund,
        Organization $organization = null
    ) {
        $this->checkIntegrity($fund, $request, $organization, $requestClarification);

        // only fund requester is allowed to see records
        if ($request->identity_address != $identity_address) {
            $this->deny('fund_requests.not_requester');
        }

        return true;
    }

    /**
     * Determine whether the user can view the fundRequestClarification.
     *
     * @param $identity_address
     * @param FundRequestClarification $requestClarification
     * @param FundRequest $request
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function viewValidator(
        $identity_address,
        FundRequestClarification $requestClarification,
        FundRequest $request,
        Fund $fund,
        Organization $organization = null
    ) {
        $this->checkIntegrity($fund, $request, $organization, $requestClarification);

        // only fund validators are allowed to see records
        if (!in_array($identity_address, $fund->validatorEmployees())) {
            $this->deny('fund_requests.not_validator');
        }

        return true;
    }

    /**
     * Determine whether the user can create fundRequestClarifications.
     *
     * @param $identity_address
     * @param FundRequest $request
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(
        $identity_address,
        FundRequest $request,
        Fund $fund,
        Organization $organization = null
    ) {
        $this->checkIntegrity($fund, $request, $organization);

        // only fund validators are allowed to request clarifications
        if (!in_array($identity_address, $fund->validatorEmployees())) {
            $this->deny('fund_requests.not_validator');
        }

        return true;
    }

    /**
     * Determine whether the user can update the fundRequestClarification.
     *
     * @param $identity_address
     * @param FundRequestClarification $requestClarification
     * @param FundRequest $request
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        $identity_address,
        FundRequestClarification $requestClarification,
        FundRequest $request,
        Fund $fund,
        Organization $organization = null
    ) {
        $this->checkIntegrity($fund, $request, $organization, $requestClarification);

        if ($requestClarification->state !== FundRequestClarification::STATE_PENDING) {
            $this->deny('fund_requests.clarification_not_pending');
        }

        // only fund requester may answer to clarification requests
        if ($request->identity_address != $identity_address) {
            $this->deny('fund_requests.not_requester');
        }

        return true;
    }

    /**
     * @param Fund $fund
     * @param FundRequest $request
     * @param Organization|null $organization
     * @param FundRequestClarification|null $requestClarification
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    private function checkIntegrity(
        Fund $fund,
        FundRequest $request,
        Organization $organization = null,
        FundRequestClarification $requestClarification = null
    ) {
        if ($organization && ($organization->id != $fund->organization_id)) {
            $this->deny('fund_requests.invalid_endpoint');
        }

        if ($request->fund_id != $fund->id) {
            $this->deny('fund_requests.invalid_endpoint');
        }

        if ($requestClarification && (
            $requestClarification->fund_request_record->fund_request_id != $request->id)) {
            $this->deny('fund_requests.invalid_endpoint');
        }
    }
}
