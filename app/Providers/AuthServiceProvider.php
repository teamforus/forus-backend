<?php

namespace App\Providers;

use App\Models\BunqMeTab;
use App\Models\Employee;
use App\Models\FundProviderChat;
use App\Models\FundProviderChatMessage;
use App\Models\FundProviderInvitation;
use App\Models\FundRequest;
use App\Models\FundRequestClarification;
use App\Models\FundRequestRecord;
use App\Models\Implementation;
use App\Models\Office;
use App\Models\FundProvider;
use App\Models\PhysicalCard;
use App\Models\PhysicalCardRequest;
use App\Models\Prevalidation;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Policies\BunqMeTabPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\FilePolicy;
use App\Policies\FundProviderChatMessagePolicy;
use App\Policies\FundProviderChatPolicy;
use App\Policies\FundProviderInvitationPolicy;
use App\Policies\FundRequestClarificationPolicy;
use App\Policies\FundRequestPolicy;
use App\Policies\FundRequestRecordPolicy;
use App\Policies\ImplementationPolicy;
use App\Policies\MediaPolicy;
use App\Policies\PhysicalCardPolicy;
use App\Policies\PhysicalCardRequestPolicy;
use App\Policies\PrevalidationPolicy;
use App\Policies\OfficePolicy;
use App\Policies\FundProviderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\VoucherPolicy;
use App\Policies\VoucherTransactionPolicy;
use App\Services\AuthService\BearerTokenGuard;
use App\Services\AuthService\ServiceIdentityProvider;
use App\Services\FileService\Models\File;
use App\Services\Forus\Identity\Repositories\Interfaces\IIdentityRepo;
use App\Services\MediaService\Models\Media;
use Illuminate\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use App\Models\Fund;
use App\Models\Organization;
use App\Policies\FundPolicy;
use App\Policies\OrganizationPolicy;
use Illuminate\Support\Facades\Auth;
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
        BunqMeTab::class                => BunqMeTabPolicy::class,
        FundRequest::class              => FundRequestPolicy::class,
        Organization::class             => OrganizationPolicy::class,
        FundProvider::class             => FundProviderPolicy::class,
        PhysicalCard::class             => PhysicalCardPolicy::class,
        Prevalidation::class            => PrevalidationPolicy::class,
        Implementation::class           => ImplementationPolicy::class,
        FundProviderChat::class         => FundProviderChatPolicy::class,
        FundRequestRecord::class        => FundRequestRecordPolicy::class,
        VoucherTransaction::class       => VoucherTransactionPolicy::class,
        PhysicalCardRequest::class      => PhysicalCardRequestPolicy::class,
        FundProviderInvitation::class   => FundProviderInvitationPolicy::class,
        FundProviderChatMessage::class  => FundProviderChatMessagePolicy::class,
        FundRequestClarification::class => FundRequestClarificationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // add custom guard provider
        Auth::provider('identity_service', function ($app, array $config) {
            return new ServiceIdentityProvider(app()->make(IIdentityRepo::class));
        });

        // add custom guard
        Auth::extend('header', function ($app, $name, array $config) {
            return new BearerTokenGuard(Auth::createUserProvider($config['provider']), app()->make('request'));
        });

        \Gate::resource('funds', FundPolicy::class, [
            'manageVouchers' => 'manageVouchers',
            'showFinances' => 'showFinances',
            'update' => 'update',
        ]);

        \Gate::resource('prevalidations', PrevalidationPolicy::class, [
            'redeem' => 'redeem',
        ]);

        \Gate::resource('organizations', OrganizationPolicy::class, [
            'update' => 'update',
        ]);
    }

    public function register()
    {
        $this->app->singleton(GateContract::class, function ($app) {
            return new Gate($app, function () use ($app) {
                return auth_user() ?? false;
            });
        });

        parent::register();
    }
}
