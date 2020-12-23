<?php

namespace App\Console\Commands;

use App\Models\Voucher;
use Illuminate\Console\Command;

class NotifyAboutVoucherExpireCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.voucher:check-expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send vouchers expiration warning email 3 and 6 weeks before expiration.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            Voucher::checkVoucherExpireQueue(3 * 7);
            Voucher::checkVoucherExpireQueue(6 * 7);
        } catch (\Exception $e) {
            if ($logger = logger()) {
                $logger->error($e->getMessage());
            }
        }
    }
}
