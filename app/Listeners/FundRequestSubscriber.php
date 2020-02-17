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

    /**
     * @param FundRequestCreated $fundRequestCreated
     * @throws \Exception
     */
    public function onFundRequestCreated(
        FundRequestCreated $fundRequestCreated
    ) {
        $fund = $fundRequestCreated->getFund();
        $fundRequest = $fundRequestCreated->getFundRequest();
        $identity_address = $fundRequest->identity_address;

        $recordRepo = resolve('forus.services.record');

        // assign fund request to default validator
        if ($fund->default_validator_employee) {
            $fundRequest->assignEmployee($fund->default_validator_employee);
        }

        // auto approve request if required
        if ($fund->default_validator_employee &&
            $fund->auto_requests_validation &&
            $fundRequest->employee &&
            !empty($recordRepo->bsnByAddress($fundRequest->identity_address))
        ) {
            $fundRequest->approve();
        } else {
            $this->notificationService->newFundRequestCreated(
                $this->recordService->primaryEmailByAddress($identity_address),
                $fundRequest->identity_address,
                $fund->name,
                $fund->urlWebshop()
            );
        }
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
