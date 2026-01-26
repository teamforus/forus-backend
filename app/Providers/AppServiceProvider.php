<?php

namespace App\Providers;

use App\Media\CmsMediaConfig;
use App\Media\FundLogoMediaConfig;
use App\Media\ImplementationBannerMediaConfig;
use App\Media\ImplementationBlockMediaConfig;
use App\Media\ImplementationMailLogoMediaConfig;
use App\Media\OfficePhotoMediaConfig;
use App\Media\OrganizationLogoMediaConfig;
use App\Media\PhysicalCardTypePhotoMediaConfig;
use App\Media\PreCheckBannerMediaConfig;
use App\Media\ProductPhotoMediaConfig;
use App\Media\ReimbursementFilePreviewMediaConfig;
use App\Models\BankConnection;
use App\Models\Employee;
use App\Models\Faq;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\FundRequest;
use App\Models\FundRequestClarification;
use App\Models\FundRequestRecord;
use App\Models\Identity;
use App\Models\IdentityEmail;
use App\Models\Implementation;
use App\Models\ImplementationBlock;
use App\Models\ImplementationPage;
use App\Models\NotificationTemplate;
use App\Models\Office;
use App\Models\Organization;
use App\Models\PhysicalCard;
use App\Models\PhysicalCardRequest;
use App\Models\PrevalidationRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductReservation;
use App\Models\ProductReservationFieldValue;
use App\Models\Reimbursement;
use App\Models\ReservationExtraPayment;
use App\Models\Voucher;
use App\Models\VoucherRecord;
use App\Models\VoucherTransaction;
use App\Models\VoucherTransactionBulk;
use App\Notifications\DatabaseChannel;
use App\Observers\FundProviderObserver;
use App\Services\BIConnectionService\Models\BIConnection;
use App\Services\MediaService\MediaService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Notifications\Channels\DatabaseChannel as IlluminateDatabaseChannel;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class AppServiceProvider extends ServiceProvider
{
    /**
     * @var array|string[]
     */
    public static array $morphMap = [
        'faq' => Faq::class,
        'fund' => Fund::class,
        'office' => Office::class,
        'voucher' => Voucher::class,
        'product' => Product::class,
        'identity' => Identity::class,
        'employees' => Employee::class,
        'fund_request' => FundRequest::class,
        'organization' => Organization::class,
        'bi_connection' => BIConnection::class,
        'reimbursement' => Reimbursement::class,
        'identity_email' => IdentityEmail::class,
        'mail_template' => NotificationTemplate::class,
        'fund_provider' => FundProvider::class,
        'physical_card' => PhysicalCard::class,
        'bank_connection' => BankConnection::class,
        'implementation' => Implementation::class,
        'product_category' => ProductCategory::class,
        'implementation_page' => ImplementationPage::class,
        'implementation_block' => ImplementationBlock::class,
        'product_reservation' => ProductReservation::class,
        'product_reservation_field_value' => ProductReservationFieldValue::class,
        'physical_card_request' => PhysicalCardRequest::class,
        'fund_request_record' => FundRequestRecord::class,
        'fund_request_clarification' => FundRequestClarification::class,
        'voucher_record' => VoucherRecord::class,
        'voucher_transaction' => VoucherTransaction::class,
        'prevalidation_request' => PrevalidationRequest::class,
        'voucher_transaction_bulk' => VoucherTransactionBulk::class,
        'reservation_extra_payment' => ReservationExtraPayment::class,
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->setLocale(config('app.locale'));

        $this->extendValidator();
        $this->registerMediaConfigs();
        $this->registerNotificationChannels();

        Schema::defaultStringLength(191);
        Relation::morphMap(self::$morphMap);

        StringHelper::setDecimalSeparator('.');
        StringHelper::setThousandsSeparator(',');

        FundProvider::observe(FundProviderObserver::class);

        ParallelTesting::setUpTestDatabase(function () {
            Artisan::call('db:seed');
            Artisan::call('test-data:seed');
        });

        if (Config::get('translation-service.target_languages')) {
            Config::set('translatable.locales', Config::get('translation-service.target_languages'));
        }

        if (Config::get('app.memory_limit')) {
            ini_set('memory_limit', Config::get('app.memory_limit'));
        }

        if (Config::get('app.exception_max_line_length')) {
            ini_set('zend.exception_string_param_max_len', Config::get('app.exception_max_line_length'));
        }

        if (App::runningUnitTests()) {
            Config::set('mail.default', 'array');
            Config::set('queue.default', 'sync');
        }

        FormRequest::macro('onlyValidated', function (array $keys, mixed $default = []) {
            return array_intersect_key($this->validated(null, $default), array_flip($keys));
        });
    }

    /**
     * @param string $locale
     * @return false|string
     */
    public function setLocale(string $locale): string|false
    {
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
    public function register(): void
    {
    }

    /**
     * @return void
     */
    protected function registerMediaConfigs(): void
    {
        MediaService::setMediaConfigs([
            new CmsMediaConfig(),
            new FundLogoMediaConfig(),
            new OfficePhotoMediaConfig(),
            new ProductPhotoMediaConfig(),
            new OrganizationLogoMediaConfig(),
            new ImplementationBannerMediaConfig(),
            new PhysicalCardTypePhotoMediaConfig(),
            new ReimbursementFilePreviewMediaConfig(),
            new ImplementationMailLogoMediaConfig(),
            new ImplementationBlockMediaConfig(),
            new PreCheckBannerMediaConfig(),
        ]);
    }

    /**
     * @return void
     */
    protected function registerNotificationChannels(): void
    {
        $this->app->instance(IlluminateDatabaseChannel::class, new DatabaseChannel());
    }

    /**
     * @return void
     */
    protected function extendValidator(): void
    {
        Validator::extend('city_name', function ($attribute, $value) {
            return preg_match('/^[A-Za-z\- ]{0,100}$/', $value);
        });

        Validator::extend('street_name', function ($attribute, $value) {
            return preg_match('/^[A-Za-z\- ]{0,80}$/', $value);
        });

        Validator::extend('house_number', function ($attribute, $value) {
            return preg_match('/^[1-9][0-9]{0,4}$/', $value);
        });

        Validator::extend('postcode', function ($attribute, $value) {
            return preg_match('/^[1-9][0-9]{3} ?[a-zA-Z]{2}$/', $value);
        });

        Validator::extend('house_addition', function ($attribute, $value) {
            return preg_match('/^[a-zA-Z0-9\-]{1,4}$/', $value);
        });
    }
}
