<?php

namespace App\Services\Forus\Session;

use App\Services\Forus\Session\Models\Session;
use App\Services\Forus\Session\Models\SessionRequest;
use App\Services\Forus\Session\Policies\SessionPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class SessionServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Session::class => SessionPolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();
        $this->loadMigrationsFrom(__DIR__ . '/migrations');

        Route::bind('sessionUid', function ($value) {
            return Session::whereUid($value)->first() ?? abort(404);
        });

        Relation::morphMap([
            'session' => Session::class,
            'session_request' => SessionRequest::class,
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('forus.session', fn() => new SessionService());
    }
}