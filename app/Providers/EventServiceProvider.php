<?php

namespace App\Providers;

use App\Listeners\EmployeeSubscriber;
use App\Listeners\FundRequestClarificationSubscriber;
use App\Listeners\FundRequestRecordSubscriber;
use App\Listeners\FundRequestSubscriber;
use App\Listeners\FundSubscriber;
use App\Listeners\OrganizationSubscriber;
use App\Listeners\VoucherSubscriber;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        FundSubscriber::class,
        VoucherSubscriber::class,
        EmployeeSubscriber::class,
        FundRequestSubscriber::class,
        OrganizationSubscriber::class,
        FundRequestRecordSubscriber::class,
        FundRequestClarificationSubscriber::class,
    ];
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\Event' => [
            'App\Listeners\EventListener',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
