<?php

namespace App\Listeners;

use App\Events\FundRequestClarifications\FundRequestClarificationRequested;
use App\Events\FundRequestRecords\FundRequestRecordApproved;
use App\Events\FundRequests\FundRequestAssigned;
use App\Events\FundRequests\FundRequestResigned;
use App\Events\FundRequestRecords\FundRequestRecordDeclined;
use App\Events\FundRequests\FundRequestCreated;
use App\Events\FundRequestRecords\FundRequestRecordAssigned;
use App\Events\FundRequestRecords\FundRequestRecordResigned;
use App\Events\FundRequests\FundRequestResolved;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Notifications\Identities\FundRequest\IdentityFundRequestApprovedNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestDisregardedNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestRecordFeedbackRequestedNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestRecordDeclinedNotification;
use App\Notifications\Organizations\FundRequests\FundRequestCreatedValidatorNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestCreatedNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestDeniedNotification;
use App\Scopes\Builders\FundQuery;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Gate;

class FundRequestSubscriber
{
    /**
     * @param FundRequestCreated $fundRequestCreated
     * @throws \Exception
     * @noinspection PhpUnused
     */
    public function onFundRequestCreated(FundRequestCreated $fundRequestCreated): void
    {
        $fund = $fundRequestCreated->getFund();
        $fundRequest = $fundRequestCreated->getFundRequest();
        $identityBsn = $fundRequest->identity?->bsn;

        // assign fund request to default validator
        if ($fund->default_validator_employee) {
            $fundRequest->assignEmployee($fund->default_validator_employee);
        }

        // auto approve request if required
        if (!empty($identityBsn) && $fund->isAutoValidatingRequests()) {
            $fundRequest->approve($fund->default_validator_employee);
        }

        $event = $fundRequest->log(
            $fundRequest::EVENT_CREATED,
            $this->getFundRequestLogModels($fundRequest)
        );

        FundRequestCreatedValidatorNotification::send($event);
        IdentityFundRequestCreatedNotification::send($event);
    }

    /**
     * @param FundRequestResolved $fundCreated
     * @noinspection PhpUnused
     */
    public function onFundRequestResolved(FundRequestResolved $fundCreated): void
    {
        if (!$fundCreated->getFundRequest()->isResolved()) {
            return;
        }

        $fundRequest = $fundCreated->getFundRequest();
        $eventModels = $this->getFundRequestLogModels($fundRequest);

        $eventsList = [
            $fundRequest::EVENT_DECLINED,
            $fundRequest::EVENT_APPROVED,
            $fundRequest::EVENT_APPROVED_PARTLY
        ];

        $fundRequest->log(array_combine($eventsList, $eventsList)[$fundRequest->state], $eventModels);
        $eventLog = $fundRequest->log($fundRequest::EVENT_RESOLVED, $eventModels);

        if ($fundRequest->isDisregarded() && $fundRequest->disregard_notify) {
            IdentityFundRequestDisregardedNotification::send($eventLog);
        }

        if ($fundRequest->isApproved()) {
            /** @var Fund[] $funds */
            $funds = [];
            $logScope = 'fund_request@' . $fundRequest->id;
            $sponsor = $fundRequest->fund->organization;
            $resolvePolicy = $sponsor->fund_request_resolve_policy;
            $sponsorFundsQuery = FundQuery::whereIsInternalConfiguredAndActive($sponsor->funds()->getQuery());

            if ($resolvePolicy == $sponsor::FUND_REQUEST_POLICY_AUTO_REQUESTED) {
                $funds = $sponsorFundsQuery->where('funds.id', $fundRequest->fund_id)->get();
            } else if ($resolvePolicy == $sponsor::FUND_REQUEST_POLICY_AUTO_AVAILABLE) {
                $funds = $sponsorFundsQuery->get();
            }

            foreach ($funds as $fund) {
                if (Gate::forUser($fundRequest->identity_address)->allows('apply', [$fund, $logScope])) {
                    $fund->makeVoucher($fundRequest->identity_address);
                    $fund->makeFundFormulaProductVouchers($fundRequest->identity_address);
                }
            }

            IdentityFundRequestApprovedNotification::send($eventLog);
        } elseif (!$fundRequest->isDisregarded()) {
            IdentityFundRequestDeniedNotification::send($eventLog);
        }
    }

    /**
     * @param FundRequestAssigned $event
     * @noinspection PhpUnused
     */
    public function onFundRequestAssigned(FundRequestAssigned $event): void
    {
        $fundRequest = $event->getFundRequest();
        $supervisorEmployee = $event->getSupervisorEmployee();

        $eventModels = $this->getFundRequestLogModels($fundRequest, [
            'employee' => $event->getEmployee(),
        ]);

        $fundRequest->log($fundRequest::EVENT_ASSIGNED, $eventModels, array_merge(
            $supervisorEmployee ? $this->getSupervisorFields($supervisorEmployee) : [],
        ));
    }

    /**
     * @param FundRequestResigned $event
     * @noinspection PhpUnused
     */
    public function onFundRequestResigned(FundRequestResigned $event): void
    {
        $fundRequest = $event->getFundRequest();
        $supervisorEmployee = $event->getSupervisorEmployee();

        $eventModels = $this->getFundRequestLogModels($fundRequest, [
            'employee' => $event->getEmployee(),
        ]);

        $fundRequest->log($fundRequest::EVENT_RESIGNED, $eventModels, array_merge(
            $supervisorEmployee ? $this->getSupervisorFields($supervisorEmployee) : [],
        ));
    }

    /**
     * @param FundRequestRecordApproved $requestRecordEvent
     * @noinspection PhpUnused
     */
    public function onFundRequestRecordApproved(FundRequestRecordApproved $requestRecordEvent): void
    {
        $fundRequestRecord = $requestRecordEvent->getFundRequestRecord();
        $eventModels = $this->getFundRequestRecordLogModels($fundRequestRecord);

        $fundRequestRecord->log($fundRequestRecord::EVENT_APPROVED, $eventModels);
    }

    /**
     * @param FundRequestRecordDeclined $requestRecordEvent
     * @noinspection PhpUnused
     */
    public function onFundRequestRecordDeclined(FundRequestRecordDeclined $requestRecordEvent): void
    {
        $fundRequestRecord = $requestRecordEvent->getFundRequestRecord();
        $eventModels = $this->getFundRequestRecordLogModels($fundRequestRecord);

        $event = $fundRequestRecord->log($fundRequestRecord::EVENT_DECLINED, $eventModels, [
            'rejection_note' => $fundRequestRecord->note,
        ]);

        IdentityFundRequestRecordDeclinedNotification::send($event);
    }

    /**
     * @param FundRequestRecordAssigned $event
     * @noinspection PhpUnused
     */
    public function onFundRequestRecordAssigned(FundRequestRecordAssigned $event): void
    {
        $fundRequestRecord = $event->getFundRequestRecord();
        $eventModels = $this->getFundRequestRecordLogModels($fundRequestRecord);
        $supervisorEmployee = $event->getSupervisorEmployee();

        $fundRequestRecord->log($fundRequestRecord::EVENT_ASSIGNED, $eventModels, array_merge(
            $supervisorEmployee ? $this->getSupervisorFields($supervisorEmployee) : [],
        ));
    }

    /**
     * @param FundRequestRecordResigned $event
     * @noinspection PhpUnused
     */
    public function onFundRequestRecordResigned(FundRequestRecordResigned $event): void
    {
        $fundRequestRecord = $event->getFundRequestRecord();
        $eventModels = $this->getFundRequestRecordLogModels($fundRequestRecord);
        $supervisorEmployee = $event->getSupervisorEmployee();

        $fundRequestRecord->log($fundRequestRecord::EVENT_RESIGNED, $eventModels, array_merge(
            $supervisorEmployee ? $this->getSupervisorFields($supervisorEmployee) : [],
        ));
    }

    /**
     * @param FundRequestClarificationRequested $clarificationCreated
     * @noinspection PhpUnused
     */
    public function onFundRequestClarificationRequested(
        FundRequestClarificationRequested $clarificationCreated
    ): void {
        $clarification = $clarificationCreated->getFundRequestClarification();
        $fundRequestRecord = $clarification->fund_request_record;

        $eventModels = $this->getFundRequestRecordLogModels($fundRequestRecord, [
            'fund_request_clarification' => $clarification,
        ]);

        IdentityFundRequestRecordFeedbackRequestedNotification::send($fundRequestRecord->log(
            $fundRequestRecord::EVENT_CLARIFICATION_REQUESTED,
            $eventModels
        ));
    }

    /**
     * @param FundRequest $fundRequest
     * @param array $extraModels
     * @return array
     */
    private function getFundRequestLogModels(
        FundRequest $fundRequest,
        array $extraModels = []
    ): array {
        return array_merge([
            'fund' => $fundRequest->fund,
            'sponsor' => $fundRequest->fund->organization,
            'fund_request' => $fundRequest,
            'implementation' => $fundRequest->fund->getImplementation(),
        ], $extraModels);
    }

    /**
     * @param FundRequestRecord $fundRequestRecord
     * @param array $extraModels
     * @return array
     */
    private function getFundRequestRecordLogModels(
        FundRequestRecord $fundRequestRecord,
        array $extraModels = []
    ): array {
        return array_merge($this->getFundRequestLogModels($fundRequestRecord->fund_request), array_merge([
            'fund_request_record' => $fundRequestRecord,
        ], $extraModels));
    }

    /**
     * @param Employee|null $supervisor
     * @return array
     */
    private function getSupervisorFields(?Employee $supervisor): array
    {
        return [
            'supervisor_employee_id' => $supervisor->id,
            'supervisor_employee_roles' => $supervisor->roles->pluck('name')->join(', '),
            'supervisor_employee_email' => $supervisor->identity?->email,
        ];
    }

    /**
     * The events dispatcher
     *
     * @param Dispatcher $events
     * @noinspection PhpUnused
     */
    public function subscribe(Dispatcher $events): void
    {
        $class = '\\' . static::class;

        $events->listen(FundRequestCreated::class, "$class@onFundRequestCreated");
        $events->listen(FundRequestResolved::class, "$class@onFundRequestResolved");

        $events->listen(FundRequestAssigned::class, "$class@onFundRequestAssigned");
        $events->listen(FundRequestResigned::class, "$class@onFundRequestResigned");

        $events->listen(FundRequestRecordDeclined::class, "$class@onFundRequestRecordDeclined");
        $events->listen(FundRequestRecordApproved::class, "$class@onFundRequestRecordApproved");
        $events->listen(FundRequestRecordAssigned::class, "$class@onFundRequestRecordAssigned");
        $events->listen(FundRequestRecordResigned::class, "$class@onFundRequestRecordResigned");

        $events->listen(FundRequestClarificationRequested::class, "$class@onFundRequestClarificationRequested");
    }
}
