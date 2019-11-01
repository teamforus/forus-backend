<?php

namespace App\Providers;

use App\Models\BunqMeTab;
use App\Models\Fund;
use App\Models\Employee;
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
use App\Services\MediaService\Models\Media;
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
     * @return void
     */
    public function boot()
    {
        //

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
                    'uid' => $value
                ])->first() ?? null;
        });

        $router->bind('organization', function ($id) {
            return Organization::find($id) ?? abort(404);
        });

        $router->bind('fund', function ($id) {
            return Fund::find($id) ?? abort(404);
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

        $router->bind('platform_config', function ($value) {
            if (Implementation::implementationKeysAvailable()->search(
                Implementation::activeKey()
            ) === false) {
                return abort(403, 'unknown_implementation_key');
            };


            $ver = request()->input('ver');

            if (preg_match('/[^a-z_\-0-9]/i', $value)) {
                abort(403);
            }

            if (preg_match('/[^a-z_\-0-9]/i', $ver)) {
                abort(403);
            }

            $config = config(
                'forus.features.' . $value . ($ver ? '.' . $ver : '')
            );

            if (is_array($config)) {
                $config['media'] = collect(config('media.sizes'))->map(function($size) {
                    return collect($size)->only([
                        'aspect_ratio', 'size'
                    ]);
                });
            }

            if (is_array($config)) {
                $implementation = Implementation::active();

                $config['fronts'] = $implementation->only([
                    'url_webshop', 'url_sponsor', 'url_provider',
                    'url_validator', 'url_app'
                ]);

                $config['map'] = [
                    'lon' => doubleval(
                        $implementation['lon'] ?: config('forus.front_ends.map.lon')
                    ),
                    'lat' => doubleval(
                        $implementation['lat'] ?: config('forus.front_ends.map.lat')
                    )
                ];
            }

            return $config ?: [];
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
