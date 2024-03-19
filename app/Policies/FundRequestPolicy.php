<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Models\Identity;
use App\Models\Note;
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
     * @param Identity $identity
     * @param Organization $organization
     *
     * @return Response|true
     *
     * @noinspection PhpUnused
     */
    public function viewAnyAsValidator(
        Identity $identity,
        Organization $organization
    ): bool|Response|bool {
        if (!$organization->identityCan($identity, [
            'validate_records', 'manage_validators',
        ], false)) {
            return $this->deny('invalid_validator');
        }

        return true;
    }

    /**
     * @param Identity $identity
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    private function baseResolveAsValidator(
        Identity $identity,
        FundRequest $fundRequest,
        Organization $organization
    ): Response|bool {
        if (!$this->checkIntegrityValidator($organization, $fundRequest)) {
            return $this->deny('invalid_endpoint');
        }

        // only pending requests could be updated by fund validators
        if (!$fundRequest->isPending()) {
            return $this->deny('not_pending');
        }

        $recordsAssigned = FundRequestRecordQuery::whereEmployeeIsAssignedValidator(
            $fundRequest->records(),
            $organization->findEmployee($identity->address)
        );

        // need to have at least one record assigned to you
        if ((clone $recordsAssigned)->doesntExist()) {
            return $this->deny('no_records_assigned');
        }

        // should not have any records disregarded by you
        if ((clone $recordsAssigned)->where('state', FundRequestRecord::STATE_DISREGARDED)->exists()) {
            return $this->deny('has_disregarded_records');
        }

        return $identity->exists;
    }

    /**
     * Determine whether the validator can resolve the fundRequest.
     *
     * @param Identity $identity
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function resolveAsValidator(
        Identity $identity,
        FundRequest $fundRequest,
        Organization $organization
    ): Response|bool {
        if (!$organization->identityCan($identity, 'validate_records')) {
            return $this->deny('invalid_validator');
        }

        return $this->baseResolveAsValidator($identity, $fundRequest, $organization);
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
     * Determine whether the user can view the fundRequest.
     *
     * @param Identity $identity
     * @param FundRequest $fundRequest
     * @param Organization $organization
     *
     * @return Response|true
     *
     * @noinspection PhpUnused
     */
    public function assignEmployeeAsSupervisor(
        Identity $identity,
        FundRequest $fundRequest,
        Organization $organization
    ): bool|Response|bool {
        if (!$this->checkIntegrityValidator($organization, $fundRequest)) {
            return $this->deny('invalid_endpoint');
        }

        if (!$organization->identityCan($identity, 'manage_validators')) {
            return $this->deny('invalid_permissions');
        }

        if ($fundRequest->state !== FundRequest::STATE_PENDING) {
            return $this->deny('not_pending');
        }

        return true;
    }

    /**
     * Throws an unauthorized exception.
     *
     * @param string $message
     * @param ?int $code
     * @return Response
     */
    protected function deny(mixed $message, ?int $code = null): Response
    {
        return Response::deny(trans('policies/fund_requests.' . $message), $code);
    }
}
