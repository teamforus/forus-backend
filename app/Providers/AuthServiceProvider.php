<?php

namespace App\Providers;

use App\Models\BankConnection;
use App\Models\Employee;
use App\Models\FundProviderChat;
use App\Models\FundProviderChatMessage;
use App\Models\FundProviderInvitation;
use App\Models\FundProviderUnsubscribe;
use App\Models\FundRequest;
use App\Models\FundRequestClarification;
use App\Models\FundRequestRecord;
use App\Models\Implementation;
use App\Models\ImplementationPage;
use App\Models\Office;
use App\Models\FundProvider;
use App\Models\PhysicalCard;
use App\Models\PhysicalCardRequest;
use App\Models\Prevalidation;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Models\VoucherTransactionBulk;
use App\Policies\BankConnectionPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\FilePolicy;
use App\Policies\FundProviderChatMessagePolicy;
use App\Policies\FundProviderChatPolicy;
use App\Policies\FundProviderInvitationPolicy;
use App\Policies\FundProviderUnsubscribePolicy;
use App\Policies\FundRequestClarificationPolicy;
use App\Policies\FundRequestPolicy;
use App\Policies\FundRequestRecordPolicy;
use App\Policies\IdentityEmailPolicy;
use App\Policies\ImplementationPagePolicy;
use App\Policies\ImplementationPolicy;
use App\Policies\MediaPolicy;
use App\Policies\MollieConnectionPolicy;
use App\Policies\MollieConnectionProfilePolicy;
use App\Policies\PhysicalCardPolicy;
use App\Policies\PhysicalCardRequestPolicy;
use App\Policies\PrevalidationPolicy;
use App\Policies\OfficePolicy;
use App\Policies\FundProviderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ProductReservationPolicy;
use App\Policies\ReimbursementCategoryPolicy;
use App\Policies\ReimbursementPolicy;
use App\Policies\VoucherPolicy;
use App\Policies\VoucherTransactionBulkPolicy;
use App\Policies\VoucherTransactionPolicy;
use App\Services\AuthService\BearerTokenGuard;
use App\Services\AuthService\ServiceIdentityProvider;
use App\Services\FileService\Models\File;
use App\Models\IdentityEmail;
use App\Services\MediaService\Models\Media;
use App\Services\MollieService\Models\MollieConnection;
use App\Services\MollieService\Models\MollieConnectionProfile;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use App\Models\Fund;
use App\Models\Organization;
use App\Policies\FundPolicy;
use App\Policies\OrganizationPolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        File::class                     => FilePolicy::class,
        Fund::class                     => FundPolicy::class,
        Media::class                    => MediaPolicy::class,
        Office::class                   => OfficePolicy::class,
        Product::class                  => ProductPolicy::class,
        Voucher::class                  => VoucherPolicy::class,
        Employee::class                 => EmployeePolicy::class,
        FundRequest::class              => FundRequestPolicy::class,
        Organization::class             => OrganizationPolicy::class,
        FundProvider::class             => FundProviderPolicy::class,
        PhysicalCard::class             => PhysicalCardPolicy::class,
        Reimbursement::class            => ReimbursementPolicy::class,
        Prevalidation::class            => PrevalidationPolicy::class,
        IdentityEmail::class            => IdentityEmailPolicy::class,
        Implementation::class           => ImplementationPolicy::class,
        BankConnection::class           => BankConnectionPolicy::class,
        FundProviderChat::class         => FundProviderChatPolicy::class,
        MollieConnection::class         => MollieConnectionPolicy::class,
        FundRequestRecord::class        => FundRequestRecordPolicy::class,
        ImplementationPage::class       => ImplementationPagePolicy::class,
        VoucherTransaction::class       => VoucherTransactionPolicy::class,
        ProductReservation::class       => ProductReservationPolicy::class,
        PhysicalCardRequest::class      => PhysicalCardRequestPolicy::class,
        ReimbursementCategory::class    => ReimbursementCategoryPolicy::class,
        VoucherTransactionBulk::class   => VoucherTransactionBulkPolicy::class,
        FundProviderInvitation::class   => FundProviderInvitationPolicy::class,
        FundProviderChatMessage::class  => FundProviderChatMessagePolicy::class,
        FundProviderUnsubscribe::class  => FundProviderUnsubscribePolicy::class,
        MollieConnectionProfile::class  => MollieConnectionProfilePolicy::class,
        FundRequestClarification::class => FundRequestClarificationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // add custom guard provider
        Auth::provider('identity_service', function () {
            return new ServiceIdentityProvider();
        });

        // add custom guard
        Auth::extend('header', function ($app, $name, array $config) {
            return new BearerTokenGuard(Auth::createUserProvider($config['provider']));
        });

        Gate::resource('funds', FundPolicy::class, [
            'manageVouchers' => 'manageVouchers',
            'showFinances' => 'showFinances',
            'update' => 'update',
        ]);

        Gate::resource('prevalidations', PrevalidationPolicy::class, [
            'redeem' => 'redeem',
        ]);

        Gate::resource('organizations', OrganizationPolicy::class, [
            'update' => 'update',
        ]);
    }

    public function register(): void
    {
        $this->app->singleton(GateContract::class, function ($app) {
            return new \Illuminate\Auth\Access\Gate($app, function () use ($app) {
                return auth()->user() ?? null;
            });
        });

        parent::register();
    }
}
