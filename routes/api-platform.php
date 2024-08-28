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
    $router->resource('tags', "Api\Platform\TagsController")->only('index', 'show');
    $router->resource('funds', "Api\Platform\FundsController")->only('index', 'show');
    $router->resource('search', "Api\Platform\SearchController")->only('index');
    $router->resource('organizations', "Api\Platform\OrganizationsController")->only('index');
    $router->resource('business-types', "Api\Platform\BusinessTypeController")->only('index', 'show');
    $router->resource('product-categories', "Api\Platform\ProductCategoriesController")->only('index', 'show');

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
    }

    $router->resource(
        'offices',
        "Api\Platform\OfficesController", [
        'only' => [
            'index', 'show'
        ]
    ]);

    $router->post('pre-checks/calculate', 'Api\Platform\PreCheckController@calculateTotals');
    $router->post('pre-checks/download-pdf', 'Api\Platform\PreCheckController@downloadPDF');

    $router->resource('pre-checks', "Api\Platform\PreCheckController")
        ->only('index');

    $router->get('products/sample', "Api\Platform\ProductsController@sample");
    $router->post('products/{product}/bookmark', "Api\Platform\ProductsController@bookmark");
    $router->post('products/{product}/remove-bookmark', "Api\Platform\ProductsController@removeBookmark");

    $router->resource('products', "Api\Platform\ProductsController")
        ->only('index');

    $router->resource('products', "Api\Platform\ProductsController")
        ->parameter('product', 'product_with_trashed')
        ->only('show');

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
        "Api\Platform\Organizations\FundsController@unArchive");

    $router->resource(
        'organizations.funds',
        "Api\Platform\Organizations\FundsController", [
        'only' => [
            'index', 'show'
        ]
    ]);

    $router->resource(
        'organizations.reimbursement-categories',
        "Api\Platform\Organizations\ReimbursementCategoriesController", [
        'only' => [
            'index', 'show', 'update', 'store', 'destroy'
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

    $router->resource('provider-invitations', "Api\Platform\FundProviderInvitationsController")
        ->parameter('provider-invitations', 'fund_provider_invitation_token')
        ->only('show', 'update');

    $router->get('/bank-connections/redirect', "Api\Platform\BankConnectionsController@redirect")->name('bankOauthRedirect');
});

$router->post('/share/sms', 'Api\Platform\ShareController@sendSms');
$router->post('/share/email', 'Api\Platform\ShareController@sendEmail');

/**
 * Authorization required
 */
$router->group(['middleware' => 'api.auth'], static function() use ($router) {
    // Organizations
    $router->group(['prefix' => 'organizations/{organization}'], function() use ($router) {
        $router->patch('roles', "Api\Platform\OrganizationsController@updateRoles");
        $router->patch('bank-fields', "Api\Platform\OrganizationsController@updateBankStatementFields");
        $router->patch('update-bi-connection', "Api\Platform\OrganizationsController@updateBIConnection");
        $router->patch('update-reservation-fields', "Api\Platform\OrganizationsController@updateReservationFields");
        $router->patch('update-accept-reservations', "Api\Platform\OrganizationsController@updateAcceptReservations");
        $router->get('features', "Api\Platform\OrganizationsController@getFeatures");
    });

    $router->resource('organizations', "Api\Platform\OrganizationsController")
        ->only('show', 'store', 'update');

    // Roles
    $router->resource('roles', "Api\Platform\RolesController")
        ->only('index', 'show');

    $router->post('funds/{fund}/apply', "Api\Platform\FundsController@apply")->name('fund.apply');
    $router->post('funds/{fund}/check', "Api\Platform\FundsController@check")->name('fund.check');

    $router->resource('vouchers', "Api\Platform\VouchersController")
        ->parameter('vouchers', 'voucher_token_address')
        ->only('index', 'show', 'destroy');

    $router->resource('reimbursements', "Api\Platform\ReimbursementsController")
        ->only('index', 'store', 'show', 'update', 'destroy');

    $router->post(
        'product-reservations/{reservation}/extra-payment/checkout',
        "Api\Platform\ProductReservationsController@checkoutExtraPayment");

    $router->post(
        'product-reservations/{reservation}/cancel',
        "Api\Platform\ProductReservationsController@cancel");

    $router->resource('product-reservations', "Api\Platform\ProductReservationsController")
        ->only('index', 'store', 'show');

    $router->post('product-reservations/validate-fields', "Api\Platform\ProductReservationsController@storeValidateFields");
    $router->post('product-reservations/validate-address', "Api\Platform\ProductReservationsController@storeValidateAddress");

    $router->group(['prefix' => '/provider'], static function() use ($router) {
        $router->resource('vouchers', "Api\Platform\Provider\VouchersController")
            ->parameter('vouchers', 'voucher_address_or_physical_code')
            ->only('show');

        $router->resource('vouchers.product-vouchers', "Api\Platform\Provider\Vouchers\ProductVouchersController")
            ->parameter('product-vouchers', 'product_voucher_token_address')
            ->parameter('vouchers', 'voucher_address_or_physical_code')
            ->only('index');

        $router->resource('vouchers.products', "Api\Platform\Provider\Vouchers\ProductsController")
            ->parameter('vouchers', 'voucher_address_or_physical_code')
            ->parameter('products', 'products')
            ->only('index', 'show');

        $router->resource('vouchers.transactions', "Api\Platform\Vouchers\TransactionsController")
            ->parameter('vouchers', 'voucher_address_or_physical_code')
            ->parameter('transactions', 'transaction_address')
            ->only('store');

        $router->resource('transactions', "Api\Platform\Provider\TransactionsController")
            ->parameter('transactions', 'transaction_address')
            ->only('index');
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
        "Api\Platform\Vouchers\DemoTransactionController",
    )->parameters([
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
        'organizations/{organization}/implementations/{implementation}/pre-check-banner',
        "Api\Platform\Organizations\ImplementationsController@updatePreCheckBanner");

    $router->patch(
        'organizations/{organization}/implementations/{implementation}/digid',
        "Api\Platform\Organizations\ImplementationsController@updateDigiD");

    $router->resource(
        'organizations/{organization}/implementations',
        "Api\Platform\Organizations\ImplementationsController"
    )->only('index', 'show');

    $router->post(
        'organizations/{organization}/implementations/{implementation}/pages/validate-blocks',
        "Api\Platform\Organizations\Implementations\ImplementationPagesController@storeBlocksValidate");

    $router->resource(
        'organizations/{organization}/implementations/{implementation}/pages',
        "Api\Platform\Organizations\Implementations\ImplementationPagesController"
    )->parameters([
        'pages' => 'implementationPage',
    ])->only('index', 'store', 'show', 'update', 'destroy');

    $router->resource(
        'organizations/{organization}/implementations/{implementation}/social-medias',
        "Api\Platform\Organizations\Implementations\ImplementationSocialMediaController"
    )->parameters([
        'social-media' => 'implementation_social_media',
    ])->only('index', 'store', 'show', 'update', 'destroy');

    $router->resource(
        'organizations/{organization}/implementations/{implementation}/system-notifications',
        "Api\Platform\Organizations\Implementations\SystemNotificationsController"
    )->only('index', 'show', 'update');

    $router->post(
        'organizations/{organization}/implementations/{implementation}/pre-checks/sync',
        "Api\Platform\Organizations\Implementations\PreCheckController@syncPreChecks"
    );

    $router->resource(
        'organizations/{organization}/implementations/{implementation}/pre-checks',
        "Api\Platform\Organizations\Implementations\PreCheckController"
    )->only('index');

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
        'organizations/{organization}/faq/validate',
        "Api\Platform\Organizations\FaqController@storeValidate");

    $router->post(
        'organizations/{organization}/funds/{fund}/top-up',
        "Api\Platform\Organizations\FundsController@topUp");

    $router->resource(
        'organizations.funds.top-up-transactions',
        "Api\Platform\Organizations\Funds\FundTopUpTransactionsController");

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

    $router->get(
        'organizations/{organization}/funds/{fund}/identities/export',
        "Api\Platform\Organizations\Funds\IdentitiesController@export");

    $router->get(
        'organizations/{organization}/funds/{fund}/identities/export-fields',
        "Api\Platform\Organizations\Funds\IdentitiesController@exportFields");

    $router->post(
        'organizations/{organization}/funds/{fund}/identities/notification',
        "Api\Platform\Organizations\Funds\IdentitiesController@sendIdentityNotification");

    $router->resource(
        'organizations.funds.identities',
        "Api\Platform\Organizations\Funds\IdentitiesController"
    )->only('index', 'show');

    $router->resource(
        'organizations.funds.provider-invitations',
        "Api\Platform\Organizations\Funds\FundProviderInvitationsController",
    )->parameters([
        'provider-invitations' => 'fund_provider_invitations',
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
            'organizations/{organization}/fund-requests/{fund_request}/assign-employee',
            "Api\Platform\Organizations\FundRequestsController@assignEmployee"
        );

        $router->patch(
            'organizations/{organization}/fund-requests/{fund_request}/resign-employee',
            "Api\Platform\Organizations\FundRequestsController@resignEmployee"
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
            'organizations/{organization}/fund-requests/{fund_request}/disregard',
            "Api\Platform\Organizations\FundRequestsController@disregard"
        );

        $router->patch(
            'organizations/{organization}/fund-requests/{fund_request}/disregard-undo',
            "Api\Platform\Organizations\FundRequestsController@disregardUndo"
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
            "Api\Platform\Organizations\FundRequests\FundRequestRecordsController"
        )->parameters([
            'records' => 'fund_request_record',
        ])->only( 'index', 'show', 'update', 'store');

        $router->resource(
            'organizations/{organization}/fund-requests/{fund_request}/clarifications',
            "Api\Platform\Organizations\FundRequests\FundRequestClarificationsController"
        )->parameters([
            'clarifications' => 'fund_request_clarification',
        ])->only( 'index', 'store', 'show');

        $router->get(
            'organizations/{organization}/fund-requests/export',
            "Api\Platform\Organizations\FundRequestsController@export"
        );

        $router->get(
            'organizations/{organization}/fund-requests/{fund_request}/person',
            "Api\Platform\Organizations\FundRequestsController@person"
        );

        // Fund requests notes
        $router->group(['prefix' => 'organizations/{organization}/fund-requests/{fund_request}'], function() use ($router) {
            $router->get('notes', "Api\Platform\Organizations\FundRequestsController@notes");
            $router->post('notes', "Api\Platform\Organizations\FundRequestsController@storeNote");
            $router->delete('notes/{note}', "Api\Platform\Organizations\FundRequestsController@destroyNote");
            $router->get('email-logs', "Api\Platform\Organizations\FundRequestsController@emailLogs");
            $router->post('email-logs/{emailLog}/export', "Api\Platform\Organizations\FundRequestsController@exportEmailLog");
        });

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

        // requester route
        $router->resource('fund-requests', "Api\Platform\FundRequestsController")->only([
            'index', 'show',
        ]);

        $router->resource(
            'fund-requests/{fund_request}/clarifications',
            'Api\Platform\FundRequests\FundRequestClarificationsController'
        )->parameters([
            'clarifications' => 'fund_request_clarification',
        ])->only('update');
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

    $router->resource(
        'organizations/{organization}/provider/fund-unsubscribes',
        "Api\Platform\Organizations\Provider\FundUnsubscribeController"
    )->parameters([
        'unsubscribe' => 'fund-unsubscribe'
    ])->only('index', 'store', 'update');

    $router->resource(
        'organizations/{organization}/sponsor/fund-unsubscribes',
        "Api\Platform\Organizations\Sponsor\FundUnsubscribeController"
    )->parameters([
        'unsubscribe' => 'fund-unsubscribe'
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

    $router->resource('organizations.funds.providers', "Api\Platform\Organizations\Funds\FundProviderController")
        ->parameter('providers', 'fund_provider')
        ->only('update');

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

    // Products
    $router->group(['prefix' => 'organizations/{organization}/products/{product}'], function() use ($router) {
        $router->patch('exclusions', "Api\Platform\Organizations\ProductsController@updateExclusions");
    });

    $router->resource(
        'organizations.products',
        "Api\Platform\Organizations\ProductsController"
    )->only('index', 'show', 'store', 'update', 'destroy');

    // Product reservations
    $router->get(
        'organizations/{organization}/product-reservations/export-fields',
        "Api\Platform\Organizations\ProductReservationsController@getExportFields"
    );

    $router->get(
        'organizations/{organization}/product-reservations/export',
        "Api\Platform\Organizations\ProductReservationsController@export"
    );

    $router->post(
        'organizations/{organization}/product-reservations/batch',
        "Api\Platform\Organizations\ProductReservationsController@storeBatch"
    );

    $router->group(['prefix' => 'organizations/{organization}/product-reservations/{product_reservation}'], function() use ($router) {
        $router->post('accept', "Api\Platform\Organizations\ProductReservationsController@accept");
        $router->post('reject', "Api\Platform\Organizations\ProductReservationsController@reject");
        $router->post('archive', "Api\Platform\Organizations\ProductReservationsController@archive");
        $router->post('unarchive', "Api\Platform\Organizations\ProductReservationsController@unarchive");
        $router->get('extra-payments/fetch', "Api\Platform\Organizations\ProductReservationsController@fetchExtraPayment");
        $router->get('extra-payments/refund', "Api\Platform\Organizations\ProductReservationsController@refundExtraPayment");
    });

    $router->resource(
        'organizations.product-reservations',
        "Api\Platform\Organizations\ProductReservationsController"
    )->only('index', 'store', 'show');

    // Reimbursements
    $router->group(['prefix' => 'organizations/{organization}/reimbursements/{reimbursement}'], function() use ($router) {
        $router->get('notes', "Api\Platform\Organizations\ReimbursementsController@notes");
        $router->post('notes', "Api\Platform\Organizations\ReimbursementsController@storeNote");
        $router->delete('notes/{note}', "Api\Platform\Organizations\ReimbursementsController@destroyNote");
        $router->post('assign', "Api\Platform\Organizations\ReimbursementsController@assign");
        $router->post('resign', "Api\Platform\Organizations\ReimbursementsController@resign");
        $router->post('approve', "Api\Platform\Organizations\ReimbursementsController@approve");
        $router->post('decline', "Api\Platform\Organizations\ReimbursementsController@decline");
    });

    $router->get(
        'organizations/{organization}/reimbursements/export-fields',
        "Api\Platform\Organizations\ReimbursementsController@getExportFields"
    );

    $router->get(
        'organizations/{organization}/reimbursements/export',
        "Api\Platform\Organizations\ReimbursementsController@export"
    );

    $router->resource(
        'organizations.reimbursements',
        "Api\Platform\Organizations\ReimbursementsController"
    )->only('index', 'show', 'update');

    $router->resource(
        'organizations.products.funds',
        "Api\Platform\Organizations\Products\FundsController"
    )->only('index');

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

    $router->resource('organizations.bank-connections', "Api\Platform\Organizations\BankConnectionsController")
        ->parameter('bank-connections', 'bankConnection')
        ->only('index', 'show', 'store', 'update');

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

    $router->get(
        'organizations/{organization}/employees/export',
        'Api\Platform\Organizations\EmployeesController@export'
    );

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

    $router->get(
        'organizations/{organization}/provider/funds-product-required',
        "Api\Platform\Organizations\Provider\FundProviderController@fundsProductRequired");

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
        'organizations/{organization}/provider/transactions/export-fields',
        "Api\Platform\Organizations\Provider\TransactionsController@getExportFields"
    );

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

    // Mollie
    $router->group(['prefix' => 'organizations/{organization}/mollie-connection'], function() use ($router) {
        $router->get('', 'Api\Platform\Organizations\MollieConnectionController@getActive');
        $router->post('', 'Api\Platform\Organizations\MollieConnectionController@store');
        $router->patch('', 'Api\Platform\Organizations\MollieConnectionController@update');
        $router->delete('', 'Api\Platform\Organizations\MollieConnectionController@destroy');
        $router->get('fetch', 'Api\Platform\Organizations\MollieConnectionController@fetchActive');
        $router->post('connect', 'Api\Platform\Organizations\MollieConnectionController@connectOAuth');
    });

    $router->resource(
        'organizations/{organization}/mollie-connection/profiles',
        'Api\Platform\Organizations\MollieConnectionProfileController',
    )->only('store', 'update');

    // BI connection
    $router->group(['prefix' => 'organizations/{organization}/bi-connection'], function() use ($router) {
        $router->get('', 'Api\Platform\Organizations\BIConnectionController@getActive');
        $router->post('', 'Api\Platform\Organizations\BIConnectionController@store');
        $router->patch('', 'Api\Platform\Organizations\BIConnectionController@update');
        $router->get('reset', 'Api\Platform\Organizations\BIConnectionController@resetToken');
        $router->get('data-types', 'Api\Platform\Organizations\BIConnectionController@getAvailableDataTypes');
    });

    /*$router->resource(
        'organizations.bi-connections',
        'Api\Platform\Organizations\BIConnectionController',
    )->parameters([
        'bi-connections' => 'connection',
    ])->only('store', 'update');*/

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
        'organizations/{organization}/sponsor/transactions/export-fields',
        "Api\Platform\Organizations\Sponsor\TransactionsController@getExportFields"
    );

    $router->get(
        'organizations/{organization}/sponsor/transactions/export',
        "Api\Platform\Organizations\Sponsor\TransactionsController@export"
    );

    $router->post(
        'organizations/{organization}/sponsor/transactions/batch',
        "Api\Platform\Organizations\Sponsor\TransactionsController@storeBatch"
    );

    $router->post(
        'organizations/{organization}/sponsor/transactions/batch/validate',
        "Api\Platform\Organizations\Sponsor\TransactionsController@storeBatchValidate"
    );

    $router->resource(
        'organizations/{organization}/sponsor/transactions',
        "Api\Platform\Organizations\Sponsor\TransactionsController"
    )->parameters([
        'transactions' => 'transaction_address',
    ])->only('index', 'show', 'store');

    $router->get(
        'organizations/{organization}/sponsor/transaction-bulks/export-fields',
        "Api\Platform\Organizations\Sponsor\TransactionBulksController@getExportFields"
    );

    $router->get(
        'organizations/{organization}/sponsor/transaction-bulks/export',
        "Api\Platform\Organizations\Sponsor\TransactionBulksController@export"
    );

    $router->get(
        'organizations/{organization}/sponsor/transaction-bulks/{voucher_transaction_bulks}/export-sepa',
        "Api\Platform\Organizations\Sponsor\TransactionBulksController@exportSEPA"
    );

    $router->patch(
        'organizations/{organization}/sponsor/transaction-bulks/{voucher_transaction_bulks}/set-accepted',
        "Api\Platform\Organizations\Sponsor\TransactionBulksController@setAccepted"
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
        'organizations/{organization}/sponsor/vouchers/export-fields',
        "Api\Platform\Organizations\Sponsor\VouchersController@getExportFields"
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
        "Api\Platform\Organizations\Sponsor\VouchersController"
    )->only('index', 'show', 'store', 'update');

    $router->resource(
        'organizations/{organization}/sponsor/vouchers/{voucher}/voucher-records',
        "Api\Platform\Organizations\Sponsor\Vouchers\VoucherRecordsController",
    )->only('index', 'show', 'store', 'update', 'destroy');

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
        'Api\Platform\Organizations\Sponsor\Providers\ProductsController',
    )->parameters([
        'providers' => 'organization_id',
    ])->only('index', 'show', 'store', 'update', 'destroy');

    $router->resource(
        'organizations/{organization}/sponsor/reservation-extra-payments',
        'Api\Platform\Organizations\Sponsor\ReservationExtraPaymentsController'
    )->parameters([
        'reservation-extra-payments' => 'payment',
    ])->only('index', 'show');

    $router->get(
        'organizations/{organization}/logs',
        'Api\Platform\Organizations\EventLogsController@index',
    );

    $router->get(
        'organizations/{organization}/announcements',
        'Api\Platform\Organizations\AnnouncementController@index',
    );

    $router->get('prevalidations/export', 'Api\Platform\PrevalidationController@export');
    $router->post('prevalidations/collection', 'Api\Platform\PrevalidationController@storeCollection');
    $router->post('prevalidations/collection/hash', 'Api\Platform\PrevalidationController@collectionHash');

    $router->resource('prevalidations', 'Api\Platform\PrevalidationController')
        ->parameter('prevalidations', 'prevalidation_uid')
        ->only('index', 'store', 'destroy');

    $router->resource('feedback', 'Api\Platform\FeedbackController')
        ->only('store');

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

    $router->post('format', 'Api\Platform\FormatController@format');

    $router
        ->post('firestore-tokens', 'Api\Platform\FirestoreTokensController@store')
        ->middleware('throttle:30,1');
});