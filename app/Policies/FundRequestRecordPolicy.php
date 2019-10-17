<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class FundRequestRecordPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view FundRequestRecords.
     *
     * @param string|null $identity_address
     * @param FundRequest $request
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function indexRequester(
        ?string $identity_address,
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
     * @param string|null $identity_address
     * @param FundRequest $request
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function indexValidator(
        ?string $identity_address,
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
     * Determine whether the user can view the fundRequestRecord.
     *
     * @param string|null $identity_address
     * @param FundRequestRecord $requestRecord
     * @param FundRequest $request
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function viewRequester(
        ?string $identity_address,
        FundRequestRecord $requestRecord,
        FundRequest $request,
        Fund $fund,
        Organization $organization = null
    ) {
        $this->checkIntegrity($fund, $request, $organization, $requestRecord);

        // only fund requester is allowed to see records
        if ($request->identity_address != $identity_address) {
            $this->deny('fund_requests.not_requester');
        }

        return true;
    }

    /**
     * Determine whether the user can view the fundRequestRecord.
     *
     * @param string|null $identity_address
     * @param FundRequestRecord $requestRecord
     * @param FundRequest $request
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function viewValidator(
        ?string $identity_address,
        FundRequestRecord $requestRecord,
        FundRequest $request,
        Fund $fund,
        Organization $organization = null
    ) {
        $this->checkIntegrity($fund, $request, $organization, $requestRecord);

        // only fund validators are allowed to see records
        if (!in_array($identity_address, $fund->validatorEmployees())) {
            $this->deny('fund_requests.not_validator');
        }

        return true;
    }

    /**
     * Determine whether the user can update the fundRequestRecord.
     *
     * @param string|null $identity_address
     * @param FundRequestRecord $requestRecord
     * @param FundRequest $request
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        ?string $identity_address,
        FundRequestRecord $requestRecord,
        FundRequest $request,
        Fund $fund,
        Organization $organization = null
    ) {
        $this->checkIntegrity($fund, $request, $organization, $requestRecord);

        // only pending requests could be updated by fund validators
        if ($request->state !== FundRequest::STATE_PENDING) {
            $this->deny('fund_request.not_pending');
        }

        // only pending requests could be updated by fund validators
        if ($request->state !== FundRequest::STATE_PENDING) {
            $this->deny('fund_request.not_pending');
        }

        // only pending requests could be updated by fund validators
        if ($requestRecord->state !== FundRequestRecord::STATE_PENDING) {
            $this->deny('fund_request_record.not_pending');
        }

        // only fund validators are allowed to see records
        if (!in_array($identity_address, $fund->validatorEmployees())) {
            $this->deny('fund_requests.not_validator');
        }
        // when request is assigned to employee,
        // only assigned employee is allowed to update request
        if ($request->employee_id) {
            if ($request->employee->identity_address !== $identity_address) {
                $this->deny('fund_request.not_assigned_employee');
            }
        }

        return  true;
    }


    /**
     * @param Fund $fund
     * @param FundRequest $request
     * @param Organization|null $organization
     * @param FundRequestRecord|null $requestRecord
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    private function checkIntegrity(
        Fund $fund,
        FundRequest $request,
        Organization $organization = null,
        FundRequestRecord $requestRecord = null
    ) {
        if ($organization && ($organization->id != $fund->organization_id)) {
            $this->deny('fund_requests.invalid_endpoint');
        }

        if ($request->fund_id != $fund->id) {
            $this->deny('fund_requests.invalid_endpoint');
        }

        if ($requestRecord && (
            $requestRecord->fund_request_id != $request->id)) {
            $this->deny('fund_requests.invalid_endpoint');
        }
    }
}
