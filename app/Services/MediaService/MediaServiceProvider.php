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
     * List mediable models
     *
     * @var array
     */
    protected $mediable_models = [
        Fund::class,
        Product::class,
        Organization::class,
        RecordCategory::class,
    ];

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