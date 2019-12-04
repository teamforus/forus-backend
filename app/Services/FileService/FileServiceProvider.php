<?php

namespace App\Services\FileService;

use App\Services\FileService\Models\File;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class FileServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');

        Relation::morphMap([
            'media' => File::class,
        ]);

        resolve('router')->bind('file_uid', function ($value) {
            return File::findByUid($value) ?? abort(404);
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('file', function () {
            return new FileService();
        });
    }
}
