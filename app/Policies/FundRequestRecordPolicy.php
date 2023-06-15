<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Models\Identity;
use App\Models\Organization;
use App\Scopes\Builders\FundRequestRecordQuery;
use App\Scopes\Builders\OrganizationQuery;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class FundRequestRecordPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view FundRequestRecords.
     *
     * @param Identity $identity
     * @param FundRequest $request
     * @param Fund $fund
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function viewAnyAsRequester(
        Identity $identity,
        FundRequest $request,
        Fund $fund
    ): Response|bool {
        if (!$this->checkIntegrityRequester($fund, $request)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        // only fund requester is allowed to see records
        if ($request->identity_address !== $identity->address) {
            return $this->deny('fund_requests.not_requester');
        }

        return true;
    }

    /**
     * Determine whether the user can view the fundRequestRecord.
     *
     * @param Identity $identity
     * @param FundRequestRecord $requestRecord
     * @param FundRequest $request
     * @param Fund $fund
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function viewAsRequester(
        Identity $identity,
        FundRequestRecord $requestRecord,
        FundRequest $request,
        Fund $fund
    ): Response|bool {
        if (!$this->checkIntegrityRequester($fund, $request, $requestRecord)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        // only fund requester is allowed to see records
        if ($request->identity_address !== $identity->address) {
            return $this->deny('fund_requests.not_requester');
        }

        return true;
    }

    /**
     * Determine whether the user can view fundRequestRecords.
     *
     * @param Identity $identity
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function viewAnyAsValidator(
        Identity $identity,
        FundRequest $fundRequest,
        Organization $organization
    ): Response|bool {
        if (!$this->checkIntegrityValidator($organization, $fundRequest)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        if (!$organization->identityCan($identity, 'validate_records')) {
            return $this->deny('fund_requests.invalid_validator');
        }

        return true;
    }

    /**
     * Determine whether the user can view the fundRequestRecord.
     *
     * @param Identity $identity
     * @param FundRequestRecord $requestRecord
     * @param FundRequest $request
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function viewAsValidator(
        Identity $identity,
        FundRequestRecord $requestRecord,
        FundRequest $request,
        Organization $organization
    ): Response|bool {
        if (!$this->checkIntegrityValidator($organization, $request, $requestRecord)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        if (!$organization->identityCan($identity, 'validate_records')) {
            return $this->deny('fund_requests.invalid_validator');
        }

        return true;
    }

    /**
     * Resolve fundRequestRecord as validator.
     *
     * @param Identity $identity
     * @param FundRequestRecord $requestRecord
     * @param FundRequest $request
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function resolveAsValidator(
        Identity $identity,
        FundRequestRecord $requestRecord,
        FundRequest $request,
        Organization $organization
    ): Response|bool {
        if (!$this->checkIntegrityValidator($organization, $request, $requestRecord)) {
            return $this->deny('fund_requests.invalid_endpoint');
        }

        if (!$organization->identityCan($identity, 'validate_records')) {
            return $this->deny('fund_requests.invalid_validator');
        }

        // only assigned employee is allowed to resolve the request
        if (!FundRequestRecordQuery::whereEmployeeIsAssignedValidator(
            $request->records(),
            $organization->findEmployee($identity->address)
        )->where('fund_request_records.id', $requestRecord->id)->exists()) {
            return $this->deny('fund_request.not_assigned_employee');
        }

        return $requestRecord->employee &&
            ($requestRecord->isPending()) &&
            ($requestRecord->employee->identity_address === $identity->address);
    }

    /**
     * Determine whether the validator can resolve the fundRequest.
     *
     * @param Identity $identity
     * @param FundRequestRecord $requestRecord
     * @param FundRequest $request
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function updateAsValidator(
        Identity $identity,
        FundRequestRecord $requestRecord,
        FundRequest $request,
        Organization $organization
    ): Response|bool {
        $result = $this->resolveAsValidator($identity, $requestRecord, $request, $organization);

        return $result === true ? $request->fund->organization->allow_fund_request_record_edit : $result;
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
