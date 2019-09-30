<?php

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

use App\Http\Controllers\NotificationsController;

Route::get('/', function () {
    return "";
});

Route::get('/notifications/unsubscribe/{unsubscribeToken}', 'NotificationsController@unsubscribe');
Route::get('/notifications/subscribe/{unsubscribeToken}', 'NotificationsController@subscribe');
