<?php

namespace App\Providers;

use App\Models\Office;
use App\Models\FundProvider;
use App\Models\Prevalidation;
use App\Models\Product;
use App\Models\ProviderIdentity;
use App\Models\Validator;
use App\Models\ValidatorRequest;
use App\Models\Voucher;
use App\Policies\MediaPolicy;
use App\Policies\PrevalidationPolicy;
use App\Policies\ProviderIdentityPolicy;
use App\Policies\ValidatorPolicy;
use App\Policies\OfficePolicy;
use App\Policies\OrganizationFundPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ValidatorRequestPolicy;
use App\Policies\VoucherPolicy;
use App\Services\AuthService\BearerTokenGuard;
use App\Services\AuthService\ServiceIdentityProvider;
use App\Services\Forus\Identity\Repositories\Interfaces\IIdentityRepo;
use App\Services\MediaService\Models\Media;
use Illuminate\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use App\Models\Fund;
use App\Models\Organization;
use App\Policies\FundPolicy;
use App\Policies\OrganizationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Fund::class                 => FundPolicy::class,
        Media::class                => MediaPolicy::class,
        Office::class               => OfficePolicy::class,
        Product::class              => ProductPolicy::class,
        Voucher::class              => VoucherPolicy::class,
        Organization::class         => OrganizationPolicy::class,
        Validator::class            => ValidatorPolicy::class,
        FundProvider::class         => OrganizationFundPolicy::class,
        Prevalidation::class        => PrevalidationPolicy::class,
        ValidatorRequest::class     => ValidatorRequestPolicy::class,
        ProviderIdentity::class     => ProviderIdentityPolicy::class,
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
    }

    public function register()
    {
        $this->app->singleton(GateContract::class, function ($app) {
            return new Gate($app, function () use ($app) {
                return auth()->user() ?? false;
            });
        });

        parent::register();
    }
}
