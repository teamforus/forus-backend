<?php

namespace App\Providers;

use App\Repositories\FundsRepo;
use App\Repositories\Interfaces\IFundsRepo;
use App\Repositories\Interfaces\IProductsRepo;
use App\Repositories\Interfaces\IVouchersRepo;
use App\Repositories\ProductsRepo;
use App\Repositories\VouchersRepo;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            IFundsRepo::class,
            FundsRepo::class
        );

        $this->app->bind(
            IProductsRepo::class,
            ProductsRepo::class
        );

        $this->app->bind(
            IVouchersRepo::class,
            VouchersRepo::class
        );
    }
}
