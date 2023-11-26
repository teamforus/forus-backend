<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/**
 * Authorization not required
 */
Route::get('/', function () {
    return "";
});

Route::get('/notifications/unsubscribe/{unsubscribeToken}', 'NotificationsController@unsubscribe');
Route::get('/notifications/subscribe/{unsubscribeToken}', 'NotificationsController@subscribe');

Route::get('/email-verification/{identity_email_token}', 'Api\Identity\IdentityEmailsController@emailVerificationToken');

Route::get('/bng/bank-connections/{bngBankConnectionToken}', 'BNGController@bankConnectionRedirect');
Route::get('/bng/payment-bulks/{bngVoucherTransactionBulkToken}', 'BNGController@voucherTransactionBulkRedirect');

Route::get('/mollie/callback', 'MollieController@processCallback')->name('mollie.callback');
Route::post('/mollie/webhooks', 'MollieController@processWebhook')->name('mollie.webhook');