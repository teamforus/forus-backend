<?php

namespace App\Providers;

use App\Models\Fund;
use App\Models\Employee;
use App\Models\Implementation;
use App\Models\Prevalidation;
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

        $router->bind('prevalidation_uid', function ($value) {
            return Prevalidation::getModel()->where([
                    'uid' => $value
                ])->first() ?? abort(404);
        });

        $router->bind('media_uid', function ($value) {
            return Media::getModel()->where([
                    'uid' => $value
                ])->first() ?? abort(404);
        });

        $router->bind('fund_id', function ($value) {
            return Fund::getModel()->where([
                    'id' => $value
                ])->first() ?? abort(404);
        });

        $router->bind('voucher_token_address', function ($value) {
            return VoucherToken::getModel()->where([
                    'address' => $value
                ])->first() ?? abort(404);
        });

        $router->bind('transaction_address', function ($value) {
            return VoucherTransaction::query()->where([
                    'address' => $value
                ])->first() ?? abort(404);
        });

        $router->bind('employee_id', function ($value) {
            return Employee::query()->where([
                'id' => $value
                ])->first() ?? abort(404);
        });

        $router->bind('platform_config', function ($value) {
            $ver = request()->input('ver');

            if (preg_match('/[^a-z_\-0-9]/i', $value)) {
                exit(abort(403,''));
            }

            if (preg_match('/[^a-z_\-0-9]/i', $ver)) {
                exit(abort(403,''));
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
                $config['fronts'] = Implementation::active()->only([
                    'url_webshop', 'url_sponsor', 'url_provider',
                    'url_validator', 'url_app'
                ]);
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
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));

        Route::prefix('api/v1/platform')
            ->middleware([
                'api', 'implementation_key'
            ])
            ->namespace($this->namespace)
            ->group(base_path('routes/api-platform.php'));
    }
}
