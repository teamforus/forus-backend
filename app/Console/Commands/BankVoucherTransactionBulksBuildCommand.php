<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Models\VoucherTransactionBulk;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class BankVoucherTransactionBulksBuildCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bank:bulks-build';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build voucher transaction bulks.';

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Throwable
     */
    public function handle(): void
    {
        /** @var Organization[]|Collection $sponsors */
        $sponsors = Organization::whereHas('funds', function(Builder $builder) {
            $builder->whereHas('voucher_transactions', function(Builder $builder) {
                $this->addAvailableForBulkConstraints($builder);
            });
        })->whereHas('bank_connection_active')->get();

        foreach ($sponsors as $sponsor) {
            $query = VoucherTransaction::whereHas('voucher', function(Builder $builder) use ($sponsor) {
                $builder->whereHas('fund', function(Builder $builder) use ($sponsor) {
                    $builder->where('funds.organization_id', $sponsor->id);
                });
            });

            $query = $this->addAvailableForBulkConstraints($query);

            if ((clone($query))->exists()) {
                /** @var VoucherTransactionBulk $transactionsBulk */
                $transactionsBulk = $sponsor->bank_connection_active->voucher_transaction_bulks()->create([
                    'state' => VoucherTransactionBulk::STATE_PENDING,
                ]);

                $query->update([
                    'voucher_transaction_bulk_id' => $transactionsBulk->id,
                ]);

                try {
                    $transactionsBulk->submitBulk();
                } catch (Throwable $e) {
                    logger()->error($e->getMessage() . "\n" . $e->getTraceAsString());
                }
            }
        }
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public function addAvailableForBulkConstraints(Builder $builder): Builder
    {
        $builder->where('voucher_transactions.state', VoucherTransaction::STATE_PENDING);
        $builder->whereNull('voucher_transaction_bulk_id');

        $builder->whereDoesntHave('provider', function(Builder $builder) {
            $builder->whereIn('iban', config('bunq.skip_iban_numbers'));
        });

        return $builder->where(function(Builder $query) {
            $query->whereNull('transfer_at');
            $query->orWhereDate('transfer_at', '<', now());
        });
    }
}
