<?php

namespace App\Services\Forus\Session;

use App\Services\Forus\Session\Models\Session;
use App\Services\Forus\Session\Models\SessionRequestMeta;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class SessionServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');

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