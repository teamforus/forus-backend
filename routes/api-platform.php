<?php
use Illuminate\Routing\Router;

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

/** @var Router $router */
$router = resolve('router');

/**
 * Public api routes
 */
$router->group([], static function() use ($router) {
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

    $router->resource(
        'search',
        "Api\Platform\SearchController", [
        'only' => [
            'index',
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

    $router->post(
        'organizations/{organization}/funds/{fund}/archive',
        "Api\Platform\Organizations\FundsController@archive");

    $router->post(
        'organizations/{organization}/funds/{fund}/unarchive',
        "Api\Platform\Organizations\FundsController@unarchive");

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

    $router->middleware('domain.digid')->group(function(Router $router) {
        $router->post('/digid', 'DigIdController@start')->name('digidStart');
        $router->get('/digid/{digid_session_uid}/redirect', 'DigIdController@redirect')->name('digidRedirect');
        $router->get('/digid/{digid_session_uid}/resolve', 'DigIdController@resolve')->name('digidResolve');
    });

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

    $router->get('/bank-connections/redirect', "Api\Platform\BankConnectionsController@redirect")->name('bankOauthRedirect');
});

$router->post('/share/sms', 'Api\Platform\ShareController@sendSms');
$router->post('/share/email', 'Api\Platform\ShareController@sendEmail');

/**
 * Authorization required
 */
$router->group(['middleware' => 'api.auth'], static function() use ($router) {
    $router->patch(
        'organizations/{organization}/update-business',
        "Api\Platform\OrganizationsController@updateBusinessType"
    );

    $router->patch(
        'organizations/{organization}/roles',
        "Api\Platform\OrganizationsController@updateRoles"
    );

    $router->patch(
        'organizations/{organization}/accept-reservations',
        "Api\Platform\OrganizationsController@updateAcceptReservations"
    );

    $router->resource('organizations', "Api\Platform\OrganizationsController", [
        'only' => [
            'show', 'store', 'update'
        ]
    ]);

    $router->resource('roles', "Api\Platform\RolesController", [
        'only' => [
            'index', 'show',
        ]
    ]);

    $router->post(
        'funds/{fund}/apply',
        "Api\Platform\FundsController@apply"
    );

    $router->resource('vouchers', "Api\Platform\VouchersController", [
        'only' => [
            'index', 'show', 'destroy',
        ],
        'parameters' => [
            'vouchers' => 'voucher_token_address'
        ]
    ]);

    $router->resource('product-reservations', "Api\Platform\ProductReservationsController", [
        'only' => [
            'index', 'store', 'show', 'update', 'destroy'
        ]
    ]);

    $router->post('product-reservations/validate', "Api\Platform\ProductReservationsController@storeValidate");

    $router->group(['prefix' => '/provider'], static function() use ($router) {
        $router->resource('vouchers', "Api\Platform\Provider\VouchersController", [
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

    $router->post(
        'vouchers/{voucher_token_address}/physical-card-requests/validate',
        "Api\Platform\Vouchers\PhysicalCardRequestsController@storeValidate"
    );

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

    $router->post(
        'organizations/{organization}/sponsor/vouchers/{voucher_token_address}/physical-card-requests/validate',
        "Api\Platform\Organizations\Sponsor\Vouchers\PhysicalCardRequestsController@storeValidate"
    );

    $router->resource(
        'organizations/{organization}/sponsor/vouchers/{voucher_token_address}/physical-card-requests',
        "Api\Platform\Organizations\Sponsor\Vouchers\PhysicalCardRequestsController"
    )->parameters([
        'physical-cards' => 'physical_card',
    ])->only('index', 'store');

    $router->post('vouchers/{voucher_token_address}/send-email', "Api\Platform\VouchersController@sendEmail");
    $router->post('vouchers/{voucher_token_address}/share', "Api\Platform\VouchersController@shareVoucher");
    $router->post('vouchers/{voucher_token_address}/deactivate', "Api\Platform\VouchersController@deactivate");

    // todo: deprecated, moved store endpoint to separate route provider/vouchers.transactions
    if (!env('DISABLE_FALLBACK_TRANSACTIONS', false)) {
        $router->resource(
            'vouchers.transactions',
            "Api\Platform\Vouchers\TransactionsController"
        )->parameters([
            'transactions' => 'transaction_address',
            'vouchers' => 'voucher_address_or_physical_code',
        ])->only('index', 'show', 'store');
    }

    $router->resource(
        'demo/transactions',
        "Api\Platform\Vouchers\DemoTransactionController", [
    ])->parameters([
        'transactions' => 'demo_token',
    ])->only('store', 'show', 'update');

    $router->patch(
        'organizations/{organization}/implementations/{implementation}/cms',
        "Api\Platform\Organizations\ImplementationsController@updateCms");

    $router->patch(
        'organizations/{organization}/implementations/{implementation}/email',
        "Api\Platform\Organizations\ImplementationsController@updateEmail");

    $router->patch(
        'organizations/{organization}/implementations/{implementation}/email-branding',
        "Api\Platform\Organizations\ImplementationsController@updateEmailBranding");

    $router->patch(
        'organizations/{organization}/implementations/{implementation}/digid',
        "Api\Platform\Organizations\ImplementationsController@updateDigiD");

    $router->resource(
        'organizations/{organization}/implementations',
        "Api\Platform\Organizations\ImplementationsController"
    )->only('index', 'show');

    $router->resource(
        'organizations/{organization}/implementations/{implementation}/system-notifications',
        "Api\Platform\Organizations\Implementations\SystemNotificationsController"
    )->only('index', 'show', 'update');

    $router->resource(
        'organizations/{organization}/provider-invitations',
        'Api\Platform\Organizations\FundProviderInvitationsController'
    )->parameters([
        'provider-invitations' => 'fund_provider_invitations',
    ])->only('index', 'show', 'update');

    $router->post(
        'organizations/{organization}/funds/criteria/validate',
        "Api\Platform\Organizations\FundsController@storeCriteriaValidate");

    $router->post(
        'organizations/{organization}/funds/{fund}/top-up',
        "Api\Platform\Organizations\FundsController@topUp");

    $router->patch(
        'organizations/{organization}/funds/{fund}/criteria/validate',
        "Api\Platform\Organizations\FundsController@updateCriteriaValidate");

    $router->patch(
        'organizations/{organization}/funds/{fund}/criteria',
        "Api\Platform\Organizations\FundsController@updateCriteria");

    $router->patch(
        'organizations/{organization}/funds/{fund}/backoffice',
        "Api\Platform\Organizations\FundsController@updateBackoffice");

    $router->post(
        'organizations/{organization}/funds/{fund}/backoffice-test',
        "Api\Platform\Organizations\FundsController@testBackofficeConnection");

    $router->patch(
        'organizations/{organization}/transfer-ownership',
        "Api\Platform\OrganizationsController@transferOwnership");

    $router->resource(
        'organizations.funds',
        "Api\Platform\Organizations\FundsController"
    )->only('store', 'update', 'destroy');

    $router->resource(
        'organizations.funds.provider-invitations',
        "Api\Platform\Organizations\Funds\FundProviderInvitationsController", [
    ])->parameters([
        'provider-invitations' => 'fund_provider_invitations'
    ])->only('index', 'show', 'store');

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
        "Api\Platform\Organizations\FundProviderController"
    )->parameters([
        'providers' => 'organization_fund'
    ])->only('index');

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
        "Api\Platform\Organizations\Funds\FundProviderController"
    )->parameters([
        'providers' => 'fund_provider'
    ])->only('update');

    $router->resource(
        'organizations.funds.providers.chats',
        "Api\Platform\Organizations\Funds\FundProviders\FundProviderChatsController"
    )->parameters([
        'providers' => 'fund_provider',
        'chats' => 'fund_provider_chats',
    ])->only('index', 'show', 'store');

    $router->resource(
        'organizations.funds.providers.products',
        "Api\Platform\Organizations\Funds\FundProviders\ProductsController"
    )->parameters([
        'providers' => 'fund_provider',
    ])->only('index', 'show');

    $router->resource(
        'organizations.funds.providers.chats.messages',
        "Api\Platform\Organizations\Funds\FundProviders\FundProviderChats\FundProviderChatMessagesController"
    )->parameters([
        'providers' => 'fund_provider',
        'chats' => 'fund_provider_chats',
        'messages' => 'fund_provider_chat_messages'
    ])->only('index', 'show', 'store');

    $router->patch(
        'organizations/{organization}/products/{product}/exclusions',
        "Api\Platform\Organizations\ProductsController@updateExclusions"
    );

    $router->resource(
        'organizations.products',
        "Api\Platform\Organizations\ProductsController"
    )->only('index', 'show', 'store', 'update', 'destroy');

    $router->post(
        'organizations/{organization}/product-reservations/batch',
        "Api\Platform\Organizations\ProductReservationsController@storeBatch"
    );

    $router->post(
        'organizations/{organization}/product-reservations/{product_reservation}/accept',
        "Api\Platform\Organizations\ProductReservationsController@accept"
    );

    $router->post(
        'organizations/{organization}/product-reservations/{product_reservation}/reject',
        "Api\Platform\Organizations\ProductReservationsController@reject"
    );

    $router->resource(
        'organizations.product-reservations',
        "Api\Platform\Organizations\ProductReservationsController"
    )->only('index', 'store', 'show');

    $router->resource(
        'organizations.products.funds',
        "Api\Platform\Organizations\Products\FundsController"
    )->only('index', 'show', 'store', 'update', 'destroy');

    $router->resource(
        'organizations.products.chats',
        "Api\Platform\Organizations\Products\FundProviderChatsController"
    )->parameters([
        'chats' => 'fund_provider_chats',
    ])->only('index', 'show');

    $router->resource(
        'organizations.products.chats.messages',
        "Api\Platform\Organizations\Products\FundProviderChats\FundProviderChatMessagesController"
    )->parameters([
        'chats' => 'fund_provider_chats',
        'messages' => 'fund_provider_chat_messages',
    ])->only('index', 'show', 'store');

    $router->resource(
        'organizations.offices',
        "Api\Platform\Organizations\OfficesController"
    )->only('index', 'show', 'store', 'update', 'destroy');

    $router->resource('organizations.bank-connections', "Api\Platform\Organizations\BankConnectionsController")->only([
        'index', 'show', 'store', 'update',
    ])->parameter('bank-connections', 'bankConnection');

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
            'index', 'show', 'store', 'update', 'destroy'
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
        'organizations/{organization}/sponsor/finances',
        "Api\Platform\Organizations\FundsController@finances");

    $router->get(
        'organizations/{organization}/sponsor/finances-overview',
        "Api\Platform\Organizations\FundsController@financesOverview");

    $router->get(
        'organizations/{organization}/sponsor/finances-overview-export',
        "Api\Platform\Organizations\FundsController@financesOverviewExport"
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

    $router->resource(
        'organizations/{organization}/sponsor/transaction-bulks',
        "Api\Platform\Organizations\Sponsor\TransactionBulksController"
    )->parameters([
        'transaction-bulks' => 'voucher-transaction-bulks',
    ])->only('index', 'show', 'store', 'update');

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

    $router->get('organizations/{organization}/sponsor/providers/finances',"Api\Platform\Organizations\Sponsor\ProvidersController@finances");
    $router->get('organizations/{organization}/sponsor/providers/finances-export',"Api\Platform\Organizations\Sponsor\ProvidersController@exportFinances");

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

    $router->resource('banks', "Api\Platform\BanksController")->only([
        'index', 'show',
    ]);
});