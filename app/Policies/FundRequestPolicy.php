<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Models\Organization;
use App\Scopes\Builders\EmployeeQuery;
use App\Scopes\Builders\FundRequestQuery;
use App\Scopes\Builders\FundRequestRecordQuery;
use App\Scopes\Builders\OrganizationQuery;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;

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
    public function viewAnyAsRequester(?string $identity_address, Fund $fund)
    {
        if (!$fund->isActive()) {
            return $this->deny('fund_not_active');
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
     * @noinspection PhpUnused
     */
    public function viewAsRequester(?string $identity_address, FundRequest $fundRequest, Fund $fund)
    {
        if (!$this->checkIntegrityRequester($fund, $fundRequest)) {
            return $this->deny('invalid_endpoint');
        }

        if ($fundRequest->identity_address !== $identity_address) {
            return $this->deny('not_requester');
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
    public function createAsRequester(?string $identity_address, Fund $fund)
    {
        if (!$fund->isActive()) {
            return $this->deny('fund_not_active');
        }

        if ($fund->fund_config->implementation->digid_required &&
            !record_repo()->bsnByAddress($identity_address)) {
            return $this->deny('bsn_record_is_mandatory');
        }

        // has pending fund requests
        if ($fund->fund_requests()->where(function(Builder $builder) use ($identity_address) {
            $builder->where('identity_address', $identity_address);
            $builder->where('state', FundRequest::STATE_PENDING);
        })->exists()) {
            return $this->deny('pending_request_exists');
        }

        // has approved fund requests where voucher is not expired
        if (FundRequestQuery::whereApprovedAndVoucherIsActive($fund->fund_requests(), $identity_address)->exists()) {
            return $this->deny('approved_request_exists');
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
    public function viewAnyAsValidator(?string $identity_address, Organization $organization)
    {
        if ($organization->employeesWithPermissionsQuery(['validate_records', 'manage_validators'])->where([
            'identity_address' => $identity_address,
        ])->doesntExist()) {
            return $this->deny('invalid_validator');
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
    public function exportAnyAsValidator(?string $identity_address, Organization $organization)
    {
        return $this->viewAnyAsValidator($identity_address, $organization);
    }

    /**
     * Determine whether the user can view the fundRequest.
     *
     * @param string|null $identity_address
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     * @noinspection PhpUnused
     */
    public function viewAsValidator(
        ?string $identity_address,
        FundRequest $fundRequest,
        Organization $organization
    ) {
        if (!$this->checkIntegrityValidator($organization, $fundRequest)) {
            return $this->deny('invalid_endpoint');
        }

        if (!$organization->findEmployee($identity_address)) {
            return $this->deny('not_employee');
        }

        $availableRecordsQuery = FundRequestRecordQuery::whereEmployeeIsValidatorOrSupervisor(
            $fundRequest->records(),
            $organization->findEmployee($identity_address)
        );

        if ($availableRecordsQuery->doesntExist()) {
            return $this->deny('not_validator');
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
            return $this->deny('invalid_endpoint');
        }

        if (!$organization->identityCan($identity_address, 'validate_records')) {
            return $this->deny('invalid_validator');
        }

        // only pending requests could be assigned
        if ($fundRequest->state !== FundRequest::STATE_PENDING) {
            return $this->deny('not_pending');
        }

        $recordsQuery = $fundRequest->records_pending()->whereDoesntHave('employee');
        $hasRecordsAvailable = EmployeeQuery::whereCanValidateRecords(
            $organization->employees()->where(compact('identity_address')),
            $recordsQuery->select('fund_request_records.id')->getQuery(),
        )->exists();

        // doesn't have pending vouchers that could be assigned
        if (!$hasRecordsAvailable) {
            return $this->deny('no_records_available');
        }

        return true;
    }

    /**
     * @param string|null $identity_address
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return bool|Response
     */
    private function baseResolveAsValidator(
        ?string $identity_address,
        FundRequest $fundRequest,
        Organization $organization
    ) {
        if (!$this->checkIntegrityValidator($organization, $fundRequest)) {
            return $this->deny('invalid_endpoint');
        }

        // only pending requests could be updated by fund validators
        if (!$fundRequest->isPending()) {
            return $this->deny('not_pending');
        }

        $recordsAssigned = FundRequestRecordQuery::whereEmployeeIsAssignedValidator(
            $fundRequest->records(),
            $organization->findEmployee($identity_address)
        );

        // need to have at least one record assigned to you
        if ((clone $recordsAssigned)->doesntExist()) {
            return $this->deny('no_records_assigned');
        }

        // should not have any records disregarded by you
        if ((clone $recordsAssigned)->where('state', FundRequestRecord::STATE_DISREGARDED)->exists()) {
            return $this->deny('has_disregarded_records');
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
        if (!$organization->identityCan($identity_address, 'validate_records')) {
            return $this->deny('invalid_validator');
        }

        return $this->baseResolveAsValidator($identity_address, $fundRequest, $organization);
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
        return $this->baseResolveAsValidator($identity_address, $fundRequest, $organization);
    }

    /**
     * Determine whether the validator can disregard the fundRequest.
     *
     * @param string|null $identity_address
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function disregard(
        ?string $identity_address,
        FundRequest $fundRequest,
        Organization $organization
    ) {
        if (!$response = $this->resolveAsValidator($identity_address, $fundRequest, $organization)) {
            return $response;
        }

        if ($organization->id !== $fundRequest->fund->organization_id) {
            return $this->deny('only_sponsor_employee');
        }

        return true;
    }

    /**
     * Determine whether the validator can disregard the fundRequest.
     *
     * @param string|null $identity_address
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function disregardUndo(
        ?string $identity_address,
        FundRequest $fundRequest,
        Organization $organization
    ) {
        if (!$response = $this->resolveAsValidator($identity_address, $fundRequest, $organization)) {
            return $response;
        }

        $requestsQuery = FundRequest::where([
            'fund_id' => $fundRequest->fund_id,
            'identity_address' => $fundRequest->identity_address,
        ])->where('id', '!=', $fundRequest->id);

        // has other pending requests
        if ((clone $requestsQuery->where('state', $fundRequest::STATE_PENDING))->exists()) {
            return $this->deny('fund_request_replaced');
        }

        // has other approved requests
        if (FundRequestQuery::whereApprovedAndVoucherIsActive(
            (clone $requestsQuery),
            $fundRequest->identity_address)->exists()) {
            return $this->deny('approved_request_exists');
        }

        if ($organization->id !== $fundRequest->fund->organization_id) {
            return $this->deny('only_sponsor_employee');
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
     * @noinspection PhpUnused
     */
    public function addPartnerBsnNumber(
        ?string $identity_address,
        FundRequest $fundRequest,
        Organization $organization
    ) {
        if (!$response = $this->resolveAsValidator($identity_address, $fundRequest, $organization)) {
            return $response;
        }

        if ($organization->id !== $fundRequest->fund->organization_id) {
            return $this->deny('only_sponsor_employee');
        }

        if (!$organization->bsn_enabled) {
            return $this->deny('bsn_not_enabled');
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
    private function checkIntegrityRequester(Fund $fund, FundRequest $fundRequest): bool
    {
        return $fundRequest->fund_id === $fund->id;
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

    /**
     * @param string|null $identity_address
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewPersonBSNData(?string $identity_address, Organization $organization): bool
    {
        return
            $organization->hasIConnectApiOin() &&
            $organization->identityCan($identity_address, 'view_person_bsn_data');
    }

    /**
     * Determine whether the user can view the fundRequest.
     *
     * @param string|null $identity_address
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function assignEmployeeAsSupervisor(
        ?string $identity_address,
        FundRequest $fundRequest,
        Organization $organization
    ) {
        if (!$this->checkIntegrityValidator($organization, $fundRequest)) {
            return $this->deny('invalid_endpoint');
        }

        if (!$organization->identityCan($identity_address, 'manage_validators')) {
            return $this->deny('invalid_permissions');
        }

        if ($fundRequest->state !== FundRequest::STATE_PENDING) {
            return $this->deny('not_pending');
        }

        return true;
    }

    /**
     * @param string|null $identity_address
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function resignEmployeeAsSupervisor(
        ?string $identity_address,
        FundRequest $fundRequest,
        Organization $organization
    ) {
        $records = $fundRequest->records()->whereHas('employee', fn(Builder $q) => $q->whereIn(
            'employees.id',
            $organization->employees()->select('employees.id')->getQuery()
        ));

        if ($records->doesntExist()) {
            return $this->deny('no_records_assigned');
        }

        return $this->assignEmployeeAsSupervisor($identity_address, $fundRequest, $organization);
    }

    /**
     * Throws an unauthorized exception.
     *
     * @param string $message
     * @param ?int $code
     * @return Response
     */
    protected function deny(string $message, ?int $code = null): Response
    {
        return Response::deny(trans('policies/fund_requests.' . $message), $code);
    }
}
