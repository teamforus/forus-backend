<?php

namespace App\Providers;

use App\Models\Employee;
use App\Models\Fund;
use App\Models\Office;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        $locale = config('app.locale');

        if (strlen($locale) == 2) {
            $locale .= '_' . strtoupper($locale);
        }

        setlocale(LC_ALL, $locale);

        Carbon::setLocale($locale);

        Relation::morphMap([
            'fund'              => Fund::class,
            'office'            => Office::class,
            'voucher'           => Voucher::class,
            'product'           => Product::class,
            'employees'         => Employee::class,
            'organization'      => Organization::class,
            'product_category'  => ProductCategory::class,
        ]);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
