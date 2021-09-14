<?php

namespace App\Listeners;

use App\Events\FundRequests\FundRequestRecordDeclined;
use App\Events\FundRequests\FundRequestCreated;
use App\Events\FundRequests\FundRequestResolved;
use App\Models\FundRequest;
use App\Notifications\Identities\FundRequest\IdentityFundRequestApprovedNotification;
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

        $event = $fundRequest->log(FundRequest::EVENT_CREATED, [
            'fund' => $fundRequest->fund,
            'sponsor' => $fundRequest->fund->organization,
            'fund_request' => $fundRequest,
        ]);

        FundRequestCreatedValidatorNotification::send($event);
        IdentityFundRequestCreatedNotification::send($event);
    }

    /**
     * @param FundRequestResolved $fundCreated
     */
    public function onFundRequestResolved(FundRequestResolved $fundCreated): void
    {
        $fundRequest = $fundCreated->getFundRequest();

        $stateEvent = [
            FundRequest::EVENT_APPROVED => FundRequest::STATE_APPROVED,
            FundRequest::EVENT_DECLINED => FundRequest::STATE_DECLINED,
            FundRequest::EVENT_APPROVED_PARTLY => FundRequest::STATE_APPROVED_PARTLY,
        ][$fundRequest->state] ?? null;

        if ($stateEvent) {
            $fundRequest->log($stateEvent, [
                'fund' => $fundRequest->fund,
                'sponsor' => $fundRequest->fund->organization,
                'fund_request' => $fundRequest,
            ]);
        }

        $eventLog = $fundRequest->log(FundRequest::EVENT_RESOLVED, [
            'fund' => $fundRequest->fund,
            'sponsor' => $fundRequest->fund->organization,
            'fund_request' => $fundRequest,
        ]);

        if ($fundRequest->state === FundRequest::STATE_APPROVED) {
            IdentityFundRequestApprovedNotification::send($eventLog);
        } else {
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

        $event = $fundRequest->log($fundRequest::EVENT_RECORD_DECLINED, [
            'fund' => $fundRequest->fund,
            'sponsor' => $fundRequest->fund->organization,
        ], [
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
