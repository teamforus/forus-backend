<?php

namespace App\Listeners;

use App\Events\FundRequests\FundRequestCreated;
use App\Events\FundRequests\FundRequestResolved;
use App\Models\FundRequest;
use App\Notifications\Organizations\FundRequests\FundRequestCreatedValidatorNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestCreatedNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestResolvedNotification;
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

        IdentityFundRequestResolvedNotification::send($eventLog);
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
    }
}
