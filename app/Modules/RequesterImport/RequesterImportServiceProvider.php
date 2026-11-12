<?php

namespace App\Modules\RequesterImport;

use App\Modules\RequesterImport\Models\RequesterImportRecord;
use App\Modules\RequesterImport\Models\RequesterImportRow;
use App\Modules\RequesterImport\Policies\RequesterImportRowPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class RequesterImportServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/requester_import.php', 'requester_import');
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        Gate::policy(RequesterImportRow::class, RequesterImportRowPolicy::class);

        Relation::morphMap([
            'requester_import_record' => RequesterImportRecord::class,
        ]);

        $this->loadMigrationsFrom(__DIR__ . '/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/lang', 'requester_import');
    }
}
