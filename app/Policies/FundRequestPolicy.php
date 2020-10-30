<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Models\Organization;
use App\Scopes\Builders\FundRequestRecordQuery;
use App\Scopes\Builders\OrganizationQuery;
use Illuminate\Auth\Access\HandlesAuthorization;

class FundRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the fundRequest.
     *
     * @param string|null $identity_address
     * @param Fund $fund
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function viewAnyAsRequester(
        ?string $identity_address,
        Fund $fund
    ) {
        if ($fund->state !== Fund::STATE_ACTIVE) {
            return $this->deny('fund_request.fund_not_active');
        }

        return !empty($identity_address);
    }

    /**
     * Determine whether the user can view the fundRequest.
     *
     * @param string|null $identity_address
     * @param FundRequest $fundRequest
     * @param Fund $fund
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function viewAsRequester(
        ?string $identity_address,
        FundRequest $fundRequest,
        Fund $fund
    ) {
        if (!$this->checkIntegrityRequester($fund, $fundRequest)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        if ((strcmp($fundRequest->identity_address, $identity_address) !== 0)) {
            return $this->deny('fund_request.not_requester');
        }

        return true;
    }

    /**
     * Determine whether the user can create fundRequests.
     *
     * @param string|null $identity_address
     * @param Fund $fund
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function createAsRequester(
        ?string $identity_address,
        Fund $fund
    ) {
        if ($fund->state !== Fund::STATE_ACTIVE) {
            return $this->deny('fund_request.fund_not_active');
        }

        if ($fund->fund_config->implementation->digid_required &&
            !record_repo()->bsnByAddress($identity_address)) {
            return $this->deny('fund_request.bsn_record_is_mandatory');
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
     * Determine whether the user can view the fundRequest.
     *
     * @param string|null $identity_address
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function viewAnyAsValidator(
        ?string $identity_address,
        Organization $organization
    ) {
        if ($organization->employeesWithPermissionsQuery('validate_records')->where(
            compact('identity_address')
        )->doesntExist()) {
            return $this->deny('fund_requests.invalid_validator');
        }

        return true;
    }

    /**
     * Determine whether the user can view the fundRequest.
     *
     * @param string|null $identity_address
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function exportAnyAsValidator(
        ?string $identity_address,
        Organization $organization
    ) {
        return $this->viewAnyAsValidator($identity_address, $organization);
    }

    /**
     * Determine whether the user can view the fundRequest.
     *
     * @param string|null $identity_address
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function viewAsValidator(
        ?string $identity_address,
        FundRequest $fundRequest,
        Organization $organization
    ) {
        if (!$this->checkIntegrityValidator($organization, $fundRequest)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        if (!$organization->identityCan($identity_address, 'validate_records')) {
            return $this->deny('fund_requests.invalid_validator');
        }

        if (!in_array($identity_address, $fundRequest->fund->validatorEmployees(), true)) {
            return $this->deny('fund_request.not_validator');
        }

        return true;
    }

    /**
     * Determine whether the user can update the fundRequest.
     *
     * @param string|null $identity_address
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function assignAsValidator(
        ?string $identity_address,
        FundRequest $fundRequest,
        Organization $organization
    ) {
        if (!$this->checkIntegrityValidator($organization, $fundRequest)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        if (!$organization->identityCan($identity_address, 'validate_records')) {
            return $this->deny('fund_requests.invalid_validator');
        }

        // only pending requests could be updated by fund validators
        if ($fundRequest->state !== FundRequest::STATE_PENDING) {
            return $this->deny('fund_request.not_pending');
        }

        $hasRecordsAvailable = FundRequestRecordQuery::whereIdentityCanBeValidatorFilter(
            $fundRequest->records()->where([
                'state' => FundRequestRecord::STATE_PENDING
            ])->whereDoesntHave('employee')->getQuery(),
            $identity_address,
            $organization->findEmployee($identity_address)->id
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
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function resignAsValidator(
        ?string $identity_address,
        FundRequest $fundRequest,
        Organization $organization
    ) {
        if (!$this->checkIntegrityValidator($organization, $fundRequest)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        if (!$organization->identityCan($identity_address, 'validate_records')) {
            return $this->deny('fund_requests.invalid_validator');
        }

        // only pending requests could be updated by fund validators
        if ($fundRequest->state !== FundRequest::STATE_PENDING) {
            return $this->deny('fund_request.not_pending');
        }

        $hasRecordsAssigned = FundRequestRecordQuery::whereIdentityIsAssignedEmployeeFilter(
            $fundRequest->records()->getQuery(),
            $identity_address,
            $organization->findEmployee($identity_address)->id
        )->exists();

        if (!$hasRecordsAssigned) {
            return $this->deny('fund_request.no_records_assigned');
        }

        return true;
    }

    /**
     * Determine whether the validator can resolve the fundRequest.
     *
     * @param string|null $identity_address
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function resolveAsValidator(
        ?string $identity_address,
        FundRequest $fundRequest,
        Organization $organization
    ) {
        if (!$this->checkIntegrityValidator($organization, $fundRequest)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        if (!$organization->identityCan($identity_address, 'validate_records')) {
            return $this->deny('fund_requests.invalid_validator');
        }

        if ($fundRequest->state !== FundRequest::STATE_PENDING) {
            return $this->deny('fund_request.not_pending');
        }

        // only assigned employee is allowed to resolve the request
        if (!FundRequestRecordQuery::whereIdentityIsAssignedEmployeeFilter(
            $fundRequest->records()->getQuery(),
            $identity_address,
            $organization->findEmployee($identity_address)->id
        )->exists()) {
            return $this->deny('fund_request.not_assigned_employee');
        }

        // only fund validators may update requests
        if (!in_array($identity_address, $fundRequest->fund->validatorEmployees(), true)) {
            return $this->deny('fund_request.invalid_validator');
        }

        return true;
    }

    /**
     * Determine whether the validator can resolve the fundRequest.
     *
     * @param string|null $identity_address
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function addPartnerBsnNumber(
        ?string $identity_address,
        FundRequest $fundRequest,
        Organization $organization
    ) {
        if (!$response = $this->resolveAsValidator(
            $identity_address,
            $fundRequest,
            $organization
        )) {
            return $response;
        }

        return $fundRequest->records()->where([
            'record_type_key' => 'partner_bsn'
        ])->doesntExist();
    }

    /**
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @return bool
     */
    private function checkIntegrityRequester(
        Fund $fund,
        FundRequest $fundRequest
    ): bool {
        return ($fund && $fundRequest) && ($fundRequest->fund_id === $fund->id);
    }

    /**
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return bool
     */
    private function checkIntegrityValidator(
        Organization $organization,
        FundRequest $fundRequest
    ): bool {
        $externalValidators = OrganizationQuery::whereIsExternalValidator(
            Organization::query(),
            $fundRequest->fund
        )->pluck('organizations.id')->toArray();

        return $fundRequest->fund->organization_id === $organization->id ||
            in_array($organization->id, $externalValidators, true);
    }
}
