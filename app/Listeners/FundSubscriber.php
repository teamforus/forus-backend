<?php

namespace App\Listeners;

use App\Events\Funds\FundCreated;
use App\Models\Organization;
use App\Models\OrganizationProductCategory;
use Illuminate\Events\Dispatcher;

class FundSubscriber
{
    public function onFundCreated(FundCreated $fundCreated) {
        $fund = $fundCreated->getFund();

        $fund->criteria()->create([
            'record_type_key' => 'kindpakket_2018_eligible',
            'value' => "Ja",
            'operator' => '='
        ]);

        $fund->criteria()->create([
            'record_type_key' => 'children_nth',
            'value' => 1,
            'operator' => '>='
        ]);

        $organizations = Organization::query()->whereIn(
            'id', OrganizationProductCategory::query()->whereIn(
            'product_category_id',
            $fund->product_categories
        )->pluck('organization_id')->toArray()
        )->get();

        $notificationService = resolve('forus.services.mail_notification');

        $notificationService->newFundCreated(
            $fund->organization->identity_address,
            $fund->name,
            env('WEB_SHOP_GENERAL_URL')
        );

        /** @var Organization $organization */
        foreach ($organizations as $organization) {
            $notificationService->newFundApplicable(
                $organization->identity_address,
                $fund->name,
                config('forus.front_ends.panel-provider')
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
