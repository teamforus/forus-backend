<?php

namespace App\Listeners;

use App\Events\FundRequestClarifications\FundRequestClarificationReceived;
use App\Events\FundRequestClarifications\FundRequestClarificationRequested;
use App\Events\FundRequestRecords\FundRequestRecordUpdated;
use App\Events\FundRequests\FundRequestAssigned;
use App\Events\FundRequests\FundRequestCreated;
use App\Events\FundRequests\FundRequestResigned;
use App\Events\FundRequests\FundRequestResolved;
use App\Models\Data\BankAccount;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Notifications\Identities\Employee\IdentityAssignedToFundRequestBySupervisorNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestApprovedNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestCreatedNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestDeniedNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestDisregardedNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestRecordFeedbackRequestedNotification;
use App\Notifications\Organizations\FundRequests\FundRequestCreatedValidatorNotification;
use App\Notifications\Organizations\FundRequests\FundRequestRecordFeedbackReceivedNotification;
use App\Scopes\Builders\FundQuery;
use Exception;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Gate;

class FundRequestSubscriber
{
    /**
     * @param FundRequestCreated $fundRequestCreated
     * @throws Exception
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
            $fundRequest->approve();
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
            $fundRequest::EVENT_DISREGARDED,
        ];

        if (in_array($fundRequest::EVENTS, $eventsList)) {
            $fundRequest->log(array_combine($eventsList, $eventsList)[$fundRequest->state], $eventModels);
        }

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
            } elseif ($resolvePolicy == $sponsor::FUND_REQUEST_POLICY_AUTO_AVAILABLE) {
                $funds = $sponsorFundsQuery->get();
            }

            foreach ($funds as $fund) {
                if (Gate::forUser($fundRequest->identity)->allows('apply', [$fund, $logScope])) {
                    $amount = $fund->id === $fundRequest->fund_id ? $fundRequest->getPaymentAmount() : null;

                    if ($fund->fund_config->isPayoutOutcome()) {
                        $fund->makePayout(
                            identity: $fundRequest->identity,
                            amount: $amount,
                            employee: $fundRequest->employee,
                            bankAccount: new BankAccount(
                                $fundRequest->getIban(),
                                $fundRequest->getIbanName(),
                            ),
                            voucherFields: [
                                'fund_request_id' => $fundRequest->id,
                            ],
                        );
                    } else {
                        $fund->makeVoucher(
                            $fundRequest->identity,
                            voucherFields: [
                                'fund_request_id' => $fundRequest->id,
                            ],
                            amount: $amount,
                        )?->dispatchCreated();
                    }

                    $fund->makeFundFormulaProductVouchers(
                        $fundRequest->identity,
                        voucherFields: [
                            'fund_request_id' => $fundRequest->id,
                        ]
                    );
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
        $employee = $event->getEmployee();
        $fundRequest = $event->getFundRequest();
        $supervisorEmployee = $event->getSupervisorEmployee();
        $eventModels = $this->getFundRequestLogModels($fundRequest, compact('employee'));

        $rawMeta = $supervisorEmployee ? $this->getSupervisorFields($supervisorEmployee) : [];
        $eventLog = $employee->log($employee::EVENT_FUND_REQUEST_ASSIGNED, $eventModels, $rawMeta);
        $fundRequest->log($fundRequest::EVENT_ASSIGNED, $eventModels, $rawMeta);

        if ($supervisorEmployee) {
            IdentityAssignedToFundRequestBySupervisorNotification::send($eventLog);
        }
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
     * @param FundRequestRecordUpdated $event
     * @noinspection PhpUnused
     */
    public function onFundRequestRecordUpdated(FundRequestRecordUpdated $event): void
    {
        $fundRequestRecord = $event->getFundRequestRecord();
        $eventModels = $this->getFundRequestRecordLogModels($fundRequestRecord, [
            'employee' => $event->getEmployee(),
        ]);

        $fundRequestRecord->log($fundRequestRecord::EVENT_UPDATED, $eventModels, [
            'fund_request_record_previous_value' => $event->getPreviousValue(),
        ]);
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
     * @param FundRequestClarificationReceived $clarificationReceived
     * @noinspection PhpUnused
     */
    public function onFundRequestClarificationReceived(
        FundRequestClarificationReceived $clarificationReceived
    ): void {
        $clarification = $clarificationReceived->getFundRequestClarification();
        $fundRequestRecord = $clarification->fund_request_record;

        $eventModels = $this->getFundRequestRecordLogModels($fundRequestRecord, [
            'fund_request_clarification' => $clarification,
        ]);

        FundRequestRecordFeedbackReceivedNotification::send($fundRequestRecord->log(
            $fundRequestRecord::EVENT_CLARIFICATION_RECEIVED,
            $eventModels
        ));
    }

    /**
     * The events dispatcher.
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

        $events->listen(FundRequestRecordUpdated::class, "$class@onFundRequestRecordUpdated");

        $events->listen(FundRequestClarificationRequested::class, "$class@onFundRequestClarificationRequested");
        $events->listen(FundRequestClarificationReceived::class, "$class@onFundRequestClarificationReceived");
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
     * @param Employee $supervisor
     * @return array
     */
    private function getSupervisorFields(Employee $supervisor): array
    {
        return [
            'supervisor_employee_id' => $supervisor->id,
            'supervisor_employee_roles' => $supervisor->roles->pluck('name')->join(', '),
            'supervisor_employee_email' => $supervisor->identity?->email,
        ];
    }
}
