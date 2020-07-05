<?php

namespace App\Console\Commands;

use App\Services\BunqService\BunqService;
use Illuminate\Console\Command;

class UpdateVoucherTransactionDetailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.digest.voucher_transactions:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates voucher transaction details';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        BunqService::updateVoucherTransactions();
    }
}
