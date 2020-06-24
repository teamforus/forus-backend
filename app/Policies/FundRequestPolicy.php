<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Organization;
use App\Scopes\Builders\EmployeeQuery;
use App\Scopes\Builders\FundRequestRecordQuery;
use App\Scopes\Builders\OrganizationQuery;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Request;

class FundRequestPolicy
{
    use HandlesAuthorization;

    private $request;

    /**
     * FundRequestPolicy constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Determine whether the user can view the fundRequest.
     *
     * @param string|null $identity_address
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function viewAnyRequester(
        ?string $identity_address,
        Fund $fund,
        Organization $organization = null
    ) {
        if (!$this->checkIntegrity($fund, $organization, null)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        if ($fund && $fund->state != Fund::STATE_ACTIVE) {
            return $this->deny('fund_request.fund_not_active');
        }

        return !empty($identity_address);
    }

    /**
     * Determine whether the user can view the fundRequest.
     *
     * @param string|null $identity_address
     * @param Fund|null $fund
     * @param Organization|null $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function viewAnyValidator(
        ?string $identity_address,
        Fund $fund = null,
        Organization $organization = null
    ) {
        if (!$this->checkIntegrity($fund, $organization, null)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        if ($fund->state != Fund::STATE_ACTIVE) {
            return $this->deny('fund_request.fund_not_active');
        }

        if (!in_array($identity_address, $fund->validatorEmployees())) {
            return $this->deny('fund_requests.invalid_validator');
        }

        return true;
    }

    /**
     * Determine whether the user can view the fundRequest.
     *
     * @param string|null $identity_address
     * @param Organization|null $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function viewAnyValidatorForOrganization(
        ?string $identity_address,
        Organization $organization = null
    ) {
        if (!$organization->employeesOfRoleQuery([
            'validation'
        ])->where(compact('identity_address'))->exists()) {
            return $this->deny('fund_requests.invalid_validator');
        }

        return true;
    }

    /**
     * Determine whether the user can view the fundRequest.
     *
     * @param string|null $identity_address
     * @param FundRequest $request
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function viewRequester(
        ?string $identity_address,
        FundRequest $request,
        Fund $fund,
        Organization $organization = null
    ) {
        if (!$this->checkIntegrity($fund, $organization, $request)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        // only validators and fund requester may see requests
        if ((strcmp($request->identity_address, $identity_address) !== 0)) {
            return $this->deny('fund_request.not_requester');
        }

        return true;
    }

    /**
     * Determine whether the user can view the fundRequest.
     *
     * @param string|null $identity_address
     * @param FundRequest $request
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function viewValidator(
        ?string $identity_address,
        FundRequest $request,
        Fund $fund,
        Organization $organization = null
    ) {
        // TODO: Restore
        /*if (!$this->checkIntegrity($fund, $organization, $request)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }*/

        // only validators and fund requester may see requests
        if (!in_array($identity_address, $request->fund->validatorEmployees())) {
            return $this->deny('fund_request.not_validator');
        }

        return true;
    }

    /**
     * Determine whether the user can create fundRequests.
     *
     * @param string|null $identity_address
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function create(
        ?string $identity_address,
        Fund $fund,
        Organization $organization = null
    ) {
        if (!$this->checkIntegrity($fund, $organization, null)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        if ($fund->state != Fund::STATE_ACTIVE) {
            return $this->deny('fund_request.fund_not_active');
        }

        if ($fund->fund_requests()->where([
            'identity_address' => $identity_address,
            'state' => FundRequest::STATE_PENDING,
        ])->first()) {
            return $this->deny('fund_request.pending_request_exists');
        }

        if ($fund->fund_requests()->where([
            'identity_address' => $identity_address,
            'state' => FundRequest::STATE_APPROVED,
        ])->first()) {
            return $this->deny('fund_request.approved_request_exists');
        }

        return !empty($identity_address);
    }

    /**
     * Determine whether the user can update the fundRequest.
     *
     * @param string|null $identity_address
     * @param FundRequest $fundRequest
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function assignAsValidator(
        ?string $identity_address,
        FundRequest $fundRequest,
        Fund $fund,
        Organization $organization = null
    ) {
        if (!$this->checkIntegrity($fund, $organization, $fundRequest)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        // only pending requests could be updated by fund validators
        if ($fundRequest->state !== FundRequest::STATE_PENDING) {
            return $this->deny('fund_request.not_pending');
        }

        $hasRecordsAvailable = FundRequestRecordQuery::whereIdentityCanBeValidatorFilter(
            $fundRequest->records()->getQuery(),
            $identity_address,
            $this->request->input('employee_id', null)
        )->exists();

        if (!$hasRecordsAvailable) {
            return $this->deny('fund_request.no_records_available');
        }

        return true;
    }

    /**
     * Determine whether the user can update the fundRequest.
     *
     * @param string|null $identity_address
     * @param FundRequest $fundRequest
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function resignAsValidator(
        ?string $identity_address,
        FundRequest $fundRequest,
        Fund $fund,
        Organization $organization = null
    ) {
        if (!$this->checkIntegrity($fund, $organization, $fundRequest)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        // only pending requests could be updated by fund validators
        if ($fundRequest->state !== FundRequest::STATE_PENDING) {
            return $this->deny('fund_request.not_pending');
        }

        $hasRecordsAssigned = FundRequestRecordQuery::whereIdentityIsAssignedEmployeeFilter(
            $fundRequest->records()->getQuery(),
            $identity_address
        )->exists();

        if (!$hasRecordsAssigned) {
            return $this->deny('fund_request.no_records_assigned');
        }

        return true;
    }

    /**
     * Determine whether the user can update the fundRequest.
     *
     * @param string|null $identity_address
     * @param FundRequest $request
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function update(
        ?string $identity_address,
        FundRequest $request,
        Fund $fund,
        Organization $organization = null
    ) {
        if (!$this->checkIntegrity($fund, $organization, $request)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        // only pending requests could be updated by fund validators
        if ($request->state !== FundRequest::STATE_PENDING) {
            return $this->deny('fund_request.not_pending');
        }

        $hasAvailableRecords = FundRequestRecordQuery::whereIdentityIsAssignedEmployeeFilter(
            $request->records()->getQuery(),
            $identity_address
        )->whereNull('employee_id')->exists();

        $identityEmployees = EmployeeQuery::whereHasPermissionFilter(
            Employee::query(),
            'validate_records'
        )->where(compact('identity_address'))->get();

        $employee_id = $this->request->input('employee_id', false);
        $state = $this->request->input('state', false);

        if ($employee_id && !$hasAvailableRecords) {
            return $this->deny('fund_request.no_available_records');
        }

        if ($state && !$hasAvailableRecords) {
            return $this->deny('fund_request.no_available_records');
        }

        // when request is assigned to employee,
        // only assigned employee is allowed to update request
        if (!$hasAvailableRecords) {
            return $this->deny('fund_request.not_assigned_employee');
        } else {
        }

        if ($this->request->input('state', null)) {
            return $this->deny('fund_request.not_assigned_employee_cant_change_state');
        }

        // only fund validators may update requests
        if (!in_array($identity_address, $request->fund->validatorEmployees())) {
            return $this->deny('fund_request.invalid_validator');
        }

        return  true;
    }

    /**
     * Determine whether the user can delete the fundRequest.
     *
     * @param string|null $identity_address
     * @param FundRequest $request
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function delete(
        ?string $identity_address,
        FundRequest $request,
        Fund $fund,
        Organization $organization = null
    ) {
        if (!$this->checkIntegrity($fund, $organization, $request)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        // deleting requests is forbidden
        return false;
    }

    /**
     * @param Fund $fund
     * @param Organization|null $organization
     * @param FundRequest|null $request
     * @return bool
     */
    private function checkIntegrity(
        Fund $fund = null,
        Organization $organization = null,
        FundRequest $request = null
    ) {
        if ($fund == null) {
            return true;
        }

        if ($organization && ($organization->id != $fund->organization_id)) {
            return false;
        }

        if ($request && ($request->fund_id != $fund->id)) {
            return false;
        }

        return true;
    }
}
