<?php

use App\Http\Controllers\Api\Identity\RecordCategoryController;
use App\Http\Controllers\Api\Identity\RecordController;
use App\Http\Controllers\Api\Identity\RecordTypeController;
use App\Http\Controllers\Api\Platform\VouchersController;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/../shared/meapp-shared-v1.php';

// list available record types
Route::get('identity/record-types', [RecordTypeController::class, 'index'])->name('identity.record-types.index');

Route::middleware('api.auth')->prefix('identity')->name('identity.')->group(static function (): void {
    // identity records
    Route::prefix('records')->name('records.')->group(static function (): void {
        // list active or archived identity records
        Route::get('/', [RecordController::class, 'index'])->name('index');

        // create an identity record
        Route::post('/', [RecordController::class, 'store'])->name('store');

        // read identity record details
        Route::get('{recordId}', [RecordController::class, 'show'])->name('show');

        // archive an identity record
        Route::delete('{recordId}', [RecordController::class, 'destroy'])->name('destroy');
    });

    // identity record categories
    Route::prefix('record-categories')->name('record-categories.')->group(static function (): void {
        // list identity record categories
        Route::get('/', [RecordCategoryController::class, 'index'])->name('index');

        // create an identity record category
        Route::post('/', [RecordCategoryController::class, 'store'])->name('store');

        // read identity record category details
        Route::get('{recordCategoryId}', [RecordCategoryController::class, 'show'])->name('show');
    });
});

Route::middleware('api.auth')->prefix('vouchers')->name('vouchers.')->group(static function (): void {
    // list the identity's vouchers
    Route::get('/', [VouchersController::class, 'index'])->name('index');

    // read voucher details by number or token address
    Route::get('{voucher_number_or_address}', [VouchersController::class, 'show'])->name('show');

    // send the voucher to the identity's email address
    Route::post('{voucher_number_or_address}/send-email', [VouchersController::class, 'sendEmail'])->name('send-email');
});
