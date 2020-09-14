<?php

namespace App\Services\MediaService;

use App\Services\MediaService\Models\Media;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

/**
 * Class MediaServiceProvider
 * @package App\Services\MediaService
 */
class MediaServiceProvider extends ServiceProvider
{
    public function boot(): void
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
    public function register(): void
    {
        $this->app->singleton('media', function () {
            return new MediaService();
        });
    }
}