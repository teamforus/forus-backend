<?php

namespace App\Providers;

use App\Models\BunqMeTab;
use App\Models\Fund;
use App\Models\Employee;
use App\Models\FundProvider;
use App\Models\FundProviderInvitation;
use App\Models\FundRequest;
use App\Models\FundRequestClarification;
use App\Models\FundRequestRecord;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Prevalidation;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Models\VoucherTransaction;
use App\Models\DemoTransaction;
use App\Services\DigIdService\Models\DigIdSession;
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
    public function boot()
    {

        parent::boot();

        $router = app()->make('router');

        $router->bind('bunq_me_tab_paid', function ($value) {
            return BunqMeTab::query()->where([
                    'status' => 'PAID',
                    'id' => $value,
                ])->first() ?? abort(404);
        });

        $router->bind('prevalidation_uid', function ($value) {
            return Prevalidation::query()->where([
                    'uid' => $value,
                    'state' => Prevalidation::STATE_PENDING
                ])->first() ?? null;
        });

        $router->bind('organization', function ($id) {
            return Organization::find($id) ?? abort(404);
        });

        $router->bind('fund', function ($id) {
            return Fund::find($id) ?? abort(404);
        });

        $router->bind('fund_provider', function ($id) {
            return FundProvider::find($id) ?? abort(404);
        });

        $router->bind('fund_provider_invitations', function ($id) {
            return FundProviderInvitation::find($id) ?? abort(404);
        });

        $router->bind('fund_provider_invitation_token', function ($value) {
            return FundProviderInvitation::where([
                'token' => $value
                ])->first() ?? abort(404);
        });

        $router->bind('configured_fund', function ($value) {
            return Fund::query()->where([
                    'id' => $value
                ])->has('fund_config')->first() ?? abort(404);
        });

        $router->bind('voucher_token_address', function ($value) {
            return VoucherToken::query()->where([
                    'address' => $value
                ])->first() ?? abort(404);
        });

        $router->bind('transaction_address', function ($value) {
            return VoucherTransaction::query()->where([
                    'address' => $value
                ])->first() ?? abort(404);
        });

        $router->bind('voucher_id', function ($value) {
            return Voucher::query()->where([
                    'id' => $value
                ])->first() ?? abort(404);
        });

        $router->bind('demo_token', function ($value) {
            return DemoTransaction::query()->where([
                    'token' => $value
                ])->first() ?? abort(404);
        });

        $router->bind('employee_id', function ($value) {
            return Employee::query()->where([
                'id' => $value
                ])->first() ?? abort(404);
        });

        $router->bind('product_with_trashed', function ($value) {
            return Product::query()->where([
                    'id' => $value
                ])->withTrashed()->first() ?? abort(404);
        });

        $router->bind('fund_request', function ($id) {
            return FundRequest::find($id) ?? abort(404);
        });

        $router->bind('fund_request_record', function ($id) {
            return FundRequestRecord::find($id) ?? abort(404);
        });

        $router->bind('fund_request_clarification', function ($id) {
            return FundRequestClarification::find($id) ?? abort(404);
        });

        $router->bind('digid_session_uid', function ($digid_session_uid) {
            return DigIdSession::where([
                'state'         => DigIdSession::STATE_PENDING_AUTH,
                'session_uid'   => $digid_session_uid,
            ])->where(
                'created_at', '>=', now()->subSeconds(
                    DigIdSession::SESSION_EXPIRATION_TIME
                ))->first() ?? abort(404);
        });

        $router->bind('platform_config', function ($value) {
            return Implementation::platformConfig($value);
        });
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
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
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api/v1')
             ->middleware([
                 'api', 'implementation_key', 'client_key'
             ])
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));

        Route::prefix('api/v1/platform')
            ->middleware([
                'api', 'implementation_key', 'client_key'
            ])
            ->namespace($this->namespace)
            ->group(base_path('routes/api-platform.php'));
    }
}
