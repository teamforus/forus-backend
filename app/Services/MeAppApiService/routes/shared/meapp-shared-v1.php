<?php

use App\Http\Controllers\Api\IdentityController;
use App\Http\Controllers\Api\Platform\DevicesController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Middleware\ClientTypeMiddleware;
use App\Http\Middleware\ClientVersionMiddleware;
use App\Http\Middleware\DomainMiddleware;
use App\Http\Middleware\ImplementationKeyMiddleware;
use App\Services\MeAppApiService\Enums\RateLimiterName;
use App\Services\MeAppApiService\Http\Controllers\Shared\IdentityController as MobileIdentityController;
use App\Services\MeAppApiService\Http\Controllers\Shared\ProductCategoriesController;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

$clientMiddleware = [
    ClientVersionMiddleware::class,
    'client_version:1',
    ClientTypeMiddleware::class,
    'client_key:' . implode(',', Config::get('me-app-api.requester.client_types')),
    'client_key:' . implode(',', Config::get('me-app-api.provider.client_types')),
    ImplementationKeyMiddleware::class,
    'implementation_key:1,' . implode(',', Config::get('me-app-api.requester.client_keys')),
    'implementation_key:1,' . implode(',', Config::get('me-app-api.provider.client_keys')),
];

// verify the custom API URL response in Android developer options
Route::get('status', [StatusController::class, 'getStatus'])
    ->name('status')
    ->withoutMiddleware([
        ...$clientMiddleware, DomainMiddleware::class,
    ]);

// list product categories
Route::get('product-categories', [ProductCategoriesController::class, 'index'])->name('product-categories.index');

Route::prefix('identity')->name('identity.')->group(static function () use ($clientMiddleware): void {
    // send a login or signup email
    Route::post('/', [MobileIdentityController::class, 'store'])
        ->middleware('throttle:' . RateLimiterName::AuthEmail->value)->name('store');

    Route::prefix('proxy')->name('proxy.')->group(static function () use ($clientMiddleware): void {
        // create a pending PIN code login
        Route::post('code', [IdentityController::class, 'proxyAuthorizationCode'])
            ->middleware('throttle:' . RateLimiterName::AuthCreate->value)->name('code');

        // check whether a pending login was authorized
        Route::get('check-token', [IdentityController::class, 'checkToken'])
            ->middleware('throttle:' . RateLimiterName::AuthPoll->value)->name('check-token');

        // show the app login link or redirect to the web client
        Route::get('email/redirect/{emailToken}', [MobileIdentityController::class, 'emailTokenRedirect'])
            ->middleware('throttle:' . RateLimiterName::AuthRedirect->value)
            ->name('email.redirect')
            ->withoutMiddleware($clientMiddleware);

        // exchange an email login token for an access token
        Route::get('email/exchange/{emailToken}', [IdentityController::class, 'emailTokenExchange'])
            ->middleware('throttle:' . RateLimiterName::AuthExchange->value)
            ->name('email.exchange');

        // show the app confirmation link or redirect to the web client
        Route::get('confirmation/redirect/{exchangeToken}', [MobileIdentityController::class, 'emailConfirmationRedirect'])
            ->middleware('throttle:' . RateLimiterName::AuthRedirect->value)
            ->name('confirmation.redirect')
            ->withoutMiddleware($clientMiddleware);

        // exchange a signup confirmation token for an access token
        Route::get('confirmation/exchange/{exchangeToken}', [IdentityController::class, 'emailConfirmationExchange'])
            ->middleware('throttle:' . RateLimiterName::AuthExchange->value)
            ->name('confirmation.exchange');
    });
});

Route::middleware('api.auth')->group(static function (): void {
    Route::prefix('identity')->name('identity.')->group(static function (): void {
        // read identity details
        Route::get('/', [MobileIdentityController::class, 'show'])
            ->middleware('throttle:' . RateLimiterName::IdentityRead->value)->name('show');
    });

    // register device token for push notifications
    Route::post('devices/register-push', [DevicesController::class, 'registerPush'])->name('devices.register-push');

    // remove a device token for push notifications
    Route::delete('devices/delete-push', [DevicesController::class, 'deletePush'])->name('devices.delete-push');
});
