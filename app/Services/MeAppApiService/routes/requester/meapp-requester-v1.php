<?php

use App\Services\MeAppApiService\Http\Controllers\Requester\FundsController;
use App\Services\MeAppApiService\Http\Controllers\Requester\ProductsController;
use App\Services\MeAppApiService\Http\Controllers\Requester\ProvidersController;
use App\Services\MeAppApiService\Http\Controllers\Requester\VouchersController;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/../shared/meapp-shared-v1.php';

// list funds for product filtering
Route::get('funds', [FundsController::class, 'index'])->name('funds.index');

// list providers for product filtering
Route::get('providers', [ProvidersController::class, 'index'])->name('providers.index');

// list products
Route::get('products', [ProductsController::class, 'index'])->name('products.index');

// get product details
Route::get('products/{product}', [ProductsController::class, 'show'])->whereNumber('product')->name('products.show');

Route::middleware('api.auth')->prefix('products')->name('products.')->group(static function (): void {
    // bookmark a product
    Route::post('{product}/bookmark', [ProductsController::class, 'bookmark'])
        ->whereNumber('product')->name('bookmark');

    // remove a product bookmark
    Route::delete('{product}/bookmark', [ProductsController::class, 'removeBookmark'])
        ->whereNumber('product')->name('remove-bookmark');
});

Route::middleware('api.auth')->prefix('vouchers')->name('vouchers.')->group(static function (): void {
    // list the identity's vouchers
    Route::get('/', [VouchersController::class, 'index'])->name('index');

    // read voucher details by number or token address
    Route::get('{voucher_number_or_address}', [VouchersController::class, 'show'])->name('show');

    // send the voucher to the identity's email address
    Route::post('{voucher_number_or_address}/send-email', [VouchersController::class, 'sendEmail'])->name('send-email');
});
