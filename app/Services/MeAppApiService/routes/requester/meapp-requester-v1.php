<?php

use App\Services\MeAppApiService\Http\Controllers\Requester\VouchersController;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/../shared/meapp-shared-v1.php';

Route::middleware('api.auth')->prefix('vouchers')->name('vouchers.')->group(static function (): void {
    // list the identity's vouchers
    Route::get('/', [VouchersController::class, 'index'])->name('index');

    // read voucher details by number or token address
    Route::get('{voucher_number_or_address}', [VouchersController::class, 'show'])->name('show');

    // send the voucher to the identity's email address
    Route::post('{voucher_number_or_address}/send-email', [VouchersController::class, 'sendEmail'])->name('send-email');
});
