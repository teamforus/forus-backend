<?php

namespace App\Modules\VoucherPass;

use App\Modules\VoucherPass\Models\VoucherPass;
use App\Modules\VoucherPass\Policies\VoucherPassPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class VoucherPassServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/voucher_pass.php', 'voucher_pass');
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        Gate::policy(VoucherPass::class, VoucherPassPolicy::class);

        Relation::morphMap([
            'voucher_pass' => VoucherPass::class,
        ]);

        $this->loadMigrationsFrom(__DIR__ . '/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/lang', 'voucher_pass');
    }
}
