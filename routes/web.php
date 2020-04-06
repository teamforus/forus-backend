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

$router = resolve('router');

/**
 * Authorization not required
 */
$router->get('/', function () {
    return "";
});

$router->get('/notifications/unsubscribe/{unsubscribeToken}', 'NotificationsController@unsubscribe');
$router->get('/notifications/subscribe/{unsubscribeToken}', 'NotificationsController@subscribe');

$router->get('/email-verification/{identity_email_token}', 'Api\Identity\IdentityEmailsController@emailVerificationToken');