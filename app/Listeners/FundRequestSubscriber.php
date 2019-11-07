<?php

namespace App\Listeners;

use App\Events\FundRequests\FundRequestCreated;
use App\Events\FundRequests\FundRequestResolved;
use Illuminate\Events\Dispatcher;

class FundRequestSubscriber
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

    public function onFundRequestCreated(FundRequestCreated $fundCreated) {
        $fundRequest = $fundCreated->getFundRequest();
        $identity_address = $fundRequest->identity_address;

        $this->notificationService->newFundRequestCreated(
            $this->recordService->primaryEmailByAddress($identity_address),
            $fundRequest->identity_address,
            $fundRequest->fund->name,
            $fundRequest->fund->urlWebshop()
        );
    }

    public function onFundRequestResolved(FundRequestResolved $fundCreated) {
        $fundRequest = $fundCreated->getFundRequest();
        $identity_address = $fundRequest->identity_address;

        $this->notificationService->fundRequestResolved(
            $this->recordService->primaryEmailByAddress($identity_address),
            $identity_address,
            $fundRequest->state,
            $fundRequest->fund->name,
            $fundRequest->fund->urlWebshop()
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
            FundRequestCreated::class,
            '\App\Listeners\FundRequestSubscriber@onFundRequestCreated'
        );

        $events->listen(
            FundRequestResolved::class,
            '\App\Listeners\FundRequestSubscriber@onFundRequestResolved'
        );
    }
}
