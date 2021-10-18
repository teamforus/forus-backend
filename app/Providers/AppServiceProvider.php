<?php

namespace App\Providers;

use App\Media\CmsMediaConfig;
use App\Media\FundLogoMediaConfig;
use App\Media\ImplementationBannerMediaConfig;
use App\Media\ImplementationMailLogoMediaConfig;
use App\Media\OfficePhotoMediaConfig;
use App\Media\ProductPhotoMediaConfig;
use App\Media\ProductPhotosMediaConfig;
use App\Media\RecordCategoryIconMediaConfig;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\FundRequest;
use App\Models\Implementation;
use App\Models\ImplementationPage;
use App\Models\NotificationTemplate;
use App\Models\PhysicalCardRequest;
use App\Models\ProductReservation;
use App\Observers\FundProviderObserver;
use Carbon\Carbon;
use App\Media\OrganizationLogoMediaConfig;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Voucher;
use App\Services\MediaService\MediaService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

/**
 * Class AppServiceProvider
 * @package App\Providers
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @throws \App\Services\MediaService\Exceptions\MediaConfigAlreadyRegisteredException
     */
    public function boot(): void
    {
        $this->setLocale(config('app.locale'));

        Schema::defaultStringLength(191);

        Relation::morphMap([
            'fund'                  => Fund::class,
            'office'                => Office::class,
            'voucher'               => Voucher::class,
            'product'               => Product::class,
            'employees'             => Employee::class,
            'fund_request'          => FundRequest::class,
            'fund_provider'         => FundProvider::class,
            'organization'          => Organization::class,
            'mail_template'         => NotificationTemplate::class,
            'implementation'        => Implementation::class,
            'product_category'      => ProductCategory::class,
            'implementation_page'   => ImplementationPage::class,
            'product_reservation'   => ProductReservation::class,
            'physical_card_request' => PhysicalCardRequest::class,
        ]);

        MediaService::setMediaConfigs([
            new CmsMediaConfig(),
            new FundLogoMediaConfig(),
            new OfficePhotoMediaConfig(),
            new ProductPhotoMediaConfig(),
            new OrganizationLogoMediaConfig(),
            new RecordCategoryIconMediaConfig(),
            new ImplementationBannerMediaConfig(),
            new ImplementationMailLogoMediaConfig(),
        ]);

        FundProvider::observe(FundProviderObserver::class);

        /*Blade::directive('mail_builder_html', function ($expression) {
            return $expression;
        });*/
    }

    /**
     * @param string $locale
     * @return false|string
     */
    public function setLocale(string $locale) {
        if (strlen($locale) === 2) {
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
    public function register() { }
}
