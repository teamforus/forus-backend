<?php

namespace App\Listeners;

use App\Events\Funds\FundCreated;
use Illuminate\Events\Dispatcher;

class FundSubscriber
{
    public function onFundCreated(FundCreated $fundCreated) {
        $fund = $fundCreated->getFund();

        $notificationService = resolve('forus.services.notification');

        $notificationService->newFundCreated(
            $fund->organization->email,
            $fund->organization->emailServiceId(),
            $fund->name,
            env('WEB_SHOP_GENERAL_URL')
        );

        if ($email = env('EMAIL_FOR_FUND_CREATED', 'demo@forus.io')) {
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
