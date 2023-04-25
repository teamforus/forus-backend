<?php

namespace App\Providers;

use App\Models\BankConnection;
use App\Models\Fund;
use App\Models\Employee;
use App\Models\FundProvider;
use App\Models\FundProviderChat;
use App\Models\FundProviderChatMessage;
use App\Models\FundProviderInvitation;
use App\Models\FundRequest;
use App\Models\FundRequestClarification;
use App\Models\FundRequestRecord;
use App\Models\Implementation;
use App\Models\ImplementationSocialMedia;
use App\Models\Organization;
use App\Models\Prevalidation;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Models\VoucherTransaction;
use App\Models\DemoTransaction;
use App\Models\VoucherTransactionBulk;
use App\Services\DigIdService\Models\DigIdSession;
use App\Models\IdentityEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function boot(): void
    {
        parent::boot();

        $router = app()->make('router');

        $router->bind('prevalidation_uid', static function ($value) {
            return Prevalidation::query()->where([
                'uid' => $value,
                'state' => Prevalidation::STATE_PENDING
            ])->first();
        });

        $router->bind('organization', static function ($id) {
            return Organization::findOrFail($id);
        });

        $router->bind('validator_organization', static function ($id) {
            return Organization::whereIsValidator(true)->findOrFail($id);
        });

        $router->bind('fund', static function ($id) {
            return Fund::findOrFail($id);
        });

        $router->bind('fund_provider', static function ($id) {
            return FundProvider::findOrFail($id);
        });

        $router->bind('fund_provider_chats', static function ($id) {
            return FundProviderChat::findOrFail($id);
        });

        $router->bind('fund_provider_chat_messages', static function ($id) {
            return FundProviderChatMessage::findOrFail($id);
        });

        $router->bind('identity_email', static function ($id) {
            return IdentityEmail::findOrFail($id);
        });

        $router->bind('identity_email_token', static function ($id) {
            return IdentityEmail::whereVerificationToken($id)->firstOrFail();
        });

        $router->bind('fund_provider_invitations', static function ($id) {
            return FundProviderInvitation::findOrFail($id);
        });

        $router->bind('fund_provider_invitation_token', static function ($token) {
            return FundProviderInvitation::where(compact('token'))->firstOrFail();
        });

        $router->bind('voucher_address_or_physical_code', static function ($value) {
            $isCard = is_string($value) && strlen($value) === 12;

            try {
                if ($voucher = Voucher::findByAddressOrPhysicalCard($value)) {
                    return $voucher->token_without_confirmation;
                }
            } catch (ModelNotFoundException) {}

            abort(404, $isCard ? "De pas is niet geactiveerd": 'De voucher is niet gevonden.');
        });

        $router->bind('voucher_token_address', static function ($address) {
            return VoucherToken::whereAddress($address)->firstOrFail();
        });

        $router->bind('product_voucher_token_address', static function ($address) {
            return VoucherToken::whereAddress($address)->whereHas('voucher', static function(Builder $builder) {
                $builder->whereNotNull('parent_id');
            })->firstOrFail();
        });

        $router->bind('budget_voucher_token_address', static function ($address) {
            return VoucherToken::whereAddress($address)->whereHas('voucher', static function(Builder $builder) {
                $builder->whereNull('parent_id');
            })->firstOrFail();
        });

        $router->bind('transaction_address', static function ($address) {
            return VoucherTransaction::whereAddress($address)->firstOrFail();
        });

        $router->bind('voucher_id', static function ($voucher_id) {
            return Voucher::findOrFail($voucher_id);
        });

        $router->bind('demo_token', static function ($demo_token) {
            return DemoTransaction::whereToken($demo_token)->firstOrFail();
        });

        $router->bind('employee_id', static function ($employee_id) {
            return Employee::findOrFail($employee_id);
        });

        $router->bind('organization_id', static function ($organization_id) {
            return Organization::findOrFail($organization_id);
        });

        $router->bind('product_with_trashed', static function ($product_id) {
            return Product::withTrashed()->findOrFail($product_id);
        });

        $router->bind('fund_request', static function ($id) {
            return FundRequest::findOrFail($id);
        });

        $router->bind('fund_request_record', static function ($id) {
            return FundRequestRecord::findOrFail($id);
        });

        $router->bind('fund_request_clarification', static function ($id) {
            return FundRequestClarification::findOrFail($id);
        });

        $router->bind('digid_session_uid', static function ($digid_session_uid) {
            $sessionExpireTime = now()->subSeconds(DigIdSession::SESSION_EXPIRATION_TIME);

            return DigIdSession::where([
                'state'         => DigIdSession::STATE_PENDING_AUTH,
                'session_uid'   => $digid_session_uid,
            ])->where('created_at', '>=', $sessionExpireTime)->firstOrFail();
        });

        $router->bind('bngBankConnectionToken', static function ($token) {
            return BankConnection::whereRedirectToken($token)->whereHas('bank', function(Builder $builder) {
                $builder->where('key', 'bng');
            })->firstOrFail();
        });

        $router->bind('bngVoucherTransactionBulkToken', static function ($token) {
            return VoucherTransactionBulk::whereRedirectToken($token)->where([
                'state' => VoucherTransactionBulk::STATE_PENDING,
            ])->whereHas('bank_connection.bank', function(Builder $builder) {
                $builder->where('key', 'bng');
            })->firstOrFail();
        });

        $router->bind('platform_config', static function ($value) {
            return Implementation::platformConfig($value);
        });

        $router->bind('voucher_transaction_bulks', static function ($value) {
            return VoucherTransactionBulk::findOrFail($value);
        });
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map(): void
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        //
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes(): void
    {
        Route::namespace($this->namespace)->middleware([
            'web'
        ])->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes(): void
    {
        Route::prefix('api/v1')->namespace(
            $this->namespace
        )->middleware([
            'api', 'implementation_key', 'client_key', 'client_version', 'forus_session'
        ])->group(base_path('routes/api.php'));

        Route::prefix('api/v1/platform')->namespace(
            $this->namespace
        )->middleware([
            'api', 'implementation_key', 'client_key', 'client_version', 'forus_session'
        ])->group(base_path('routes/api-platform.php'));
    }
}
