<?php

namespace App\Services\MollieService;

use App\Services\MollieService\Models\MollieConnection;
use App\Services\MollieService\Models\MollieConnectionProfile;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class MollieServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');

        Relation::morphMap([
            'mollie_connection' => MollieConnection::class,
            'mollie_connection_profile' => MollieConnectionProfile::class,
        ]);
    }
}