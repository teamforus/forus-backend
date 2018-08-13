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
    $router->get(
        '/organization-types',
        "Api\Platform\OrganizationTypeController@index"
    );

    $router->get(
        '/product-categories',
        "Api\Platform\ProductCategoryController@index"
    );

    $router->resource(
        'organizations',
        "Api\Platform\OrganizationsController", [
        'only' => [
            'index', 'show', 'store', 'update'
        ]
    ]);

    $router->resource(
        'funds',
        "Api\Platform\FundsController", [
        'only' => [
            'index', 'show'
        ]
    ]);

    $router->resource(
        'organizations.funds',
        "Api\Platform\Organizations\FundsController", [
        'only' => [
            'index', 'show', 'store', 'update'
        ]
    ]);

    $router->resource(
        'organizations.providers',
        "Api\Platform\Organizations\FundProviderController", [
        'only' => [
            'index'
        ],
        'parameters' => [
            'providers' => 'organization_fund'
        ]
    ]);

    $router->resource(
        'organizations.funds.providers',
        "Api\Platform\Organizations\Funds\FundProviderController", [
        'only' => [
            'index', 'show', 'update'
        ],
        'parameters' => [
            'providers' => 'organization_fund'
        ]
    ]);

    $router->resource(
        'organizations.products',
        "Api\Platform\Organizations\ProductsController", [
        'only' => [
            'index', 'show', 'store', 'update', 'destroy'
        ]
    ]);

    $router->resource(
        'organizations.offices',
        "Api\Platform\Organizations\OfficesController", [
        'only' => [
            'index', 'show', 'store', 'update', 'destroy'
        ]
    ]);

    $router->resource(
        'organizations.validators',
        "Api\Platform\Organizations\ValidatorController", [
        'only' => [
            'index', 'show', 'store', 'update', 'destroy'
        ]
    ]);

    $router->get(
        'organizations/{organization}/provider/funds-available',
        'Api\Platform\Organizations\Provider\FundProviderController@availableFunds'
    );

    $router->resource(
        'organizations/{organization}/provider/funds',
        "Api\Platform\Organizations\Provider\FundProviderController", [
        'only' => [
            'index', 'show', 'store', 'update'
        ],
        'parameters' => [
            'funds' => 'organization_fund'
        ]
    ]);

    // Prevalidations endpoints
    $router->post(
        'prevalidations/{prevalidations_uid}/redeem',
        'Api\Platform\PrevalidationController@redeem');

    $router->resource(
        'prevalidations',
        'Api\Platform\PrevalidationController',[
            'only' => [
                'index', 'store', 'show'
            ],
            'parameters' => [
                'prevalidations' => 'prevalidation_uid'
            ]
        ]
    );

    $router->resource(
        'validators',
        "Api\Platform\ValidatorsController", [
        'only' => [
            'index'
        ]
    ]);

    $router->resource(
        'validator-requests',
        "Api\Platform\ValidatorRequestController", [
        'only' => [
            'index', 'show', 'store'
        ]
    ]);

    $router->resource(
        'validator/validator-requests',
        "Api\Platform\Validator\ValidatorRequestController", [
        'only' => [
            'index', 'show', 'update'
        ]
    ]);
});
