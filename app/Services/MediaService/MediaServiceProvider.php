<?php

namespace App\Services\MediaService;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Employee;
use App\Models\Office;
use App\Models\ProductCategory;
use App\Models\Voucher;
use App\Services\FileService\Models\File;
use App\Services\Forus\Record\Models\RecordCategory;
use App\Services\MediaService\Models\Media;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class MediaServiceProvider extends ServiceProvider
{
    /**
     * List mediable Models
     *
     * @var array
     */
    protected $mediable_map = [
        'fund'              => Fund::class,
        'office'            => Office::class,
        'voucher'           => Voucher::class,
        'product'           => Product::class,
        'employees'         => Employee::class,
        'organization'      => Organization::class,
        'product_category'  => ProductCategory::class,
        'record_category'   => RecordCategory::class,
    ];

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');

        Relation::morphMap([
            'media' => Media::class,
        ]);

        resolve('router')->bind('media_uid', function ($value) {
            return Media::findByUid($value) ?? abort(404);
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('media', function () {
            return new MediaService($this->mediable_map);
        });
    }
}