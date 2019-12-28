<?php

namespace App\Listeners;

use App\Events\FundRequestRecords\FundRequestRecordDeclined;
use Illuminate\Events\Dispatcher;

class FundRequestRecordSubscriber
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

    public function onFundRequestRecordDeclined(
        FundRequestRecordDeclined $fundRequestRecordDeclined
    ) {
        $requestRecord = $fundRequestRecordDeclined->getFundRequestRecord();
        $fundRequest = $requestRecord->fund_request;
        $identity_address = $fundRequest->identity_address;

        $this->notificationService->fundRequestRecordDeclined(
            $this->recordService->primaryEmailByAddress($identity_address),
            $fundRequest->identity_address,
            $requestRecord->note,
            $fundRequest->fund->name,
            env('WEB_SHOP_GENERAL_URL')
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
            FundRequestRecordDeclined::class,
            '\App\Listeners\FundRequestRecordSubscriber@onFundRequestRecordDeclined'
        );
    }
}
