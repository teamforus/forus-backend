<?php

namespace App\Listeners;

use App\Events\Funds\FundCreated;
use Illuminate\Events\Dispatcher;

class FundSubscriber
{
    /**
     * @param FundCreated $fundCreated
     */
    public function onFundCreated(FundCreated $fundCreated) {
        $fund = $fundCreated->getFund();

        try {
            $fund->createWallet();
        } catch (\Exception $exception) {};

        $notificationService = resolve('forus.services.notification');

        $notificationService->newFundCreated(
            $fund->organization->email,
            $fund->organization->emailServiceId(),
            $fund->name,
            env('WEB_SHOP_GENERAL_URL')
        );

        if ($email = env('EMAIL_FOR_FUND_CREATED', false)) {
            $notificationService->newFundCreatedNotifyCompany(
                $email,
                $fund->name,
                $fund->organization->name
            );
        }
    }

    /**
     * The events dispatcher
     *
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(
            FundCreated::class,
            '\App\Listeners\FundSubscriber@onFundCreated'
        );
    }
}
