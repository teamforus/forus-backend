<?php

namespace App\Services\MeAppApiService;

use App\Services\MeAppApiService\Enums\RateLimiterName;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MeAppApiServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config.php', 'me-app-api');
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        $this->registerRateLimiters();

        Route::middleware('api')->group(function (): void {
            Route::prefix(trim(Config::get('me-app-api.requester.namespace'), '/') . '/v1')
                ->middleware([
                    'implementation_key:1,' . implode(',', Config::get('me-app-api.requester.client_keys')),
                    'client_key:' . implode(',', Config::get('me-app-api.requester.client_types')),
                    'client_version:1', 'forus_session',
                ])
                ->name('mobile.requester.v1.')
                ->group(function (): void {
                    $this->loadRoutesFrom(__DIR__ . '/routes/requester/meapp-requester-v1.php');
                });

            Route::prefix(trim(Config::get('me-app-api.provider.namespace'), '/') . '/v1')
                ->middleware([
                    'implementation_key:1,' . implode(',', Config::get('me-app-api.provider.client_keys')),
                    'client_key:' . implode(',', Config::get('me-app-api.provider.client_types')),
                    'client_version:1', 'forus_session',
                ])
                ->name('mobile.provider.v1.')
                ->group(function (): void {
                    $this->loadRoutesFrom(__DIR__ . '/routes/provider/meapp-provider-v1.php');
                });
        });
    }

    /**
     * @return void
     */
    private function registerRateLimiters(): void
    {
        foreach ([
            RateLimiterName::AuthEmail->value => Config::get('me-app-api.throttles.auth_email'),
            RateLimiterName::AuthCreate->value => Config::get('me-app-api.throttles.auth_create'),
            RateLimiterName::AuthExchange->value => Config::get('me-app-api.throttles.auth_exchange'),
            RateLimiterName::AuthPoll->value => Config::get('me-app-api.throttles.auth_poll'),
        ] as $name => $settings) {
            RateLimiter::for($name, static fn (Request $request): Limit => Limit::perMinutes(
                $settings['decay_minutes'],
                $settings['attempts'],
            )->by($request->ip()));
        }

        foreach ([
            RateLimiterName::AuthRedirect->value => Config::get('me-app-api.throttles.auth_redirect'),
        ] as $name => $settings) {
            RateLimiter::for($name, static fn (Request $request): Limit => Limit::perMinutes(
                $settings['decay_minutes'],
                $settings['attempts'],
            )->by($request->ip())->response(static function (Request $request, array $headers): Response {
                return resolve(MeAppApiService::class)->authErrorResponse(
                    'Too many attempts',
                    'Wait a moment, then open the link again.',
                    429,
                    $headers,
                );
            }));
        }

        foreach ([
            RateLimiterName::AuthApprove->value => Config::get('me-app-api.throttles.auth_approve'),
            RateLimiterName::IdentityRead->value => Config::get('me-app-api.throttles.identity_read'),
        ] as $name => $settings) {
            RateLimiter::for($name, static function (Request $request) use ($settings): Limit {
                $identity = $request->user();

                return Limit::perMinutes($settings['decay_minutes'], $settings['attempts'])->by(
                    $identity ? 'identity:' . $identity->getAuthIdentifier() : 'ip:' . $request->ip(),
                );
            });
        }
    }
}
