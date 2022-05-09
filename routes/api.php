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

/** @var \Illuminate\Support\Facades\Route $router */
$router = resolve('router');

/**
 * No authorization required
 */
Route::group([], static function() use ($router) {
    $router->group(['prefix' => '/identity'], static function() use ($router) {
        $router->post('/', 'Api\IdentityController@store');
        $router->post('/validate/email', 'Api\IdentityController@storeValidateEmail');

        $router->group(['prefix' => '/proxy'], static function() use ($router) {
            $router->post('/code', 'Api\IdentityController@proxyAuthorizationCode');
            $router->post('/token', 'Api\IdentityController@proxyAuthorizationToken');
            $router->post('/email', 'Api\IdentityController@proxyAuthorizationEmailToken');

            // short tokens
            $router->post('/short-token', 'Api\IdentityController@proxyAuthorizationShortToken');
            $router->get('/short-token/exchange/{shortToken}', 'Api\IdentityController@proxyExchangeAuthorizationShortToken');

            // sign in by email
            $router->get('/email/redirect/{emailToken}', 'Api\IdentityController@emailTokenRedirect')->name('emailSignInRedirect');
            $router->get('/email/exchange/{emailToken}', 'Api\IdentityController@emailTokenExchange');

            // sign up, email confirmation
            $router->get('/confirmation/redirect/{exchangeToken}', 'Api\IdentityController@emailConfirmationRedirect')->name('emailSignUpRedirect');
            $router->get('/confirmation/exchange/{exchangeToken}', 'Api\IdentityController@emailConfirmationExchange');

            $router->get('/check-token', 'Api\IdentityController@checkToken');
        });

        /**
         * Record types
         */
        $router->group(['prefix' => '/record-types'], static function() use ($router) {
            $router->get('/', 'Api\Identity\RecordTypeController@index');
        });

        /**
         * Notification preferences
         */
        $router->get(
            'notification-preferences/{identity_address}/{exchange_token}',
            'Api\Platform\NotificationsController@index'
        );

        $router->post(
            'notification-preferences/{identity_address}/{exchange_token}/unsubscribe',
            'Api\Platform\NotificationsController@unsubscribe'
        );

        $router->post(
            'notification-preferences/{identity_address}/{exchange_token}',
            'Api\Platform\NotificationsController@update'
        );
    });
});

/**
 * Authorization required
 */
Route::group(['middleware' => ['api.auth']], static function() use ($router) {
    $router->group(['prefix' => '/identity'], static function() use ($router) {
        $router->get('/', 'Api\IdentityController@getPublic');
        $router->delete('/', 'Api\IdentityController@destroy');
        $router->resource('emails', 'Api\Identity\IdentityEmailsController', [
            'only' => ['index', 'show', 'store', 'destroy'],
            'parameters' => [
                'emails' => 'identity_email'
            ]
        ]);
        $router->post('emails/{identity_email}/resend', 'Api\Identity\IdentityEmailsController@resend');
        $router->patch('emails/{identity_email}/primary', 'Api\Identity\IdentityEmailsController@primary');

        /**
         * Identity proxies
         */
        $router->group(['prefix' => '/proxy'], static function() use ($router) {
            $router->delete('/', 'Api\IdentityController@proxyDestroy');

            $router->group(['prefix' => '/authorize'], static function() use ($router) {
                $router->post('/code', 'Api\IdentityController@proxyAuthorizeCode');
                $router->post('/token', 'Api\IdentityController@proxyAuthorizeToken');
            });
        });

        /**
         * Record categories
         */
        $router->group(['prefix' => '/record-categories'], static function() use ($router) {
            $router->get('/', 'Api\Identity\RecordCategoryController@index');
            $router->post('/', 'Api\Identity\RecordCategoryController@store');
            $router->patch('/sort', 'Api\Identity\RecordCategoryController@sort');
            $router->get('/{recordCategoryId}', 'Api\Identity\RecordCategoryController@show');
            $router->patch('/{recordCategoryId}', 'Api\Identity\RecordCategoryController@update');
            $router->delete('/{recordCategoryId}', 'Api\Identity\RecordCategoryController@destroy');
        });

        /**
         * Record
         */
        $router->group(['prefix' => '/records'], static function() use ($router) {
            $router->get('/', 'Api\Identity\RecordController@index');
            $router->post('/', 'Api\Identity\RecordController@store');
            $router->post('/validate', 'Api\Identity\RecordController@storeValidate');
            $router->get('/types', 'Api\Identity\RecordController@typeKeys');
            $router->patch('/sort', 'Api\Identity\RecordController@sort');
            $router->get('/{recordId}', 'Api\Identity\RecordController@show');
            $router->patch('/{recordId}', 'Api\Identity\RecordController@update');
            $router->patch('/{recordId}/validate', 'Api\Identity\RecordController@updateValidate');
            $router->delete('/{recordId}', 'Api\Identity\RecordController@destroy');
        });

        /**
         * Record validations
         */
        $router->group(['prefix' => '/record-validations'], static function() use ($router) {
            $router->post('/', 'Api\Identity\RecordValidationController@store');
            $router->get('/{recordUuid}', 'Api\Identity\RecordValidationController@show');
            $router->patch('/{recordUuid}/approve', 'Api\Identity\RecordValidationController@approve');
            $router->patch('/{recordUuid}/decline', 'Api\Identity\RecordValidationController@decline');
        });

        /**
         * Sessions validations
         */
        $router->resource('sessions', 'Api\Identity\SessionController', [
            'only' => [
                'index', 'show'
            ],
            'parameters' => [
                'sessions' => 'sessionUid'
            ]
        ]);

        $router->patch('sessions/{sessionUid}/terminate', 'Api\Identity\SessionController@terminate');
        $router->patch('sessions/terminate', 'Api\Identity\SessionController@terminateAll');

    });

    $router->resource('medias', 'Api\MediaController', [
        'only' => ['index', 'show', 'store', 'destroy'],
        'parameters' => [
            'medias' => 'media_uid'
        ]
    ]);

    if (config('file.enabled', false)) {
        $router->resource('files', 'Api\FileController', [
            'only' => ['index', 'show', 'store'],
            'parameters' => [
                'files' => 'file_uid'
            ]
        ]);

        $router->get('files/{file_uid}/download', 'Api\FileController@download');
        $router->post('files/validate', 'Api\FileController@storeValidate');
    }
});

Route::get('/status', 'Api\StatusController@getStatus')->name('status');
