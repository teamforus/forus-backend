<?php

namespace App\Console\Commands;

use App\Events\Funds\FundBalanceSuppliedEvent;
use App\Models\BankConnection;
use App\Models\Fund;
use App\Models\FundTopUp;
use App\Models\Organization;
use App\Scopes\Builders\FundQuery;
use App\Services\BankService\Values\BankPayment;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
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
    protected int $fetchInterval = 5;

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->processTopUps();
        $this->processBankConnectionBalance();
    }

    /**
     * @return void
     */
    public function processBankConnectionBalance()
    {
        $organizations = Organization::whereHas('funds', function(Builder $builder) {
            $balanceProvider = Fund::BALANCE_PROVIDER_BANK_CONNECTION;
            FundQuery::whereTopUpAndBalanceUpdateAvailable($builder, $balanceProvider);
        })->get();

        foreach ($organizations as $organization) {
            $organization->updateFundBalancesByBankConnection();
            sleep($this->fetchInterval);
        }
    }

    /**
     * @throws Throwable
     */
    public function processTopUps(): void
    {
        $balanceProvider = Fund::BALANCE_PROVIDER_TOP_UPS;
        $funds = FundQuery::whereTopUpAndBalanceUpdateAvailable(Fund::query(), $balanceProvider)->get();

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
        $bankConnection = $fund->organization->bank_connection_active;
        $payments = $bankConnection->fetchPayments();

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
        if (strpos(strtolower($payment->getDescription()), strtolower($topUp->code)) === FALSE) {
            return;
        }

        if ($topUp->transactions()->where('bank_transaction_id', $payment->getId())->exists()) {
            return;
        }

        try {
            $this->applyTopUp($payment, $topUp, $connection);
        } catch (Throwable $e) {
            resolve('log')->error($e->getMessage());
        }
    }

    /**
     * @param BankPayment $payment
     * @param FundTopUp $topUp
     * @param BankConnection $connection
     */
    protected function applyTopUp(BankPayment $payment, FundTopUp $topUp, BankConnection $connection): void
    {
        $transaction = $topUp->transactions()->firstOrCreate([
            'bank_transaction_id' => $payment->getId(),
            'amount' => $payment->getAmount()
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
}
