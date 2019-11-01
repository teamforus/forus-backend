<?php

namespace App\Listeners;

use App\Events\FundRequestClarifications\FundRequestClarificationCreated;
use Illuminate\Events\Dispatcher;

class FundRequestClarificationSubscriber
{
    protected $recordService;
    protected $notificationService;

    /**
     * FundRequestSubscriber constructor.
     */
    public function __construct()
    {
        $this->recordService = resolve('forus.services.record');
        $this->notificationService = resolve('forus.services.notification');
    }

    public function onFundRequestClarificationCreated(
        FundRequestClarificationCreated $clarificationCreated
    ) {
        $clarification = $clarificationCreated->getFundRequestClarification();
        $fundRequest = $clarification->fund_request_record->fund_request;
        $identity_address = $fundRequest->identity_address;

        $webshopUrl = $fundRequest->fund->fund_config->
            implementation->url_webshop ?? env('WEB_SHOP_GENERAL_URL');

        $this->notificationService->sendFundRequestClarificationToRequester(
            $this->recordService->primaryEmailByAddress($identity_address),
            $fundRequest->identity_address,
            $fundRequest->fund->name,
            $clarification->question,
            $webshopUrl . sprintf(
                'funds/%s/requests/%s/clarifications/%s',
                $fundRequest->fund_id,
                $fundRequest->id,
                $clarification->id
            ),
            $webshopUrl
        );
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
