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

$router->group([], function() use ($router) {
    $router->resource(
        'product-categories',
        "Api\Platform\ProductCategoryController", [
        'only' => [
            'index', 'show'
        ]
    ]);

    $router->resource(
        'business-types',
        "Api\Platform\BusinessTypeController", [
        'only' => [
            'index', 'show'
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
        'funds/{fund}/requests',
        "Api\Platform\Funds\FundRequestsController", [
        'only' => [
            'index', 'show', 'store'
        ],
        'parameters' => [
            'requests' => 'fund_request',
        ]
    ]);

    $router->post('funds/{fund}/requests/validate',"Api\Platform\Funds\FundRequestsController@storeValidate");

    $router->resource(
        'funds/{fund}/requests/{fund_request}/records',
        "Api\Platform\Funds\Requests\FundRequestRecordsController", [
        'only' => [
            'index', 'show'
        ],
        'parameters' => [
            'records' => 'fund_request_record',
        ]
    ]);

    $router->resource(
        'funds/{fund}/requests/{fund_request}/clarifications',
        "Api\Platform\Funds\Requests\FundRequestClarificationsController", [
        'only' => [
            'index', 'show', 'update'
        ],
        'parameters' => [
            'clarifications' => 'fund_request_clarification',
        ]
    ]);

    $router->resource(
        'offices',
        "Api\Platform\OfficesController", [
        'only' => [
            'index', 'show'
        ]
    ]);

    $router->get(
        'products/sample',
        "Api\Platform\ProductsController@sample"
    );

    $router->resource(
        'products',
        "Api\Platform\ProductsController", [
        'only' => [
            'index'
        ]
    ]);

    $router->get(
        'products/{product_with_trashed}',
        "Api\Platform\ProductsController@show"
    );

    $router->get(
        'config/{platform_config}',
        'Api\Platform\ConfigController@getConfig'
    );
});

// TODO TEMP added throttle 20 per minutes - must be secured
$router->group(['middleware' => ['throttle:20']], function() use ($router) {
    $router->post(
        '/sms/send',
        'Api\Platform\SmsController@send'
    );
});

/**
 * Public api routes
 */
$router->resource(
    'organizations.funds.bunq-transactions',
    "Api\Platform\Organizations\Funds\BunqMeTabsController", [
    'only' => [
        'index', 'show'
    ],
    'parameters' => [
        'bunq-transactions' => 'bunq_me_tab_paid'
    ]
]);

$router->resource(
    'organizations.funds.transactions',
    "Api\Platform\Organizations\Funds\TransactionsController", [
    'only' => [
        'index', 'show',
    ],
    'parameters' => [
        'transactions' => 'transaction_address',
    ]
]);

$router->resource(
    'organizations.funds.providers',
    "Api\Platform\Organizations\Funds\FundProviderController", [
    'only' => [
        'index', 'show'
    ],
    'parameters' => [
        'providers' => 'organization_fund'
    ]
]);

$router->resource(
    'organizations.funds',
    "Api\Platform\Organizations\FundsController", [
    'only' => [
        'index', 'show'
    ]
]);

$router->get(
    'funds/{configured_fund}/ideal/issuers',
    "Api\Platform\FundsController@idealIssuers"
);

$router->post(
    'funds/{fund}/ideal/requests',
    "Api\Platform\FundsController@idealMakeRequest"
);

/**
 * Authorization required
 */
$router->group(['middleware' => ['api.auth']], function() use ($router) {
    $router->resource(
        'organizations',
        "Api\Platform\OrganizationsController", [
        'only' => [
            'index', 'show', 'store', 'update'
        ]
    ]);

    $router->resource(
        'roles',
        "Api\Platform\RolesController", [
        'only' => [
            'index', 'show'
        ]
    ]);

    $router->post(
        'funds/{fund}/apply',
        "Api\Platform\FundsController@apply"
    );

    $router->resource(
        'vouchers',
        "Api\Platform\VouchersController", [
        'only' => [
            'index', 'show', 'store', 'destroy'
        ],
        'parameters' => [
            'vouchers' => 'voucher_token_address'
        ]
    ]);

    $router->get(
        'vouchers/{voucher_token_address}/provider',
        "Api\Platform\VouchersController@provider"
    );

    $router->post(
        'vouchers/{voucher_token_address}/send-email',
        "Api\Platform\VouchersController@sendEmail"
    );

    $router->post(
        'vouchers/{voucher_token_address}/share',
        "Api\Platform\VouchersController@shareVoucher"
    );

    $router->resource(
        'vouchers.transactions',
        "Api\Platform\Vouchers\TransactionsController", [
        'only' => [
            'index', 'show', 'store'
        ],
        'parameters' => [
            'vouchers' => 'voucher_token_address',
            'transactions' => 'transaction_address',
        ]
    ]);

    $router->get(
        'organizations/{organization}/funds/{fund}/finances',
        "Api\Platform\Organizations\FundsController@finances");

    $router->post(
        'organizations/{organization}/funds/{fund}/top-up',
        "Api\Platform\Organizations\FundsController@topUp");

    $router->resource(
        'organizations.funds',
        "Api\Platform\Organizations\FundsController", [
        'only' => [
            'store', 'update', 'destroy'
        ]
    ]);

    $router->resource(
        'organizations/{organization}/funds/{fund}/requests',
        "Api\Platform\Organizations\Funds\FundRequestsController", [
        'only' => [
            'index', 'show', 'update'
        ],
        'parameters' => [
            'requests' => 'fund_request',
        ]
    ]);

    $router->resource(
        'organizations/{organization}/funds/{fund}/requests/{fund_request}/records',
        "Api\Platform\Organizations\Funds\Requests\FundRequestRecordsController", [
        'only' => [
            'index', 'show', 'update'
        ],
        'parameters' => [
            'records' => 'fund_request_record',
        ]
    ]);

    $router->resource(
        'organizations/{organization}/funds/{fund}/requests/{fund_request}/clarifications',
        "Api\Platform\Organizations\Funds\Requests\FundRequestClarificationsController", [
        'only' => [
            'index', 'show', 'store'
        ],
        'parameters' => [
            'clarifications' => 'fund_request_clarification',
        ]
    ]);

    $router->resource(
        'organizations/{organization}/requests',
        "Api\Platform\Organizations\FundRequestsController", [
        'only' => [
            'index', 'show'
        ],
        'parameters' => [
            'requests' => 'fund_request',
        ]
    ]);

    $router->get(
        'organizations/{organization}/providers/export',
        "Api\Platform\Organizations\FundProviderController@export"
    );

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

    $router->get(
        'organizations/{organization}/funds/{fund}/providers/{organization_fund}/finances',
        "Api\Platform\Organizations\Funds\FundProviderController@finances");

    $router->get(
        'organizations/{organization}/funds/{fund}/providers/{organization_fund}/transactions',
        "Api\Platform\Organizations\Funds\FundProviderController@transactions");

    $router->get(
        'organizations/{organization}/funds/{fund}/providers/{organization_fund}/transactions/export',
        "Api\Platform\Organizations\Funds\FundProviderController@transactionsExport");

    $router->get(
        'organizations/{organization}/funds/{fund}/providers/{organization_fund}/transactions/{transaction_address}',
        "Api\Platform\Organizations\Funds\FundProviderController@transaction");

    $router->resource(
        'organizations.funds.providers',
        "Api\Platform\Organizations\Funds\FundProviderController", [
        'only' => [
            'update'
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
        "Api\Platform\Organizations\ValidatorsController", [
        'only' => [
            'index', 'show', /*'store', 'update', 'destroy'*/
        ]
    ]);

    $router->resource(
        'organizations.employees',
        "Api\Platform\Organizations\EmployeesController", [
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

    $router->resource(
        'organizations/{organization}/provider/identities',
        "Api\Platform\Organizations\Provider\ProviderIdentitiesController", [
        'only' => [
            'index', 'show', 'store', 'destroy', 'update'
        ],
        'parameters' => [
            'identities' => 'provider_identity'
        ]
    ]);


    $router->get(
        'organizations/{organization}/provider/transactions/export',
        "Api\Platform\Organizations\Provider\TransactionsController@export"
    );

    $router->resource(
        'organizations/{organization}/provider/transactions',
        "Api\Platform\Organizations\Provider\TransactionsController", [
            'parameters' => [
                'transactions' => 'transaction_address',
            ]
        ]
    );

    $router->get(
        'organizations/{organization}/sponsor/transactions/export',
        "Api\Platform\Organizations\Sponsor\TransactionsController@export"
    );

    $router->resource(
        'organizations/{organization}/sponsor/transactions',
        "Api\Platform\Organizations\Sponsor\TransactionsController", [
            'only' => [
                'index', 'show'
            ],
            'parameters' => [
                'transactions' => 'transaction_address',
            ]
        ]
    );

    $router->post(
        'organizations/{organization}/sponsor/vouchers/{voucher_id}/send',
        "Api\Platform\Organizations\Sponsor\VouchersController@sendByEmail"
    );

    $router->get(
        'organizations/{organization}/sponsor/vouchers/export-unassigned',
        "Api\Platform\Organizations\Sponsor\VouchersController@exportUnassigned"
    );

    $router->patch(
        'organizations/{organization}/sponsor/vouchers/{voucher_id}/assign',
        "Api\Platform\Organizations\Sponsor\VouchersController@assign"
    );

    $router->resource(
        'organizations/{organization}/sponsor/vouchers',
        "Api\Platform\Organizations\Sponsor\VouchersController", [
            'only' => [
                'index', 'show', 'store'
            ],
            'parameters' => [
                'vouchers' => 'voucher_id',
            ]
        ]
    );

    // Prevalidations endpoints
    $router->post(
        'prevalidations/{prevalidation_uid}/redeem',
        'Api\Platform\PrevalidationController@redeem'
    );
    $router->get(
        'prevalidations/export',
        'Api\Platform\PrevalidationController@export'
    );

    $router->get(
        'prevalidations/{prevalidation_uid}/fund',
        'Api\Platform\PrevalidationController@showFundId'
    );
    $router->resource(
        'prevalidations',
        'Api\Platform\PrevalidationController', [
            'only' => [
                'index', 'show', 'store'
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
        'employees',
        "Api\Platform\EmployeesController", [
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

    $router->post(
        '/devices/register-push',
        'Api\Platform\DevicesController@registerPush'
    );

    $router->delete(
        '/devices/delete-push',
        'Api\Platform\DevicesController@deletePush'
    );

    $router->get('notifications', 'Api\Platform\NotificationsController@index');
    $router->patch('notifications', 'Api\Platform\NotificationsController@update');
});
