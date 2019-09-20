<?php

namespace App\Listeners;

use App\Events\Funds\FundCreated;
use App\Models\Fund;
use App\Models\Implementation;
use Illuminate\Events\Dispatcher;

class FundSubscriber
{
    public function onFundCreated(FundCreated $fundCreated) {
        $fund = $fundCreated->getFund();

        $notificationService = resolve('forus.services.mail_notification');

        $notificationService->newFundCreated(
            $fund->organization->email,
            $fund->organization->emailServiceId(),
            $fund->name,
            env('WEB_SHOP_GENERAL_URL')
        );

        $notificationService->newFundCreatedNotifyCompany(
            $fund->name,
            $fund->organization->name
        );


        // TODO: demo tweak
        if (false) {
            return;
        }

        /** @var Implementation $implementation */
        $implementation = Implementation::where('key', 'barneveld')->first();

        $fund->fund_config()->forceCreate(collect([
            'fund_id'           => $fund->id,
            'implementation_id' => $implementation->id,
            'key'               => $implementation->key . '_' . date('Y'),
            'bunq_sandbox'      => true,
            'bunq_key'          => env('DB_SEED_BUNQ_KEY', ''),
            'csv_primary_key'   => 'uid',
            'is_configured'     => true
        ])->toArray());

        $fund->fund_formulas()->create([
            'type'      => 'fixed',
            'amount'    => 600
        ]);

        $fund->changeState(Fund::STATE_ACTIVE);

        (new \LoremDbSeederDemo())->addProvidersToFund($fund);
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
