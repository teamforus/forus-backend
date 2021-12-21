<?php

namespace App\Console\Commands;

use A\B;
use App\Events\Funds\FundBalanceSuppliedEvent;
use App\Models\Fund;
use App\Models\FundTopUp;
use App\Scopes\Builders\FundQuery;
use bunq\Model\Generated\Endpoint\Payment;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class BankProcessFundTopUpsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bank:process-top-ups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch bank transactions to check for top-ups.';

    /**
     * @var int Seconds to wait until next request to the API
     */
    protected $fetchInterval = 1;

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws Throwable
     */
    public function handle(): void
    {
        $funds = $this->getFunds();

        foreach ($funds as $fund) {
            DB::transaction(function() use ($fund) {
                $this->processFund($fund);
                sleep($this->fetchInterval);
            });
        }
    }

    /**
     * @param Fund $fund
     */
    public function processFund(Fund $fund): void
    {
        $payments = $fund->organization->bank_connection_active->fetchPayments();

        foreach ($fund->top_ups as $topUp) {
            foreach ($payments as $payment) {
                $this->processPayment($payment, $topUp);
            }
        }
    }

    /**
     * @param Payment $payment
     * @param FundTopUp $topUp
     */
    protected function processPayment(Payment $payment, FundTopUp $topUp): void
    {
        if (strpos(strtolower($payment->getDescription()), strtolower($topUp->code)) === FALSE) {
            return;
        }

        if ($topUp->transactions()->where('bunq_transaction_id', $payment->getId())->exists()) {
            return;
        }

        try {
            $this->applyTopUp($payment, $topUp);
        } catch (Throwable $exception) {
            resolve('log')->error($exception->getMessage());
        }
    }

    /**
     * @param Payment $payment
     * @param FundTopUp $topUp
     */
    protected function applyTopUp(Payment $payment, FundTopUp $topUp): void
    {
        $amount = $payment->getAmount();
        $transaction = $topUp->transactions()->firstOrCreate([
            'bunq_transaction_id' => $payment->getId(),
            'amount' => $amount->getValue()
        ]);

        FundBalanceSuppliedEvent::dispatch($transaction);

        $this->info(sprintf(
            "A new top-up payment found and processed: \nID: %s\nAMOUNT: %s\nDESCRIPTION: %s\n",
            $payment->getId(),
            $amount->getCurrency() . ' ' . number_format($amount->getValue()),
            $payment->getDescription()
        ));
    }

    /**
     * @return Collection|Fund[]
     */
    public function getFunds(): Collection
    {
        return Fund::whereHas('organization', function(Builder $builder) {
            $builder->whereHas('bank_connection_active');
        })->where(function(Builder $builder) {
            FundQuery::whereIsInternal($builder);
            FundQuery::whereIsConfiguredByForus($builder);
        })->whereHas('top_ups')->get();
    }
}
