<?php

namespace App\Console\Commands;

use App\Models\BankConnection;
use App\Models\VoucherTransactionBulk;
use App\Services\BankService\Models\Bank;
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
        $bulks = VoucherTransactionBulk::where([
            'state' => VoucherTransactionBulk::STATE_PENDING,
        ])->whereHas('bank_connection', function(Builder $builder) {
            $builder->where('state', '!=', BankConnection::STATE_INVALID);

            $builder->whereHas('bank', function(Builder $builder) {
                $builder->where('key', Bank::BANK_BUNQ);
                $builder->orWhere(function(Builder $builder) {
                    $builder->where('key', Bank::BANK_BNG);
                    $builder->whereNotNull('access_token');
                });
            });
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
