<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class FundRequestRecordPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view FundRequestRecords.
     *
     * @param Identity $identity
     * @param FundRequest $fundRequest
     * @param Fund $fund
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function viewAnyAsRequester(
        Identity $identity,
        FundRequest $fundRequest,
        Fund $fund,
    ): Response|bool {
        if (!$this->checkIntegrityRequester($fund, $fundRequest)) {
            return $this->deny(trans('policies.fund_requests.invalid_endpoint'));
        }

        // only fund requester is allowed to see records
        if ($fundRequest->identity_id !== $identity->id) {
            return $this->deny(trans('policies.fund_requests.invalid_requester'));
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
        Fund $fund,
    ): Response|bool {
        if (!$this->checkIntegrityRequester($fund, $request, $requestRecord)) {
            return $this->deny(trans('policies.fund_requests.invalid_endpoint'));
        }

        // only fund requester is allowed to see records
        if ($request->identity_id !== $identity->id) {
            return $this->deny(trans('policies.fund_requests.invalid_requester'));
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
        Organization $organization,
    ): Response|bool {
        if (!$this->checkIntegrityValidator($organization, $fundRequest)) {
            return $this->deny(trans('policies.fund_requests.invalid_endpoint'));
        }

        if (!$organization->identityCan($identity, Permission::VALIDATE_RECORDS)) {
            return $this->deny(trans('policies.fund_requests.invalid_validator'));
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
        Organization $organization,
    ): Response|bool {
        if (!$this->checkIntegrityValidator($organization, $request, $requestRecord)) {
            return $this->deny(trans('policies.fund_requests.invalid_endpoint'));
        }

        if (!$organization->identityCan($identity, Permission::VALIDATE_RECORDS)) {
            return $this->deny(trans('policies.fund_requests.invalid_validator'));
        }

        return true;
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
        Organization $organization,
    ): Response|bool {
        if (!$this->checkIntegrityValidator($organization, $request, $requestRecord)) {
            return $this->deny(trans('policies.fund_requests.invalid_endpoint'));
        }

        if (!$organization->identityCan($identity, Permission::VALIDATE_RECORDS)) {
            return $this->deny(trans('policies.fund_requests.invalid_validator'));
        }

        return
            $request->isPending() &&
            $request->fund->organization->allow_fund_request_record_edit &&
            ($requestRecord->fund_request->employee->identity_address === $identity->address);
    }

    /**
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @param FundRequestRecord|null $fundRequestRecord
     * @return bool
     */
    private function checkIntegrityRequester(
        Fund $fund,
        FundRequest $fundRequest,
        FundRequestRecord $fundRequestRecord = null,
    ): bool {
        return
            $fundRequest->fund_id === $fund->id &&
            (!$fundRequestRecord || ($fundRequestRecord->fund_request_id !== $fundRequest->id));
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
        FundRequestRecord $fundRequestRecord = null,
    ): bool {
        return
            ($fundRequest->fund->organization_id === $organization->id) &&
            (!$fundRequestRecord || $fundRequestRecord->fund_request_id === $fundRequest->id);
    }
}
