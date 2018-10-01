<?php

namespace App\Services\MediaService;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\Product;
use App\Services\Forus\Record\Models\RecordCategory;
use Illuminate\Support\ServiceProvider;

class MediaServiceProvider extends ServiceProvider
{
    /**
     * List mediable Models
     *
     * @var array
     */
    protected $mediable_models = [
        Fund::class,
        Product::class,
        Organization::class,
        RecordCategory::class,
    ];

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('media', function () {
            return new MediaService($this->mediable_models);
        });
    }
}