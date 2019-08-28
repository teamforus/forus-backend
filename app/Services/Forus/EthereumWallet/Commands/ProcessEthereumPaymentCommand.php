<?php

namespace App\Services\BunqService\Commands;

use App\Services\Forus\EthereumWallet\Models\EthereumWallet;
use Illuminate\Console\Command;

class ProcessEthereumPaymentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.ethereum:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process ethereum transactions queue.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle() {
        try {
            EthereumWallet::processQueue(time());
        } catch (\Exception $e) {}
    }
}
