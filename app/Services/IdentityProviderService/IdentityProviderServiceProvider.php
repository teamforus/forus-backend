<?php

namespace App\Services\IdentityProviderService;

use App\Services\IdentityProviderService\Commands\CleanupIdentityProviderSessions;
use App\Services\IdentityProviderService\Commands\VerifyIdentityProviderConnectionState;
use App\Services\IdentityProviderService\Contracts\IdentityProviderAdapterContract;
use App\Services\IdentityProviderService\Implementations\EntraAdapter;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use App\Services\IdentityProviderService\Models\IdentityProviderOidcSession;
use App\Services\IdentityProviderService\Policies\IdentityProviderConnectionPolicy;
use App\Services\IdentityProviderService\Policies\IdentityProviderMembershipPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class IdentityProviderServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        IdentityProviderConnection::class => IdentityProviderConnectionPolicy::class,
        IdentityProviderMembership::class => IdentityProviderMembershipPolicy::class,
    ];

    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(IdentityProviderAdapterContract::class, EntraAdapter::class);
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        Route::bind('identity_provider_connection', fn (string $uid): IdentityProviderConnection =>
            IdentityProviderConnection::where('uid', $uid)->firstOrFail());

        Route::bind('identity_provider_link', fn (string $uid): IdentityProviderMembership =>
            IdentityProviderMembership::where('uid', $uid)->firstOrFail());

        Route::bind('identity_provider_oidc_session', fn (string $uid): IdentityProviderOidcSession =>
            IdentityProviderOidcSession::where('uid', $uid)->firstOrFail());

        Relation::morphMap([
            'identity_provider_connection' => IdentityProviderConnection::class,
        ]);

        $this->registerPolicies();
        $this->loadMigrationsFrom(__DIR__ . '/migrations');

        $this->commands([
            CleanupIdentityProviderSessions::class,
            VerifyIdentityProviderConnectionState::class,
        ]);

        $this->registerRateLimiters();
    }

    /**
     * @return void
     */
    protected function registerRateLimiters(): void
    {
        RateLimiter::for('identity-providers-entra-authorization', function (Request $request): Limit {
            $limit = max(1, (int) Config::get('identity_providers.throttle.authorization_requests_per_minute', 20));

            return Limit::perMinute($limit)->by($request->ip());
        });

        RateLimiter::for('identity-providers-entra-callbacks-and-exchanges', function (Request $request): Limit {
            $limit = max(1, (int) Config::get('identity_providers.throttle.callbacks_and_exchanges_per_minute', 120));

            return Limit::perMinute($limit)->by($request->ip());
        });
    }
}
