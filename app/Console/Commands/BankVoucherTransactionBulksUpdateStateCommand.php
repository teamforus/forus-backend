<?php

namespace App\Console\Commands;

use App\Models\BankConnection;
use App\Models\VoucherTransactionBulk;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class BankVoucherTransactionBulksUpdateStateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bank:bulks-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch bank api to update bulk transactions state.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        /** @var VoucherTransactionBulk[]|Collection $bulks */
        $bulksQuery = VoucherTransactionBulk::whereState(VoucherTransactionBulk::STATE_PENDING);
        $bulks = $bulksQuery->whereHas('bank_connection', function(Builder $builder) {
            $builder->where('state', '!=', BankConnection::STATE_INVALID);
        })->get();

        foreach ($bulks as $transactionBulk) {
            try {
                $transactionBulk->updatePaymentStatus();
            } catch (Throwable $e) {
                logger()->error($e->getMessage() . "\n" . $e->getTraceAsString());
            }
        }
    }
}
