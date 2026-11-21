<?php

use App\Services\IdentityProviderService\Http\Controllers\IdentityProviderScimController;
use App\Services\IdentityProviderService\Http\Middleware\AuthenticateIdentityProviderScim;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/scim/{connection}/v2')
    ->middleware([
        'throttle:identity-providers-scim',
        AuthenticateIdentityProviderScim::class,
    ])
    ->group(function (): void {
        Route::get('ServiceProviderConfig', [IdentityProviderScimController::class, 'serviceProviderConfig']);
        Route::get('Schemas/{schema?}', [IdentityProviderScimController::class, 'schemas']);
        Route::get('ResourceTypes/{type?}', [IdentityProviderScimController::class, 'resourceTypes']);
        Route::get('Users', [IdentityProviderScimController::class, 'index']);
        Route::get('Users/{uid}', [IdentityProviderScimController::class, 'show']);
        Route::post('Users', [IdentityProviderScimController::class, 'store']);
        Route::match(['PUT', 'PATCH'], 'Users/{uid}', [IdentityProviderScimController::class, 'update']);
        Route::delete('Users/{uid}', [IdentityProviderScimController::class, 'destroy']);
    });
