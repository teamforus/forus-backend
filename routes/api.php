<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/**
 * No authorization required
 */
Route::group([], static function() {
    Route::group(['prefix' => '/identity'], static function()  {
        Route::post('/', 'Api\IdentityController@store');
        Route::post('/validate/email', 'Api\IdentityController@storeValidateEmail');

        Route::group(['prefix' => '/proxy'], static function()  {
            Route::post('/code', 'Api\IdentityController@proxyAuthorizationCode');
            Route::post('/token', 'Api\IdentityController@proxyAuthorizationToken');
            Route::post('/email', 'Api\IdentityController@proxyAuthorizationEmailToken');

            // short tokens
            Route::post('/short-token', 'Api\IdentityController@proxyAuthorizationShortToken');
            Route::get('/short-token/exchange/{shortToken}', 'Api\IdentityController@proxyExchangeAuthorizationShortToken');

            // sign in by email
            Route::get('/email/redirect/{emailToken}', 'Api\IdentityController@emailTokenRedirect')->name('emailSignInRedirect');
            Route::get('/email/exchange/{emailToken}', 'Api\IdentityController@emailTokenExchange');

            // sign up, email confirmation
            Route::get('/confirmation/redirect/{exchangeToken}', 'Api\IdentityController@emailConfirmationRedirect')->name('emailSignUpRedirect');
            Route::get('/confirmation/exchange/{exchangeToken}', 'Api\IdentityController@emailConfirmationExchange');

            Route::get('/check-token', 'Api\IdentityController@checkToken');
        });

        /**
         * Record types
         */
        Route::group(['prefix' => '/record-types'], static function()  {
            Route::get('/', 'Api\Identity\RecordTypeController@index');
        });

        /**
         * Notification preferences
         */
        Route::get(
            'notification-preferences/{identity_address}/{exchange_token}',
            'Api\Platform\NotificationsController@index'
        );

        Route::post(
            'notification-preferences/{identity_address}/{exchange_token}/unsubscribe',
            'Api\Platform\NotificationsController@unsubscribe'
        );

        Route::post(
            'notification-preferences/{identity_address}/{exchange_token}',
            'Api\Platform\NotificationsController@update'
        );
    });
});

/**
 * Authorization required
 */
Route::group(['middleware' => ['api.auth']], static function()  {
    Route::group(['prefix' => '/identity'], static function()  {
        Route::get('/', 'Api\IdentityController@getPublic');
        Route::delete('/', 'Api\IdentityController@destroy');
        Route::resource('emails', 'Api\Identity\IdentityEmailsController')
            ->only('index', 'show', 'store', 'destroy')
            ->parameter('emails', 'identity_email');

        Route::post('emails/{identity_email}/resend', 'Api\Identity\IdentityEmailsController@resend');
        Route::patch('emails/{identity_email}/primary', 'Api\Identity\IdentityEmailsController@primary');

        /**
         * Identity proxies
         */
        Route::group(['prefix' => '/proxy'], static function()  {
            Route::delete('/', 'Api\IdentityController@proxyDestroy');

            Route::group(['prefix' => '/authorize'], static function()  {
                Route::post('/code', 'Api\IdentityController@proxyAuthorizeCode');
                Route::post('/token', 'Api\IdentityController@proxyAuthorizeToken');
            });
        });

        /**
         * Record categories
         */
        Route::group(['prefix' => '/record-categories'], static function()  {
            Route::get('/', 'Api\Identity\RecordCategoryController@index');
            Route::post('/', 'Api\Identity\RecordCategoryController@store');
            Route::patch('/sort', 'Api\Identity\RecordCategoryController@sort');
            Route::get('/{recordCategoryId}', 'Api\Identity\RecordCategoryController@show');
            Route::patch('/{recordCategoryId}', 'Api\Identity\RecordCategoryController@update');
            Route::delete('/{recordCategoryId}', 'Api\Identity\RecordCategoryController@destroy');
        });

        /**
         * Record
         */
        Route::group(['prefix' => '/records'], static function()  {
            Route::get('/', 'Api\Identity\RecordController@index');
            Route::post('/', 'Api\Identity\RecordController@store');
            Route::post('/validate', 'Api\Identity\RecordController@storeValidate');
            Route::get('/types', 'Api\Identity\RecordController@typeKeys');
            Route::patch('/sort', 'Api\Identity\RecordController@sort');
            Route::get('/{recordId}', 'Api\Identity\RecordController@show');
            Route::patch('/{recordId}', 'Api\Identity\RecordController@update');
            Route::patch('/{recordId}/validate', 'Api\Identity\RecordController@updateValidate');
            Route::delete('/{recordId}', 'Api\Identity\RecordController@destroy');
        });

        /**
         * Record validations
         */
        Route::group(['prefix' => '/record-validations'], static function()  {
            Route::post('/', 'Api\Identity\RecordValidationController@store');
            Route::get('/{recordUuid}', 'Api\Identity\RecordValidationController@show');
            Route::patch('/{recordUuid}/approve', 'Api\Identity\RecordValidationController@approve');
            Route::patch('/{recordUuid}/decline', 'Api\Identity\RecordValidationController@decline');
        });

        /**
         * Sessions validations
         */
        Route::resource('sessions', 'Api\Identity\SessionController')
            ->parameter('sessions', 'sessionUid')
            ->only( 'index', 'show');

        Route::patch('sessions/{sessionUid}/terminate', 'Api\Identity\SessionController@terminate');
        Route::patch('sessions/terminate', 'Api\Identity\SessionController@terminateAll');
    });

    Route::post('medias/{media_uid}/clone', 'Api\MediaController@clone');
    Route::resource('medias', 'Api\MediaController')
        ->only('index', 'show', 'store', 'destroy')
        ->parameter('medias', 'media_uid');

    if (config('file.enabled', false)) {
        Route::resource('files', 'Api\FileController')
            ->only('index', 'show', 'store')
            ->parameter('files', 'file_uid');

        Route::get('files/{file_uid}/download', 'Api\FileController@download');
        Route::post('files/validate', 'Api\FileController@storeValidate');
    }
});

Route::get('/status', 'Api\StatusController@getStatus')->name('status');
Route::post('/contact-form', 'ContactFormController@send');
Route::get('/exporteren', 'Api\BIConnectionController@index')
    ->middleware('bi_connection')
    ->name('bi-connection');
