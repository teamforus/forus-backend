<?php

namespace App\Services\Forus\EthereumWallet;

use App\Services\BunqService\Commands\ProcessEthereumPaymentCommand;
use App\Services\Forus\EthereumWallet\Repositories\EthereumWalletRepo;
use App\Services\Forus\EthereumWallet\Repositories\Interfaces\IEthereumWalletRepo;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

/**
 * Class EthereumWalletServiceProvider
 * @package App\Services\Forus\EthereumWallet
 */
class EthereumWalletServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');

        $this->app->booted(function () {
            $schedule = app(Schedule::class);

            $schedule->command('forus.ethereum:process')
                ->everyMinute();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            ProcessEthereumPaymentCommand::class
        ]);

        $this->app->bind(IEthereumWalletRepo::class, EthereumWalletRepo::class);

        $this->app->singleton('forus.services.ethereum_wallet', function () {
            return app(IEthereumWalletRepo::class);
        });
    }
}