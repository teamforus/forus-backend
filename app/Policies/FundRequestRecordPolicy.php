<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Models\Organization;
use App\Scopes\Builders\FundRequestRecordQuery;
use App\Scopes\Builders\OrganizationQuery;
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
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function viewAnyAsRequester(
        ?string $identity_address,
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
     * Determine whether the user can view the fundRequestRecord.
     *
     * @param string|null $identity_address
     * @param FundRequestRecord $requestRecord
     * @param FundRequest $request
     * @param Fund $fund
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function viewAsRequester(
        ?string $identity_address,
        FundRequestRecord $requestRecord,
        FundRequest $request,
        Fund $fund
    ) {
        if (!$this->checkIntegrityRequester($fund, $request, $requestRecord)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        // only fund requester is allowed to see records
        if ($request->identity_address !== $identity_address) {
            return $this->deny('fund_requests.not_requester');
        }

        return true;
    }

    /**
     * Determine whether the user can view fundRequestRecords.
     *
     * @param string|null $identity_address
     * @param FundRequest $fundRequest
     * @param Organization|null $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function viewAnyAsValidator(
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

        return true;
    }

    /**
     * Determine whether the user can view the fundRequestRecord.
     *
     * @param string|null $identity_address
     * @param FundRequestRecord $requestRecord
     * @param FundRequest $request
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function viewAsValidator(
        ?string $identity_address,
        FundRequestRecord $requestRecord,
        FundRequest $request,
        Organization $organization
    ) {
        if (!$this->checkIntegrityValidator($organization, $request, $requestRecord)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        if (!$organization->identityCan($identity_address, 'validate_records')) {
            return $this->deny('fund_requests.invalid_validator');
        }

        return true;
    }

    /**
     * Resolve fundRequestRecord as validator.
     *
     * @param string|null $identity_address
     * @param FundRequestRecord $requestRecord
     * @param FundRequest $request
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function resolveAsValidator(
        ?string $identity_address,
        FundRequestRecord $requestRecord,
        FundRequest $request,
        Organization $organization
    ) {
        if (!$this->checkIntegrityValidator($organization, $request, $requestRecord)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        if (!$organization->identityCan($identity_address, 'validate_records')) {
            return $this->deny('fund_requests.invalid_validator');
        }

        // only assigned employee is allowed to resolve the request
        if (!FundRequestRecordQuery::whereIdentityIsAssignedEmployeeFilter(
            $request->records()->getQuery(),
            $identity_address,
            $organization->findEmployee($identity_address)->id
        )->where('fund_request_records.id', $requestRecord->id)->exists()) {
            return $this->deny('fund_request.not_assigned_employee');
        }

        return $requestRecord->employee &&
            ($requestRecord->state === $requestRecord::STATE_PENDING) &&
            ($requestRecord->employee->identity_address === $identity_address);
    }

    /**
     * @param Fund $fund
     * @param FundRequest $request
     * @param FundRequestRecord|null $requestRecord
     * @return bool
     */
    private function checkIntegrityRequester(
        Fund $fund,
        FundRequest $request,
        FundRequestRecord $requestRecord = null
    ): bool {
        if ($request->fund_id !== $fund->id) {
            return false;
        }

        if ($requestRecord && ($requestRecord->fund_request_id !== $request->id)) {
            return false;
        }

        return true;
    }


    /**
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param FundRequestRecord|null $fundRequestRecord
     * @return bool
     */
    private function checkIntegrityValidator(
        Organization $organization,
        FundRequest $fundRequest,
        FundRequestRecord $fundRequestRecord = null
    ): bool {
        $externalValidators = OrganizationQuery::whereIsExternalValidator(
            Organization::query(),
            $fundRequest->fund
        )->pluck('organizations.id')->toArray();

        if (($fundRequest->fund->organization_id !== $organization->id) &&
            !in_array($organization->id, $externalValidators, true)) {
            return false;
        }

        return !$fundRequestRecord ||
            $fundRequestRecord->fund_request_id === $fundRequest->id;
    }
}
