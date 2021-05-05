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
        'organizations',
        "Api\Platform\OrganizationsController", [
        'only' => [
            'index',
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
        'organizations.external-funds',
        "Api\Platform\Organizations\ExternalFundsController", [
        'only' => [
            'index', 'update'
        ],
        'parameters' => [
            'external-funds' => 'fund'
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

$router->post('/share/sms', 'Api\Platform\ShareController@sendSms');
$router->post('/share/email', 'Api\Platform\ShareController@sendEmail');

/**
 * Authorization required
 */
$router->group(['middleware' => [
    'api.auth',
]], static function() use ($router) {
    $router->patch(
        'organizations/{organization}/update-business',
        "Api\Platform\OrganizationsController@updateBusinessType"
    );

    $router->patch(
        'organizations/{organization}/roles',
        "Api\Platform\OrganizationsController@updateRoles"
    );

    $router->resource(
        'organizations',
        "Api\Platform\OrganizationsController", [
        'only' => [
            'show', 'store', 'update'
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

    $router->group(['prefix' => '/provider'], static function() use ($router) {
        $router->resource(
            'vouchers',
            "Api\Platform\Provider\VouchersController", [
            'only' => [
                'show'
            ],
            'parameters' => [
                'vouchers' => 'voucher_address_or_physical_code',
            ]
        ]);

        $router->resource(
            'vouchers.product-vouchers',
            "Api\Platform\Provider\Vouchers\ProductVouchersController", [
            'only' => [
                'index'
            ],
            'parameters' => [
                'vouchers' => 'voucher_address_or_physical_code',
                'product-vouchers' => 'product_voucher_token_address',
            ]
        ]);

        $router->resource(
            'vouchers.products',
            "Api\Platform\Provider\Vouchers\ProductsController", [
            'only' => [
                'index', 'show'
            ],
            'parameters' => [
                'vouchers' => 'voucher_address_or_physical_code',
                'products' => 'products',
            ]
        ]);

        $router->resource(
            'vouchers.transactions',
            "Api\Platform\Vouchers\TransactionsController", [
            'only' => [
                'store'
            ],
            'parameters' => [
                'vouchers' => 'voucher_address_or_physical_code',
                'transactions' => 'transaction_address',
            ]
        ]);

        $router->resource(
            'transactions',
            "Api\Platform\Provider\TransactionsController", [
            'only' => [
                'index'
            ],
            'parameters' => [
                'transactions' => 'transaction_address',
            ]
        ]);
    });

    $router->post('funds/redeem', "Api\Platform\FundsController@redeem");

    $router->resource(
        'vouchers/{voucher_token_address}/physical-cards',
        "Api\Platform\Vouchers\PhysicalCardsController", [
        'only' => [
            'store', 'destroy'
        ],
        'params' => [
            'physical-cards' => 'physical_card',
        ]
    ]);

    $router->resource(
        'sponsor/{organization_id}/vouchers/{voucher}/physical-cards',
        "Api\Platform\Organizations\Sponsor\Vouchers\PhysicalCardsController", [
        'only' => [
            'store', 'destroy',
        ],
        'params' => [
            'physical-cards' => 'physical_card',
        ]
    ]);

    $router->resource(
        'vouchers/{voucher_token_address}/physical-card-requests',
        "Api\Platform\Vouchers\PhysicalCardRequestsController", [
        'only' => [
            'index', 'store', 'show'
        ],
        'params' => [
            'physical-cards' => 'physical_card',
        ]
    ]);

    $router->post('vouchers/{voucher_token_address}/send-email', "Api\Platform\VouchersController@sendEmail");
    $router->post('vouchers/{voucher_token_address}/share', "Api\Platform\VouchersController@shareVoucher");

    // todo: deprecated, moved store endpoint to separate route provider/vouchers.transactions
    if (!env('DISABLE_FALLBACK_TRANSACTIONS', false)) {
        $router->resource(
            'vouchers.transactions',
            "Api\Platform\Vouchers\TransactionsController", [
            'only' => [
                'index', 'show', 'store'
            ],
            'parameters' => [
                'vouchers' => 'voucher_address_or_physical_code',
                'transactions' => 'transaction_address',
            ]
        ]);
    }

    $router->resource(
        'demo/transactions',
        "Api\Platform\Vouchers\DemoTransactionController", [
        'only' => [
            'store', 'show', 'update',
        ],
        'parameters' => [
            'transactions' => 'demo_token',
        ]
    ]);

    $router->patch(
        'organizations/{organization}/implementations/{implementation}/cms',
        "Api\Platform\Organizations\ImplementationsController@updateCms");

    $router->patch(
        'organizations/{organization}/implementations/{implementation}/email',
        "Api\Platform\Organizations\ImplementationsController@updateEmail");

    $router->patch(
        'organizations/{organization}/implementations/{implementation}/digid',
        "Api\Platform\Organizations\ImplementationsController@updateDigiD");

    $router->resource(
        'organizations/{organization}/implementations',
        "Api\Platform\Organizations\ImplementationsController", [
        'only' => [
            'index', 'show',
        ],
    ]);

    $router->resource(
        'organizations/{organization}/provider-invitations',
        'Api\Platform\Organizations\FundProviderInvitationsController', [
        'only' => [
            'index', 'show', 'update',
        ],
        'parameters' => [
            'provider-invitations' => 'fund_provider_invitations'
        ]
    ]);

    $router->post(
        'organizations/{organization}/funds/criteria/validate',
        "Api\Platform\Organizations\FundsController@storeCriteriaValidate");

    $router->get(
        'organizations/{organization}/funds/{fund}/finances',
        "Api\Platform\Organizations\FundsController@finances");

    $router->post(
        'organizations/{organization}/funds/{fund}/top-up',
        "Api\Platform\Organizations\FundsController@topUp");

    $router->patch(
        'organizations/{organization}/funds/{fund}/criteria/validate',
        "Api\Platform\Organizations\FundsController@updateCriteriaValidate");

    $router->patch(
        'organizations/{organization}/funds/{fund}/criteria',
        "Api\Platform\Organizations\FundsController@updateCriteria");


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
        $router->patch(
            'organizations/{organization}/fund-requests/{fund_request}/assign',
            "Api\Platform\Organizations\FundRequestsController@assign"
        );

        $router->patch(
            'organizations/{organization}/fund-requests/{fund_request}/resign',
            "Api\Platform\Organizations\FundRequestsController@resign"
        );

        $router->patch(
            'organizations/{organization}/fund-requests/{fund_request}/approve',
            "Api\Platform\Organizations\FundRequestsController@approve"
        );

        $router->patch(
            'organizations/{organization}/fund-requests/{fund_request}/decline',
            "Api\Platform\Organizations\FundRequestsController@decline"
        );

        $router->patch(
            'organizations/{organization}/fund-requests/{fund_request}/records/{fund_request_record}/approve',
            "Api\Platform\Organizations\FundRequests\FundRequestRecordsController@approve"
        );

        $router->patch(
            'organizations/{organization}/fund-requests/{fund_request}/records/{fund_request_record}/decline',
            "Api\Platform\Organizations\FundRequests\FundRequestRecordsController@decline"
        );

        $router->resource(
            'organizations/{organization}/fund-requests/{fund_request}/records',
            "Api\Platform\Organizations\FundRequests\FundRequestRecordsController", [
            'only' => [
                'index', 'store', 'show',
            ],
            'parameters' => [
                'records' => 'fund_request_record',
            ]
        ]);

        $router->resource(
            'organizations/{organization}/fund-requests/{fund_request}/clarifications',
            "Api\Platform\Organizations\FundRequests\FundRequestClarificationsController", [
            'only' => [
                'index', 'show', 'store'
            ],
            'parameters' => [
                'clarifications' => 'fund_request_clarification',
            ]
        ]);

        $router->get(
            'organizations/{organization}/fund-requests/export',
            "Api\Platform\Organizations\FundRequestsController@export"
        );

        $router->resource(
            'organizations/{organization}/fund-requests',
            "Api\Platform\Organizations\FundRequestsController", [
            'only' => [
                'index', 'show',
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
        'organizations.funds.providers.products',
        "Api\Platform\Organizations\Funds\FundProviders\ProductsController", [
        'only' => [
            'index', 'show'
        ],
        'parameters' => [
            'providers' => 'fund_provider',
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

    $router->patch(
        'organizations/{organization}/products/{product}/exclusions',
        "Api\Platform\Organizations\ProductsController@updateExclusions"
    );

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
        'organizations.validators',
        "Api\Platform\Organizations\ValidatorOrganizationsController", [
        'only' => [
            'index', 'show', 'store', 'destroy'
        ],
        'parameters' => [
            'validators' => 'validator_organization'
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
        'organizations/{organization}/sponsor/vouchers/{voucher}/send',
        "Api\Platform\Organizations\Sponsor\VouchersController@sendByEmail"
    );

    $router->get(
        'organizations/{organization}/sponsor/vouchers/export',
        "Api\Platform\Organizations\Sponsor\VouchersController@export"
    );

    $router->get(
        'organizations/{organization}/sponsor/vouchers/export-xls',
        "Api\Platform\Organizations\Sponsor\VouchersController@exportXls"
    );

    $router->get(
        'organizations/{organization}/sponsor/vouchers/export-data',
        "Api\Platform\Organizations\Sponsor\VouchersController@exportData"
    );

    $router->patch(
        'organizations/{organization}/sponsor/vouchers/{voucher}/assign',
        "Api\Platform\Organizations\Sponsor\VouchersController@assign"
    );

    $router->patch(
        'organizations/{organization}/sponsor/vouchers/{voucher}/activate',
        "Api\Platform\Organizations\Sponsor\VouchersController@activate"
    );

    $router->patch(
        'organizations/{organization}/sponsor/vouchers/{voucher}/deactivate',
        "Api\Platform\Organizations\Sponsor\VouchersController@deactivate"
    );

    $router->patch(
        'organizations/{organization}/sponsor/vouchers/{voucher}/activation-code',
        "Api\Platform\Organizations\Sponsor\VouchersController@makeActivationCode"
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

    $router->get('organizations/{organization}/sponsor/providers/export', "Api\Platform\Organizations\Sponsor\ProvidersController@export");

    $router->resource(
        'organizations/{organization}/sponsor/providers',
        "Api\Platform\Organizations\Sponsor\ProvidersController", [
            'only' => [
                'index', 'show',
            ],
            'parameters' => [
                'providers' => 'organization_id',
            ]
        ]
    );

    $router->resource(
        'organizations/{organization}/sponsor/providers.products',
        "Api\Platform\Organizations\Sponsor\Providers\ProductsController", [
            'only' => [
                'index', 'show', 'store', 'update', 'destroy',
            ],
            'parameters' => [
                'providers' => 'organization_id',
            ]
        ]
    );

    $router->get(
        'prevalidations/export',
        'Api\Platform\PrevalidationController@export'
    );

    $router->post(
        'prevalidations/collection',
        'Api\Platform\PrevalidationController@storeCollection'
    );

    $router->post(
        'prevalidations/collection/hash',
        'Api\Platform\PrevalidationController@collectionHash'
    );

    $router->resource(
        'prevalidations',
        'Api\Platform\PrevalidationController', [
            'only' => [
                'index', 'store', 'destroy',
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
