<?php

namespace App\Services\DigIdService;

use App\Services\DigIdService\Console\Commands\DigIdSessionsCleanupCommand;
use App\Services\DigIdService\Models\DigIdSession;
use App\Services\DigIdService\Policies\DigIdSessionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class DigIdServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        DigIdSession::class => DigIdSessionPolicy::class,
    ];

    /**
     * @return void
     */
    public function boot(): void
    {
        $this->registerPolicies();
        $this->commands(DigIdSessionsCleanupCommand::class);
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }

    /**
     * @return void
     */
    public function register(): void
    {
        parent::register();

        $this->app->singleton('tvs_service', fn () => new TvsService());
        $this->app->alias('tvs_service', TvsService::class);
    }
}
