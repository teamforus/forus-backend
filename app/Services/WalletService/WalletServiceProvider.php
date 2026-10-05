<?php

namespace App\Services\WalletService;

use App\Services\WalletService\Commands\WalletSessionsCleanupCommand;
use App\Services\WalletService\Models\WalletDisclosure;
use App\Services\WalletService\Models\WalletSession;
use App\Services\WalletService\Policies\WalletDisclosurePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

class WalletServiceProvider extends ServiceProvider
{
    protected $policies = [
        WalletDisclosure::class => WalletDisclosurePolicy::class,
    ];

    /**
     * @return void
     */
    public function boot(): void
    {
        Route::bind('wallet_session_uid', static function ($wallet_session_uid) {
            $sessionExpireTime = now()->subSeconds(Config::get('openid.session_expiration_seconds'));

            return WalletSession::where([
                'session_state' => WalletSession::STATE_PENDING,
                'session_uid' => $wallet_session_uid,
            ])->where('created_at', '>=', $sessionExpireTime)->firstOrFail();
        });

        $this->commands(WalletSessionsCleanupCommand::class);
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }
}
