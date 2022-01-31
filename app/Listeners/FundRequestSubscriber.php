<?php

namespace App\Listeners;

use App\Events\FundRequests\FundRequestRecordDeclined;
use App\Events\FundRequests\FundRequestCreated;
use App\Events\FundRequests\FundRequestResolved;
use App\Models\FundRequest;
use App\Notifications\Identities\FundRequest\IdentityFundRequestApprovedNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestDisregardedNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestRecordDeclinedNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestResolvedNotification;
use App\Notifications\Organizations\FundRequests\FundRequestCreatedValidatorNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestCreatedNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestDeniedNotification;
use Illuminate\Events\Dispatcher;

/**
 * Class FundRequestSubscriber
 * @package App\Listeners
 */
class FundRequestSubscriber
{
    /**
     * @param FundRequest $fundRequest
     * @return array
     */
    private function getFundRequestLogModels(FundRequest $fundRequest): array
    {
        return [
            'fund' => $fundRequest->fund,
            'sponsor' => $fundRequest->fund->organization,
            'fund_request' => $fundRequest,
            'implementation' => $fundRequest->fund->getImplementation(),
        ];
    }

    /**
     * @param FundRequestCreated $fundRequestCreated
     * @throws \Exception
     */
    public function onFundRequestCreated(FundRequestCreated $fundRequestCreated): void
    {
        $fund = $fundRequestCreated->getFund();
        $fundRequest = $fundRequestCreated->getFundRequest();
        $recordRepo = resolve('forus.services.record');
        $identityBsn = $recordRepo->bsnByAddress($fundRequest->identity_address);

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
     */
    public function onFundRequestResolved(FundRequestResolved $fundCreated): void
    {
        if (!$fundCreated->getFundRequest()->isResolved()) {
            return;
        }

        $fundRequest = $fundCreated->getFundRequest();
        $eventModels = $this->getFundRequestLogModels($fundRequest);
        $eventsList = array_combine($fundRequest::EVENTS, $fundRequest::EVENTS);

        $fundRequest->log($eventsList[$fundRequest->state], $eventModels);
        $eventLog = $fundRequest->log($fundRequest::EVENT_RESOLVED, $eventModels);

        if ($fundRequest->isDisregarded()) {
            if ($fundRequest->disregard_notify) {
                IdentityFundRequestDisregardedNotification::send($eventLog);
            }
        }

        if ($fundRequest->isApproved()) {
            IdentityFundRequestApprovedNotification::send($eventLog);
        } elseif (!$fundRequest->isDisregarded()) {
            IdentityFundRequestDeniedNotification::send($eventLog);
        }
    }

    /**
     * @param FundRequestRecordDeclined $requestRecordEvent
     */
    public function onFundRequestRecordDeclined(FundRequestRecordDeclined $requestRecordEvent): void
    {
        $fundRequest = $requestRecordEvent->getFundRequest();
        $fundRequestRecord = $requestRecordEvent->getFundRequestRecord();
        $eventModels = $this->getFundRequestLogModels($fundRequest);

        $event = $fundRequest->log($fundRequest::EVENT_RECORD_DECLINED, $eventModels, [
            'rejection_note' => $fundRequestRecord->note,
        ]);

        IdentityFundRequestRecordDeclinedNotification::send($event);
    }

    /**
     * The events dispatcher
     *
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            FundRequestCreated::class,
            '\App\Listeners\FundRequestSubscriber@onFundRequestCreated'
        );

        $events->listen(
            FundRequestResolved::class,
            '\App\Listeners\FundRequestSubscriber@onFundRequestResolved'
        );

        $events->listen(
            FundRequestRecordDeclined::class,
            '\App\Listeners\FundRequestSubscriber@onFundRequestRecordDeclined'
        );
    }
}
