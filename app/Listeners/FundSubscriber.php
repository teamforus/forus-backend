<?php

namespace App\Listeners;

use App\Events\Funds\FundCreated;
use Illuminate\Events\Dispatcher;

class FundSubscriber
{
    public function onFundCreated(FundCreated $fundCreated) {
        $fund = $fundCreated->getFund();
        $notificationService = resolve('forus.services.notification');

        if ($email = env('EMAIL_FOR_FUND_CREATED', false)) {
            $notificationService->newFundCreatedNotifyForus(
                $email,
                $fund->fund_config->implementation->getEmailFrom(),
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
