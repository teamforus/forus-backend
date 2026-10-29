<?php

use App\Http\Controllers\Api\IdentityController;
use App\Http\Controllers\Api\Platform\DevicesController;
use App\Http\Controllers\Api\Platform\FirestoreTokensController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Middleware\ClientTypeMiddleware;
use App\Http\Middleware\ClientVersionMiddleware;
use App\Http\Middleware\DomainMiddleware;
use App\Http\Middleware\ImplementationKeyMiddleware;
use App\Services\MeAppApiService\Http\Controllers\Shared\IdentityController as MobileIdentityController;
use App\Services\MeAppApiService\Http\Controllers\Shared\ProductCategoriesController;
use Illuminate\Support\Facades\Route;

// verify the custom API URL response in Android developer options
Route::get('status', [StatusController::class, 'getStatus'])
    ->name('status')
    ->withoutMiddleware([
        ClientTypeMiddleware::class, ClientVersionMiddleware::class,
        ImplementationKeyMiddleware::class, DomainMiddleware::class,
    ]);

// list product categories
Route::get('product-categories', [ProductCategoriesController::class, 'index'])->name('product-categories.index');

Route::prefix('identity')->name('identity.')->group(static function (): void {
    // send a login or signup email
    Route::post('/', [IdentityController::class, 'store'])->name('store');

    Route::prefix('proxy')->name('proxy.')->group(static function (): void {
        // create a pending PIN code login
        Route::post('code', [IdentityController::class, 'proxyAuthorizationCode'])->name('code');

        // check whether a pending login was authorized
        Route::get('check-token', [IdentityController::class, 'checkToken'])->name('check-token');

        // show the app login link or redirect to the web client
        Route::get('email/redirect/{emailToken}', [IdentityController::class, 'emailTokenRedirect'])
            ->name('email.redirect')
            ->withoutMiddleware([ClientTypeMiddleware::class, ClientVersionMiddleware::class]);

        // exchange an email login token for an access token
        Route::get('email/exchange/{emailToken}', [IdentityController::class, 'emailTokenExchange'])
            ->name('email.exchange');

        // show the app confirmation link or redirect to the web client
        Route::get('confirmation/redirect/{exchangeToken}', [IdentityController::class, 'emailConfirmationRedirect'])
            ->name('confirmation.redirect')
            ->withoutMiddleware([ClientTypeMiddleware::class, ClientVersionMiddleware::class]);

        // exchange a signup confirmation token for an access token
        Route::get('confirmation/exchange/{exchangeToken}', [IdentityController::class, 'emailConfirmationExchange'])
            ->name('confirmation.exchange');
    });
});

Route::middleware('api.auth')->group(static function (): void {
    Route::prefix('identity')->name('identity.')->group(static function (): void {
        // read identity details
        Route::get('/', [MobileIdentityController::class, 'show'])->name('show');
    });

    // register device token for push notifications
    Route::post('devices/register-push', [DevicesController::class, 'registerPush'])->name('devices.register-push');

    // remove a device token for push notifications
    Route::delete('devices/delete-push', [DevicesController::class, 'deletePush'])->name('devices.delete-push');

    // create a Firebase authentication token for app logging
    Route::post('firestore-tokens', [FirestoreTokensController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('firestore-tokens.store');
});
