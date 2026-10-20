<?php

use App\Http\Controllers\Api\Platform\Provider\TransactionsController as ProviderTransactionsController;
use App\Http\Controllers\Api\Platform\Provider\Vouchers\ProductsController;
use App\Http\Controllers\Api\Platform\Provider\Vouchers\ProductVouchersController;
use App\Http\Controllers\Api\Platform\Provider\VouchersController;
use App\Http\Controllers\Api\Platform\Vouchers\DemoTransactionController;
use App\Http\Controllers\Api\Platform\Vouchers\TransactionsController;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/../shared/meapp-shared-v1.php';

Route::middleware('api.auth')->group(static function (): void {
    Route::prefix('vouchers/{voucher_address_or_physical_code}')->name('vouchers.')->group(static function (): void {
        // read a scanned voucher by token address or physical card code
        Route::get('/', [VouchersController::class, 'show'])->name('show');

        // list linked product vouchers available to the provider
        Route::get('product-vouchers', [ProductVouchersController::class, 'index'])->name('product-vouchers.index');

        // list provider products available for the scanned voucher
        Route::get('products', [ProductsController::class, 'index'])->name('products.index');

        // create a payment using the scanned voucher
        Route::post('transactions', [TransactionsController::class, 'store'])->name('transactions.store');
    });

    // list the provider's transaction history
    Route::get('transactions', [ProviderTransactionsController::class, 'index'])->name('transactions.index');

    // update a demo payment state using its qr token
    Route::patch('demo/transactions/{demo_token}', [DemoTransactionController::class, 'update'])
        ->name('demo.transactions.update');
});
