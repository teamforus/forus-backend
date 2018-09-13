<?php

namespace App\Providers;

use App\Models\Fund;
use App\Models\Prevalidation;
use App\Models\ProviderIdentity;
use App\Models\Voucher;
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

        $router->bind('voucher_address', function ($value) {
            return Voucher::getModel()->where([
                    'address' => $value
                ])->first() ?? abort(404);
        });

        $router->bind('transaction_address', function ($value) {
            return VoucherTransaction::getModel()->where([
                    'address' => $value
                ])->first() ?? abort(404);
        });

        $router->bind('provider_identity', function ($value) {
            return ProviderIdentity::getModel()->where([
                    'id' => $value
                ])->first() ?? abort(404);
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
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/api-platform.php'));
    }
}
