<?php

namespace App\Providers;

use App\Listeners\BankConnectionSubscriber;
use App\Listeners\EmployeeSubscriber;
use App\Listeners\FundProviderSubscriber;
use App\Listeners\FundRequestSubscriber;
use App\Listeners\FundSubscriber;
use App\Listeners\MollieConnectionSubscriber;
use App\Listeners\OrganizationSubscriber;
use App\Listeners\ProductReservationSubscriber;
use App\Listeners\ProductSubscriber;
use App\Listeners\ReimbursementSubscriber;
use App\Listeners\VoucherRecordSubscriber;
use App\Listeners\VoucherSubscriber;
use App\Listeners\VoucherTransactionsSubscriber;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Class EventServiceProvider
 * @package App\Providers
 */
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
        ProductSubscriber::class,
        EmployeeSubscriber::class,
        FundRequestSubscriber::class,
        OrganizationSubscriber::class,
        FundProviderSubscriber::class,
        ReimbursementSubscriber::class,
        VoucherRecordSubscriber::class,
        BankConnectionSubscriber::class,
        MollieConnectionSubscriber::class,
        ProductReservationSubscriber::class,
        VoucherTransactionsSubscriber::class,
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
    public function boot(): void
    {
        parent::boot();
    }
}
