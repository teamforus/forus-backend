<?php

namespace App\Services\Forus\Session;

use App\Services\Forus\Session\Models\Session;
use App\Services\Forus\Session\Models\SessionRequestMeta;

use App\Services\Forus\Session\Policies\SessionPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

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

        resolve('router')->bind('sessionUid', function ($value) {
            return Session::whereUid($value)->first() ?? abort(404);
        });

        Relation::morphMap([
            'session' => Session::class,
            'session_meta' => SessionRequestMeta::class,
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('forus.session', function () {
            return new SessionService();
        });
    }
}