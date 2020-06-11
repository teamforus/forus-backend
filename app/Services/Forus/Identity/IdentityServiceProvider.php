<?php

namespace App\Services\Forus\Identity;

use App\Notifications\Organizations\Funds\BalanceLowNotification;
use App\Policies\IdentityEmailPolicy;
use App\Services\Forus\Identity\Models\Identity;
use App\Services\Forus\Identity\Models\IdentityEmail;
use App\Services\Forus\Identity\Repositories\IdentityRepo;
use App\Services\Forus\Identity\Repositories\Interfaces\IIdentityRepo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class IdentityServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        IdentityEmail::class => IdentityEmailPolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();
        $this->loadMigrationsFrom(__DIR__ . '/migrations');

        Relation::morphMap([
            'identity' => Identity::class,
        ]);
    }
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(IIdentityRepo::class, IdentityRepo::class);

        $this->app->singleton('forus.services.identity', function () {
            return app(IIdentityRepo::class);
        });
    }
}