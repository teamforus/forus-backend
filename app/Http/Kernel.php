<?php

namespace App\Http;

use App\Http\Middleware\ApiAuthMiddleware;
use App\Http\Middleware\ClientTypeMiddleware;
use App\Http\Middleware\ClientVersionMiddleware;
use App\Http\Middleware\DomainDigIdMiddleware;
use App\Http\Middleware\DomainMiddleware;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\ImplementationKeyMiddleware;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Middleware\ParseApiDependencyMiddleware;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\VerifyCsrfToken;
use App\Services\Forus\Auth2FAService\Middleware\Auth2FAMiddleware;
use App\Services\Forus\Session\Middleware\ForusSessionMiddleware;
use App\Services\QueryCounterService\Middleware\LogQueryCountMiddleware;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Http\Middleware\HandleCors;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        TrimStrings::class,
        ParseApiDependencyMiddleware::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        TrustProxies::class,
        HandleCors::class,
        LogQueryCountMiddleware::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            'domain',
        ],

        'api' => [
            // 'throttle:120D,1',
            'bindings',
            'locale',
            'domain',
        ],

        'api.auth' => [
            ApiAuthMiddleware::class,
            Auth2FAMiddleware::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $middlewareAliases = [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => RedirectIfAuthenticated::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'forus_session' => ForusSessionMiddleware::class,
        'implementation_key' => ImplementationKeyMiddleware::class,
        'client_key' => ClientTypeMiddleware::class,
        'client_version' => ClientVersionMiddleware::class,
        'locale' => LocaleMiddleware::class,
        'domain' => DomainMiddleware::class,
        'domain.digid' => DomainDigIdMiddleware::class,
        'forward_headers' => \App\Http\Middleware\ForwardHeadersMiddleware::class,
    ];
}
