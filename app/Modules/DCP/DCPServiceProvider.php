<?php

namespace App\Modules\DCP;

use App\Modules\DCP\Commands\SendDCPOrdersCommand;
use App\Modules\DCP\Commands\UpdateDCPOrdersStatusCommand;
use App\Modules\DCP\Listeners\DCPEventSubscriber;
use App\Modules\DCP\Models\DCPOrder;
use App\Modules\DCP\Models\DCPOrderBulk;
use App\Modules\DCP\Policies\DCPOrderBulkPolicy;
use App\Modules\DCP\Policies\DCPOrderPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class DCPServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/dcp.php', 'dcp');
        $this->commands([SendDCPOrdersCommand::class, UpdateDCPOrdersStatusCommand::class]);
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        Gate::policy(DCPOrder::class, DCPOrderPolicy::class);
        Gate::policy(DCPOrderBulk::class, DCPOrderBulkPolicy::class);

        Event::subscribe(DCPEventSubscriber::class);

        Relation::morphMap([
            'dcp_order' => DCPOrder::class,
            'dcp_order_bulk' => DCPOrderBulk::class,
        ]);

        $this->loadMigrationsFrom(__DIR__ . '/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/lang', 'dcp');
    }
}
