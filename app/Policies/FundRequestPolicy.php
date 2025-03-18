<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Note;
use App\Models\Organization;
use App\Models\Permission;
use App\Scopes\Builders\EmailLogQuery;
use App\Scopes\Builders\FundRequestQuery;
use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\App;

class FundRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the fundRequest.
     *
     * @param Identity $identity
     * @param Fund|null $fund
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function viewAnyAsRequester(Identity $identity, ?Fund $fund = null): Response|bool
    {
        if ($fund && !$fund->isActive()) {
            return $this->deny(trans('policies.fund_requests.fund_not_active'));
        }

        return $identity->exists;
    }

    /**
     * Determine whether the user can view the fundRequest.
     *
     * @param Identity $identity
     * @param FundRequest $fundRequest
     * @param Fund $fund
     * @return bool|\Illuminate\Auth\Access\Response
     * @noinspection PhpUnused
     */
    public function viewAsRequester(
        Identity $identity,
        FundRequest $fundRequest,
        Fund $fund
    ): Response|bool {
        if (!$this->checkIntegrityRequester($fund, $fundRequest)) {
            return $this->deny(trans('policies.fund_requests.invalid_endpoint'));
        }

        if ($fundRequest->identity_id !== $identity->id) {
            return $this->deny(trans('policies.fund_requests.not_requester'));
        }

        return true;
    }

    /**
     * Determine whether the user can create fundRequests.
     *
     * @param Identity $identity
     * @param Fund $fund
     * @return bool|\Illuminate\Auth\Access\Response
     * @noinspection PhpUnused
     */
    public function createAsRequester(Identity $identity, Fund $fund): Response|bool
    {
        if (!$fund->isActive()) {
            return $this->deny(trans('policies.fund_requests.fund_not_active'));
        }

        if ($fund->fund_config->implementation->digid_required && !$identity->bsn) {
            return $this->deny(trans('policies.fund_requests.bsn_is_required'));
        }

        if ($fund->fund_config->email_required && !$identity->email) {
            return $this->deny(trans('policies.fund_requests.email_is_required'));
        }

        // has pending fund requests
        if ($fund->fund_requests()->where([
            'identity_id' => $identity->id,
            'state' => FundRequest::STATE_PENDING,
        ])->exists()) {
            return $this->deny(trans('policies.fund_requests.pending_request_exists'));
        }

        // has approved fund requests where voucher is not expired
        if (FundRequestQuery::whereApprovedAndVoucherIsActive(
            $fund->fund_requests(),
            $identity->id
        )->exists()) {
            return $this->deny(trans('policies.fund_requests.approved_request_exists'));
        }

        return $identity->exists;
    }

    /**
     * Determine whether the user can view the fundRequest.
     *
     * @param Identity $identity
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     * @noinspection PhpUnused
     */
    public function viewAnyAsValidator(
        Identity $identity,
        Organization $organization
    ): Response|bool {
        if (!$organization->identityCan($identity, [
            Permission::VALIDATE_RECORDS,
            Permission::MANAGE_VALIDATORS,
        ], false)) {
            return $this->deny(trans('policies.fund_requests.invalid_validator'));
        }

        return true;
    }

    /**
     * Determine whether the user can view the fundRequest.
     *
     * @param Identity $identity
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     * @noinspection PhpUnused
     */
    public function exportAnyAsValidator(
        Identity $identity,
        Organization $organization
    ): Response|bool {
        return $this->viewAnyAsValidator($identity, $organization);
    }

    /**
     * Determine whether the user can view the fundRequest.
     *
     * @param Identity $identity
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function viewAsValidator(
        Identity $identity,
        FundRequest $fundRequest,
        Organization $organization
    ): Response|bool {
        if (!$this->checkIntegrityValidator($organization, $fundRequest)) {
            return $this->deny(trans('policies.fund_requests.invalid_endpoint'));
        }

        return $this->viewAnyAsValidator($identity, $organization);
    }

    /**
     * Determine whether the user can update the fundRequest.
     *
     * @param Identity $identity
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     * @noinspection PhpUnused
     */
    public function assignAsValidator(
        Identity $identity,
        FundRequest $fundRequest,
        Organization $organization
    ): Response|bool {
        if (!$this->checkIntegrityValidator($organization, $fundRequest)) {
            return $this->deny(trans('policies.fund_requests.invalid_endpoint'));
        }

        if (!$organization->identityCan($identity, Permission::VALIDATE_RECORDS)) {
            return $this->deny(trans('policies.fund_requests.invalid_validator'));
        }

        // only pending requests could be assigned
        if (!$fundRequest->isPending()) {
            return $this->deny(trans('policies.fund_requests.not_pending'));
        }

        return !$fundRequest->employee;
    }

    /**
     * Determine whether the validator can resolve the fundRequest.
     *
     * @param Identity $identity
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @param bool $checkResolvingIssues
     * @return Response|bool
     */
    public function resolveAsValidator(
        Identity $identity,
        FundRequest $fundRequest,
        Organization $organization,
        bool $checkResolvingIssues = false,
    ): Response|bool {
        if (!$organization->identityCan($identity, Permission::VALIDATE_RECORDS)) {
            return $this->deny(trans('policies.fund_requests.invalid_validator'));
        }

        return $this->baseResolveAsValidator($identity, $fundRequest, $organization, $checkResolvingIssues);
    }

    /**
     * Determine whether the validator can approve the fundRequest.
     *
     * @param Identity $identity
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function approveAsValidator(
        Identity $identity,
        FundRequest $fundRequest,
        Organization $organization,
    ): Response|bool {
        return $this->resolveAsValidator($identity, $fundRequest, $organization, true);
    }

    /**
     * Determine whether the user can update the fundRequest.
     *
     * @param Identity $identity
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function resignAsValidator(
        Identity $identity,
        FundRequest $fundRequest,
        Organization $organization,
    ): Response|bool {
        return $this->baseResolveAsValidator($identity, $fundRequest, $organization);
    }

    /**
     * Determine whether the validator can disregard the fundRequest.
     *
     * @param Identity $identity
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     * @noinspection PhpUnused
     */
    public function disregard(
        Identity $identity,
        FundRequest $fundRequest,
        Organization $organization,
    ): Response|bool {
        return $this->resolveAsValidator($identity, $fundRequest, $organization);
    }

    /**
     * Determine whether the validator can disregard the fundRequest.
     *
     * @param Identity $identity
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     * @noinspection PhpUnused
     */
    public function disregardUndo(
        Identity $identity,
        FundRequest $fundRequest,
        Organization $organization
    ): Response|bool {
        if (!$response = $this->resolveAsValidator($identity, $fundRequest, $organization)) {
            return $response;
        }

        $query = FundRequest::where([
            'fund_id' => $fundRequest->fund_id,
            'identity_id' => $fundRequest->identity_id,
        ])->where('id', '!=', $fundRequest->id);

        // has other pending requests
        if ((clone $query->where('state', $fundRequest::STATE_PENDING))->exists()) {
            return $this->deny(trans('policies.fund_requests.fund_request_replaced'));
        }

        // has other approved requests
        if (FundRequestQuery::whereApprovedAndVoucherIsActive(
            (clone $query),
            $fundRequest->identity->id,
        )->exists()) {
            return $this->deny(trans('policies.fund_requests.approved_request_exists'));
        }

        return $fundRequest->isDisregarded();
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
    public function addPartnerBsnNumber(
        Identity $identity,
        FundRequest $fundRequest,
        Organization $organization
    ): Response|bool {
        if (!$response = $this->resolveAsValidator($identity, $fundRequest, $organization)) {
            return $response;
        }

        if (!$organization->bsn_enabled) {
            return $this->deny(trans('policies.fund_requests.bsn_not_enabled'));
        }

        return $fundRequest->records()->where([
            'record_type_key' => 'partner_bsn',
        ])->doesntExist();
    }

    /**
     * Determine whether the user can view the fundRequest.
     *
     * @param Identity $identity
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function assignEmployeeAsSupervisor(
        Identity $identity,
        FundRequest $fundRequest,
        Organization $organization,
    ): Response|bool {
        if (!$this->checkIntegrityValidator($organization, $fundRequest)) {
            return $this->deny(trans('policies.fund_requests.invalid_endpoint'));
        }

        if (!$organization->identityCan($identity, Permission::MANAGE_VALIDATORS)) {
            return $this->deny(trans('policies.fund_requests.invalid_permissions'));
        }

        if (!$fundRequest->isPending()) {
            return $this->deny(trans('policies.fund_requests.not_pending'));
        }

        return true;
    }

    /**
     * @param $identity $identity
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     * @noinspection PhpUnused
     */
    public function resignEmployeeAsSupervisor(
        Identity $identity,
        FundRequest $fundRequest,
        Organization $organization
    ): Response|bool {
        return $this->assignEmployeeAsSupervisor($identity, $fundRequest, $organization);
    }

    /**
     * @param Identity $identity
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function viewPersonBSNData(
        Identity $identity,
        FundRequest $fundRequest,
        Organization $organization,
    ): Response|bool {
        if (!$this->checkIntegrityValidator($organization, $fundRequest)) {
            return $this->deny(trans('policies.fund_requests.invalid_endpoint'));
        }

        if (!$organization->identityCan($identity, 'view_person_bsn_data')) {
            return $this->deny(trans('policies.fund_requests.invalid_validator'));
        }

        if (!$fundRequest->identity->bsn) {
            return $this->deny(trans('policies.fund_requests.bsn_is_unknown'));
        }

        if (!$organization->bsn_enabled) {
            return $this->deny(trans('policies.fund_requests.bsn_not_enabled'));
        }

        if (!$fundRequest->fund->hasIConnectApiOin()) {
            return $this->deny(trans('policies.fund_requests.iconnect_not_available'));
        }

        return true;
    }

    /**
     * Determine whether the user can view reimbursement notes.
     *
     * @param Identity $identity
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function viewAnyNoteAsValidator(
        Identity $identity,
        FundRequest $fundRequest,
        Organization $organization,
    ): Response|bool {
        if (!$this->checkIntegrityValidator($organization, $fundRequest)) {
            return $this->deny(trans('policies.fund_requests.invalid_endpoint'));
        }

        if (!$organization->identityCan($identity, Permission::VALIDATE_RECORDS)) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can store fund request note.
     *
     * @param Identity $identity
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function storeNoteAsValidator(
        Identity $identity,
        FundRequest $fundRequest,
        Organization $organization
    ): Response|bool {
        if (!$this->checkIntegrityValidator($organization, $fundRequest)) {
            return $this->deny(trans('policies.fund_requests.invalid_endpoint'));
        }

        if (!$organization->identityCan($identity, Permission::VALIDATE_RECORDS)) {
            return false;
        }

        return $fundRequest->employee->identity_address === $identity->address;
    }

    /**
     * Determine whether the user can delete reimbursement note.
     *
     * @param Identity $identity
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @param Note $note
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function destroyNoteAsValidator(
        Identity $identity,
        FundRequest $fundRequest,
        Organization $organization,
        Note $note
    ): Response|bool {
        if (!$this->checkIntegrityValidator($organization, $fundRequest)) {
            return $this->deny(trans('policies.fund_requests.invalid_endpoint'));
        }

        if (!$organization->identityCan($identity, Permission::VALIDATE_RECORDS)) {
            return false;
        }

        if ($note->employee?->identity_address !== $identity->address) {
            return $this->deny(trans('policies.fund_requests.not_note_author'));
        }

        return true;
    }

    /**
     * Determine whether the user can view reimbursement notes.
     *
     * @param Identity $identity
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function viewAnyEmailLogs(
        Identity $identity,
        FundRequest $fundRequest,
        Organization $organization
    ): Response|bool {
        return $this->viewAsValidator($identity, $fundRequest, $organization);
    }

    /**
     * Determine whether the user can export email logs.
     *
     * @param Identity $identity
     * @param EmailLog $emailLog
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @return Response|bool
     */
    public function exportEmailLog(
        Identity $identity,
        FundRequest $fundRequest,
        Organization $organization,
        EmailLog $emailLog,
    ): Response|bool {
        if (!EmailLogQuery::whereFundRequest(EmailLog::query(), $fundRequest)
            ->where('id', $emailLog->id)
            ->exists()) {
            return false;
        }

        return $this->viewAsValidator($identity, $fundRequest, $organization);
    }

    /**
     * @param Identity $identity
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @param bool $checkResolvingIssues
     * @return Response|bool
     */
    private function baseResolveAsValidator(
        Identity $identity,
        FundRequest $fundRequest,
        Organization $organization,
        bool $checkResolvingIssues = false,
    ): Response|bool {
        if (!$this->checkIntegrityValidator($organization, $fundRequest)) {
            return $this->deny(trans('policies.fund_requests.invalid_endpoint'));
        }

        // only pending requests could be updated by fund validators
        if (!$fundRequest->isPending()) {
            return $this->deny(trans('policies.fund_requests.not_pending'));
        }

        // should be properly configured
        if ($checkResolvingIssues && ($error = $fundRequest->getResolvingError())) {
            $fundRequest->fund->logError($error, ['fund_request_id' => $fundRequest->id]);

            return $this->deny(App::hasDebugModeEnabled() ?
                $error :
                trans('policies.fund_requests.configuration_issue'));
        }

        // has to be assigned
        if ($fundRequest->employee?->identity_address !== $identity->address) {
            return $this->deny(trans('policies.fund_requests.not_assigned'));
        }

        return true;
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
        FundRequest $fundRequest,
    ): bool {
        return $fundRequest->fund->organization_id === $organization->id;
    }
}
