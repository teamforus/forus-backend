<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Organization;
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
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function indexRequester(
        ?string $identity_address,
        Fund $fund,
        Organization $organization = null
    ) {
        $this->checkIntegrity($fund, $organization, null);

        if ($fund && $fund->state != Fund::STATE_ACTIVE) {
            $this->deny('fund_request.fund_not_active');
        }

        return !empty($identity_address);
    }

    /**
     * Determine whether the user can view the fundRequest.
     *
     * @param string|null $identity_address
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function indexValidator(
        ?string $identity_address,
        Fund $fund = null,
        Organization $organization = null
    ) {
        $this->checkIntegrity($fund, $organization, null);

        if ($fund->state != Fund::STATE_ACTIVE) {
            $this->deny('fund_request.fund_not_active');
        }

        if (!in_array($identity_address, $fund->validatorEmployees())) {
            $this->deny('fund_requests.invalid_validator');
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
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function viewRequester(
        ?string $identity_address,
        FundRequest $request,
        Fund $fund,
        Organization $organization = null
    ) {
        $this->checkIntegrity($fund, $organization, $request);

        // only validators and fund requester may see requests
        if ((strcmp($request->identity_address, $identity_address) !== 0)) {
            $this->deny('fund_request.not_requester');
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
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function viewValidator(
        ?string $identity_address,
        FundRequest $request,
        Fund $fund,
        Organization $organization = null
    ) {
        $this->checkIntegrity($fund, $organization, $request);

        // only validators and fund requester may see requests
        if (!in_array($identity_address, $request->fund->validatorEmployees())) {
            $this->deny('fund_request.not_validator');
        }

        return true;
    }

    /**
     * Determine whether the user can create fundRequests.
     *
     * @param string|null $identity_address
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool|void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(
        ?string $identity_address,
        Fund $fund,
        Organization $organization = null
    ) {
        $this->checkIntegrity($fund, $organization, null);

        if ($fund->state != Fund::STATE_ACTIVE) {
            $this->deny('fund_request.fund_not_active');
        }

        if ($fund->fund_requests()->where([
            'identity_address' => $identity_address,
            'state' => FundRequest::STATE_PENDING,
        ])->first()) {
            $this->deny('fund_request.pending_request_exists');
        }

        if ($fund->fund_requests()->where([
            'identity_address' => $identity_address,
            'state' => FundRequest::STATE_APPROVED,
        ])->first()) {
            $this->deny('fund_request.approved_request_exists');
        }

        return !empty($identity_address);
    }

    /**
     * Determine whether the user can update the fundRequest.
     *
     * @param string|null $identity_address
     * @param FundRequest $request
     * @param Fund $fund
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        ?string $identity_address,
        FundRequest $request,
        Fund $fund,
        Organization $organization = null
    ) {
        $this->checkIntegrity($fund, $organization, $request);

        // only pending requests could be updated by fund validators
        if ($request->state !== FundRequest::STATE_PENDING) {
            $this->deny('fund_request.not_pending');
        }

        // when request is assigned to employee,
        // only assigned employee is allowed to update request
        if ($request->employee_id) {
            if ($request->employee->identity_address !== $identity_address) {
                $this->deny('fund_request.not_assigned_employee');
            }
        } else {
            if ($this->request->input('state', null)) {
                $this->deny('fund_request.not_assigned_employee_cant_change_state');
            }
        }

        // only fund validators may update requests
        if (!in_array($identity_address, $request->fund->validatorEmployees())) {
            $this->deny('fund_request.invalid_validator');
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
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function delete(
        ?string $identity_address,
        FundRequest $request,
        Fund $fund,
        Organization $organization = null
    ) {
        $this->checkIntegrity($fund, $organization, $request);

        // deleting requests is forbidden
        return !empty($identity_address) && !empty($request) && false;
    }

    /**
     * @param Fund $fund
     * @param Organization|null $organization
     * @param FundRequest|null $request
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    private function checkIntegrity(
        Fund $fund,
        Organization $organization = null,
        FundRequest $request = null
    ) {
        if (!$fund) {
            $fund = $organization->funds[0];
        }

        if ($organization && ($organization->id != $fund->organization_id)) {
            $this->deny('fund_requests.invalid_endpoint');
        }

        if ($request && ($request->fund_id != $fund->id)) {
            $this->deny('fund_requests.invalid_endpoint');
        }
    }
}
