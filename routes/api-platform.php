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

/** @var \Illuminate\Routing\Router $router */
$router = resolve('router');

/**
 * Public api routes
 */
$router->group([
    'middleware' => []
], static function() use ($router) {
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

    if (config('forus.features.webshop.funds.fund_requests', FALSE)) {
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

        $router->post(
            'funds/{fund}/requests/validate',
            "Api\Platform\Funds\FundRequestsController@storeValidate"
        );

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
    }

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

    $router->resource(
        'providers',
        "Api\Platform\ProvidersController", [
        'only' => [
            'index', 'show'
        ],
        'parameters' => [
            'providers' => 'organization'
        ]
    ]);

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

    $router->post('/digid', 'DigIdController@start');
    $router->get('/digid/{digid_session_uid}/redirect', 'DigIdController@redirect')->name('digidRedirect');
    $router->get('/digid/{digid_session_uid}/resolve', 'DigIdController@resolve')->name('digidResolve');

    $router->resource(
        'provider-invitations',
        "Api\Platform\FundProviderInvitationsController", [
        'only' => [
            'show', 'update'
        ],
        'parameters' => [
            'provider-invitations' => 'fund_provider_invitation_token'
        ]
    ]);
});

$router->group(['middleware' => [
    'throttle:3'
]], function() use ($router) {
    $router->post(
        '/sms/send',
        'Api\Platform\SmsController@send'
    );
});

/**
 * Authorization required
 */
$router->group(['middleware' => [
    'api.auth',
]], function() use ($router) {
    $router->patch(
        'organizations/{organization}/update-business',
        "Api\Platform\OrganizationsController@updateBusinessType"
    );

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

    // TODO: deprecated, remove in next releases
    if (!env('DISABLE_DEPRECATED_API', FALSE)) {
        $router->get(
            'vouchers/{voucher_token_address}/provider',
            "Api\Platform\Provider\VouchersController@show"
        );
    }

    $router->group(['prefix' => '/provider'], function() use ($router) {
        $router->resource(
            'vouchers',
            "Api\Platform\Provider\VouchersController", [
            'only' => [
                'show'
            ],
            'parameters' => [
                'vouchers' => 'voucher_token_address',
            ]
        ]);

        $router->resource(
            'vouchers.product-vouchers',
            "Api\Platform\Provider\Vouchers\ProductVouchersController", [
            'only' => [
                'index'
            ],
            'parameters' => [
                'vouchers' => 'budget_voucher_token_address',
                'product-vouchers' => 'product_voucher_token_address',
            ]
        ]);
    });

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

    $router->resource(
        'demo/transactions',
        "Api\Platform\Vouchers\DemoTransactionController", [
        'only' => [
            'store', 'show', 'update'
        ],
        'parameters' => [
            'transactions' => 'demo_token',
        ]
    ]);

    $router->resource(
        'organizations/{organization}/provider-invitations',
        'Api\Platform\Organizations\FundProviderInvitationsController', [
        'only' => [
            'index', 'show', 'update'
        ],
        'parameters' => [
            'provider-invitations' => 'fund_provider_invitations'
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
        'organizations.funds.provider-invitations',
        "Api\Platform\Organizations\Funds\FundProviderInvitationsController", [
        'only' => [
            'index', 'show', 'store'
        ],
        'parameters' => [
            'provider-invitations' => 'fund_provider_invitations'
        ]
    ]);

    if (config('forus.features.dashboard.organizations.funds.fund_requests', FALSE)) {
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

        $router->get(
            'organizations/{organization}/requests/export',
            "Api\Platform\Organizations\FundRequestsController@export"
        );

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
    }

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
            'providers' => 'fund_provider'
        ]
    ]);

    $router->resource(
        'organizations.funds.providers.chats',
        "Api\Platform\Organizations\Funds\FundProviders\FundProviderChatsController", [
        'only' => [
            'index', 'show', 'store'
        ],
        'parameters' => [
            'providers' => 'fund_provider',
            'chats' => 'fund_provider_chats',
        ]
    ]);

    $router->resource(
        'organizations.funds.providers.chats.messages',
        "Api\Platform\Organizations\Funds\FundProviders\FundProviderChats\FundProviderChatMessagesController", [
        'only' => [
            'index', 'show', 'store'
        ],
        'parameters' => [
            'providers' => 'fund_provider',
            'chats' => 'fund_provider_chats',
            'messages' => 'fund_provider_chat_messages'
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
        'organizations.products.funds',
        "Api\Platform\Organizations\Products\FundsController", [
        'only' => [
            'index', 'show', 'store', 'update', 'destroy'
        ]
    ]);

    $router->resource(
        'organizations.products.chats',
        "Api\Platform\Organizations\Products\FundProviderChatsController", [
        'only' => [
            'index', 'show'
        ],
        'parameters' => [
            'chats' => 'fund_provider_chats',
        ]
    ]);

    $router->resource(
        'organizations.products.chats.messages',
        "Api\Platform\Organizations\Products\FundProviderChats\FundProviderChatMessagesController", [
        'only' => [
            'index', 'show', 'store'
        ],
        'parameters' => [
            'chats' => 'fund_provider_chats',
            'messages' => 'fund_provider_chat_messages'
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
        'organizations/{organization}/sponsor/vouchers/validate',
        "Api\Platform\Organizations\Sponsor\VouchersController@storeValidate"
    );

    $router->post(
        'organizations/{organization}/sponsor/vouchers/batch',
        "Api\Platform\Organizations\Sponsor\VouchersController@storeBatch"
    );

    $router->post(
        'organizations/{organization}/sponsor/vouchers/batch/validate',
        "Api\Platform\Organizations\Sponsor\VouchersController@storeBatchValidate"
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

    $router->post(
        'prevalidations/collection',
        'Api\Platform\PrevalidationController@storeCollection'
    );

    $router->resource(
        'prevalidations',
        'Api\Platform\PrevalidationController', [
            'only' => [
                'index', 'store'
            ],
            'parameters' => [
                'prevalidations' => 'prevalidation_uid'
            ]
        ]
    );

    $router->resource(
        'employees',
        "Api\Platform\EmployeesController", [
        'only' => [
            'index'
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

    $router->resource(
        'notifications',
        "Api\Platform\NotificationsController", [
        'only' => [
            'index'
        ]
    ]);

    $router->get('notifications/settings', 'Api\Platform\Notifications\NotificationsSettingsController@index');
    $router->patch('notifications/settings', 'Api\Platform\Notifications\NotificationsSettingsController@update');
});