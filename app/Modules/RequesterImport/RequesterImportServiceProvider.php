<?php

namespace App\Modules\RequesterImport;

use App\Modules\RequesterImport\Models\RequesterImportRow;
use App\Modules\RequesterImport\Policies\RequesterImportRowPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class RequesterImportServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot(): void
    {
        Gate::policy(RequesterImportRow::class, RequesterImportRowPolicy::class);

        $this->loadMigrationsFrom(__DIR__ . '/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/lang', 'requester_import');
    }
}
