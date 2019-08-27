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

Route::get('/', function () {
    /** @var \App\Models\Fund $fund */
    $fund = \App\Models\Fund::query()->first();
    $fundTwo = \App\Models\Fund::query()->latest()->first();

    //dd($fundTwo->topUpWallet(55));

    dd($fund->getWalletBalance(), $fundTwo->getWalletBalance());
    dd($fund->transferEtherToModel($fundTwo, 14));
});

Route::get('/test', function() {
    \App\Services\BunqService\BunqService::processBunqMeTabQueue(\App\Models\Fund::query()->find(1));
});