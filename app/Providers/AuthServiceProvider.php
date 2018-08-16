<?php

namespace App\Providers;

use App\Models\Office;
use App\Models\FundProvider;
use App\Models\Prevalidation;
use App\Models\Product;
use App\Models\Validator;
use App\Models\ValidatorRequest;
use App\Policies\MediaPolicy;
use App\Policies\PrevalidationPolicy;
use App\Policies\ValidatorPolicy;
use App\Policies\OfficePolicy;
use App\Policies\OrganizationFundPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ValidatorRequestPolicy;
use App\Services\MediaService\Models\Media;
use Illuminate\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use App\Models\Fund;
use App\Models\Organization;
use App\Policies\FundPolicy;
use App\Policies\OrganizationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

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
        Organization::class         => OrganizationPolicy::class,
        Validator::class            => ValidatorPolicy::class,
        FundProvider::class         => OrganizationFundPolicy::class,
        Prevalidation::class        => PrevalidationPolicy::class,
        ValidatorRequest::class     => ValidatorRequestPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }

    public function register()
    {
        $this->app->singleton(GateContract::class, function ($app) {
            return new Gate($app, function () use ($app) {
                return request()->get('identity', false);
            });
        });

        parent::register();
    }
}
