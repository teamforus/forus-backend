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

$router = app()->make('router');

/**
 * Authorization required
 */
$router->group(['middleware' => ['api.auth']], function() use ($router) {
    $router->get('/organization-types', "Api\Platform\OrganizationTypeController@index");
    $router->get('/product-categories', "Api\Platform\ProductCategoryController@index");

    $router->resource('/sponsors', "Api\Platform\SponsorController", [
        'only' => ['index', 'show', 'store', 'update']
    ]);
});
