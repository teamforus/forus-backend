<?php

namespace App\Listeners;

use App\Events\FundRequestClarifications\FundRequestClarificationCreated;
use App\Models\FundRequest;
use App\Notifications\Identities\FundRequest\IdentityFundRequestFeedbackRequestedNotification;
use Illuminate\Events\Dispatcher;

/**
 * Class FundRequestClarificationSubscriber
 * @package App\Listeners
 */
class FundRequestClarificationSubscriber
{
    /**
     * @param FundRequestClarificationCreated $clarificationCreated
     * @noinspection PhpUnused
     */
    public function onFundRequestClarificationCreated(
        FundRequestClarificationCreated $clarificationCreated
    ) {
        $clarification = $clarificationCreated->getFundRequestClarification();
        $fundRequest = $clarification->fund_request_record->fund_request;

        $eventLog = $fundRequest->log(FundRequest::EVENT_CLARIFICATION_REQUESTED, [
            'fund' => $fundRequest->fund,
            'fund_request' => $fundRequest,
            'fund_request_clarification' => $clarification
        ], [
            'sponsor_name' => $fundRequest->fund->organization->name
        ]);

        IdentityFundRequestFeedbackRequestedNotification::send($eventLog);
    }

    /**
     * The events dispatcher
     *
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(
            FundRequestClarificationCreated::class,
            '\App\Listeners\FundRequestClarificationSubscriber@onFundRequestClarificationCreated'
        );
    }
}
