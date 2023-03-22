<?php

namespace App\Providers;

use App\Media\CmsMediaConfig;
use App\Media\FundLogoMediaConfig;
use App\Media\ImplementationBannerMediaConfig;
use App\Media\ImplementationBlockMediaConfig;
use App\Media\ImplementationMailLogoMediaConfig;
use App\Media\OfficePhotoMediaConfig;
use App\Media\ProductPhotoMediaConfig;
use App\Media\ReimbursementFilePreviewMediaConfig;
use App\Models\BankConnection;
use App\Models\Faq;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\FundRequest;
use App\Models\FundRequestClarification;
use App\Models\FundRequestRecord;
use App\Models\IdentityEmail;
use App\Models\Implementation;
use App\Models\ImplementationBlock;
use App\Models\ImplementationPage;
use App\Models\NotificationTemplate;
use App\Models\PhysicalCard;
use App\Models\PhysicalCardRequest;
use App\Models\ProductReservation;
use App\Models\Reimbursement;
use App\Models\VoucherRecord;
use App\Models\VoucherTransaction;
use App\Models\VoucherTransactionBulk;
use App\Observers\FundProviderObserver;
use App\Models\Identity;
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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class AppServiceProvider extends ServiceProvider
{
    /**
     * @var array|string[]
     */
    public static array $morphMap = [
        'faq'                           => Faq::class,
        'fund'                          => Fund::class,
        'office'                        => Office::class,
        'voucher'                       => Voucher::class,
        'product'                       => Product::class,
        'identity'                      => Identity::class,
        'employees'                     => Employee::class,
        'fund_request'                  => FundRequest::class,
        'organization'                  => Organization::class,
        'reimbursement'                 => Reimbursement::class,
        'identity_email'                => IdentityEmail::class,
        'mail_template'                 => NotificationTemplate::class,
        'fund_provider'                 => FundProvider::class,
        'physical_card'                 => PhysicalCard::class,
        'bank_connection'               => BankConnection::class,
        'implementation'                => Implementation::class,
        'product_category'              => ProductCategory::class,
        'implementation_page'           => ImplementationPage::class,
        'implementation_block'          => ImplementationBlock::class,
        'product_reservation'           => ProductReservation::class,
        'physical_card_request'         => PhysicalCardRequest::class,
        'fund_request_record'           => FundRequestRecord::class,
        'fund_request_clarification'    => FundRequestClarification::class,
        'voucher_record'                => VoucherRecord::class,
        'voucher_transaction'           => VoucherTransaction::class,
        'voucher_transaction_bulk'      => VoucherTransactionBulk::class,
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->setLocale(config('app.locale'));

        Schema::defaultStringLength(191);
        Relation::morphMap(self::$morphMap);

        MediaService::setMediaConfigs([
            new CmsMediaConfig(),
            new FundLogoMediaConfig(),
            new OfficePhotoMediaConfig(),
            new ProductPhotoMediaConfig(),
            new OrganizationLogoMediaConfig(),
            new ImplementationBannerMediaConfig(),
            new ReimbursementFilePreviewMediaConfig(),
            new ImplementationMailLogoMediaConfig(),
            new ImplementationBlockMediaConfig(),
        ]);

        StringHelper::setDecimalSeparator('.');
        StringHelper::setThousandsSeparator(',');

        FundProvider::observe(FundProviderObserver::class);
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
    public function register(): void {}
}
