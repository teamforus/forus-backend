<?php

namespace App\Providers;

use App\Media\FundLogoMediaConfig;
use App\Media\OfficePhotoMediaConfig;
use App\Media\ProductPhotoMediaConfig;
use App\Media\ProductPhotosMediaConfig;
use App\Media\RecordCategoryIconMediaConfig;
use App\Models\FundProvider;
use App\Models\FundRequest;
use App\Observers\FundProviderObserver;
use Carbon\Carbon;
use App\Media\OrganizationLogoMediaConfig;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\Office;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Voucher;
use App\Services\MediaService\MediaService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @throws \App\Services\MediaService\Exceptions\MediaConfigAlreadyRegisteredException
     */
    public function boot()
    {
        self::setLocale(config('app.locale'));

        Schema::defaultStringLength(191);

        Relation::morphMap([
            'fund'              => Fund::class,
            'office'            => Office::class,
            'voucher'           => Voucher::class,
            'product'           => Product::class,
            'employees'         => Employee::class,
            'fund_request'      => FundRequest::class,
            'fund_provider'     => FundProvider::class,
            'organization'      => Organization::class,
            'product_category'  => ProductCategory::class,
        ]);

        MediaService::setMediaConfigs([
            new FundLogoMediaConfig(),
            new OfficePhotoMediaConfig(),
            new ProductPhotoMediaConfig(),
            new OrganizationLogoMediaConfig(),
            new RecordCategoryIconMediaConfig(),
        ]);

        FundProvider::observe(FundProviderObserver::class);
    }

    /**
     * @param string $locale
     * @return false|string
     */
    public function setLocale(string $locale) {
        if (strlen($locale) == 2) {
            $locale .= '_' . strtoupper($locale);
        }

        Carbon::setLocale($locale);

        return setlocale(LC_ALL, $locale);
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
