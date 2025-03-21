<?php

namespace App\Console\Commands;

use App\Events\Funds\FundBalanceSuppliedEvent;
use App\Models\BankConnection;
use App\Models\Fund;
use App\Models\FundTopUp;
use App\Scopes\Builders\FundQuery;
use App\Services\BankService\Values\BankPayment;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BankProcessFundTopUpsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bank:process-top-ups {--fund=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch bank transactions to check for top-ups.';

    /**
     * @var int Seconds to wait until next request to the API
     */
    protected int $fetchInterval = 5;

    /**
     * Execute the console command.
     *
     * @throws Throwable
     * @return void
     */
    public function handle(): void
    {
        $this->processTopUps();
    }

    /**
     * @throws Throwable
     */
    public function processTopUps(): void
    {
        $fundId = $this->option('fund');
        $funds = $fundId ? Fund::whereKey($fundId) : Fund::query();

        $funds->whereHas('organization', fn (Builder $b) => $b->whereHas('bank_connection_active'));
        $funds->whereHas('top_ups');

        FundQuery::whereIsInternal($funds);
        FundQuery::whereIsConfiguredByForus($funds);

        foreach ($funds->get() as $fund) {
            DB::transaction(function () use ($fund) {
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
        $bankConnection = $fund->organization->bank_connection_active;
        $payments = $bankConnection->fetchPayments();

        if (!$payments) {
            $this->info("Could not fetch top-ups for \"$fund->name\" fund.");

            return;
        }

        foreach ($fund->top_ups as $topUp) {
            foreach ($payments as $payment) {
                $this->processPayment($payment, $topUp, $bankConnection);
            }
        }
    }

    /**
     * @param BankPayment $payment
     * @param FundTopUp $topUp
     * @param BankConnection $connection
     */
    protected function processPayment(
        BankPayment $payment,
        FundTopUp $topUp,
        BankConnection $connection
    ): void {
        if (!str_contains(strtolower($payment->getDescription()), strtolower($topUp->code))) {
            return;
        }

        if ($topUp->transactions()->where('bank_transaction_id', $payment->getId())->exists()) {
            return;
        }

        try {
            $this->applyTopUp($payment, $topUp, $connection);
        } catch (Throwable $e) {
            Log::channel($connection->bank->isBNG() ? 'bng' : 'bunq')->error($e->getMessage());
        }
    }

    /**
     * @param BankPayment $payment
     * @param FundTopUp $topUp
     * @param BankConnection $connection
     */
    protected function applyTopUp(BankPayment $payment, FundTopUp $topUp, BankConnection $connection): void
    {
        if ($connection->bank->isBNG() && $this->shouldSkipBNG($payment, $topUp)) {
            $topUp->transactions()->firstOrCreate([
                'bank_transaction_id' => $payment->getId(),
                'amount' => null,
            ]);

            return;
        }

        $transaction = $topUp->transactions()->firstOrCreate([
            'bank_transaction_id' => $payment->getId(),
            'amount' => $payment->getAmount(),
        ]);

        $transaction->update($connection->only('bank_connection_account_id'));
        FundBalanceSuppliedEvent::dispatch($topUp->fund, $transaction);

        $this->info(sprintf(
            "A new top-up payment found and processed: \nID: %s\nAMOUNT: %s %s\nDESCRIPTION: %s\n",
            $payment->getId(),
            $payment->getCurrency(),
            $payment->getAmount(),
            $payment->getDescription()
        ));
    }

    /**
     * @param BankPayment $payment
     * @param FundTopUp $topUp
     * @return bool
     */
    private function shouldSkipBNG(BankPayment $payment, FundTopUp $topUp): bool
    {
        $query = $topUp->fund->top_up_transactions()
            ->whereRelation('fund_top_up', 'code', $topUp->code)
            ->where('fund_top_up_transactions.created_at', '>=', now()->subHours(48));

        $topUpExists = (clone $query)->where('amount', $payment->getAmount())->exists();
        $doubleExists = (clone $query)->whereNull('amount')->exists();

        return $topUpExists && !$doubleExists;
    }
}
