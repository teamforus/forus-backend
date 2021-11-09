<?php
namespace App\Services\BunqService;

use App\Events\Funds\FundBalanceSuppliedEvent;
use App\Models\Fund;
use App\Models\FundTopUp;
use App\Models\VoucherTransaction;
use bunq\Context\BunqContext;
use bunq\Model\Generated\Endpoint\MonetaryAccount;
use bunq\Model\Generated\Endpoint\Payment;
use bunq\Model\Generated\Object\Amount;
use bunq\Model\Generated\Object\Pointer;
use bunq\Util\BunqEnumApiEnvironmentType;
use bunq\Context\ApiContext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Filesystem\Filesystem;

/**
 * Class BunqService
 * @package App\Services\BunqService
 */
class BunqService
{
    private $deviceDescription = "Device description";

    /**
     * Filesystem driver to use for storage
     * @var string $storageDriver
     */
    protected $storageDriver;

    /**
     * Path to store api contexts
     * @var string $storagePath
     */
    protected $storagePath;

    /**
     * Bunq API mode
     * @var bool
     */
    protected $bunqUseSandbox;

    /**
     * BunqService constructor.
     *
     * @param $fundId
     * @param $bunqKey
     * @param array $bunqAllowedIp
     * @param bool $bunqUseSandbox
     * @throws \Exception
     */
    public function __construct(
        $fundId,
        $bunqKey,
        $bunqAllowedIp = [],
        $bunqUseSandbox = true
    ) {
        $this->storagePath = config('bunq.storage_path');
        $this->storageDriver = config('bunq.storage_driver');
        $this->bunqUseSandbox = $bunqUseSandbox;

        if (!is_numeric($fundId) || $this->storageDriver === 'public') {
            app('log')->alert(
                'BunqService->__construct: ' .
                "can't use `public` storage for bunq context files."
            );

            abort(403);
        }

        $storage = $this->storage();

        if (!$storage->exists($this->storagePath . $fundId)) {
            $storage->makeDirectory($this->storagePath . $fundId);
        }

        $bunqContextFilePath = $this->storagePath . $fundId . '/context.json';

        $apiContext = $this->requestApiContext(
            $bunqContextFilePath,
            $bunqUseSandbox,
            $bunqKey,
            $bunqAllowedIp,
            $this->deviceDescription
        );

        if (!$apiContext) {
            throw new \RuntimeException("Can't create or restore api context.");
        }

        BunqContext::loadApiContext($apiContext);
    }

    /**
     * Create new BunqService instance.
     *
     * @param $fundId
     * @param $bunqKey
     * @param array $bunqAllowedIp
     * @param bool $bunqUseSandbox
     * @return bool|static
     */
    public static function create(
        $fundId,
        $bunqKey,
        $bunqAllowedIp = [],
        $bunqUseSandbox = true
    ) {
        try {
            return new static($fundId, $bunqKey, $bunqAllowedIp, $bunqUseSandbox);
        } catch (\Exception $exception) {
            if ($logger = logger()) {
                $logger->error($exception);
            }
        }

        return false;
    }

    /**
     * Create or restore existing bunq context.
     *
     * @param string $bunqContextFilePath
     * @param bool $useSandbox
     * @param string $apiKey
     * @param array $permittedIps
     * @param string $deviceDescription
     * @return ApiContext|boolean
     */
    private function requestApiContext(
        string $bunqContextFilePath,
        bool $useSandbox,
        string $apiKey,
        array $permittedIps,
        string $deviceDescription
    ) {
        $storage = $this->storage();

        if ($storage->exists($bunqContextFilePath)) {
            try {
                return $this->restoreContext($bunqContextFilePath);
            } catch (\Exception $exception) {
                $storage->delete($bunqContextFilePath);

                return $this->requestApiContext(
                    $bunqContextFilePath,
                    $useSandbox,
                    $apiKey,
                    $permittedIps,
                    $deviceDescription
                );
            }
        } else {
            return $this->createAndStoreContext(
                $bunqContextFilePath,
                $useSandbox,
                $apiKey,
                $permittedIps,
                $deviceDescription
            );
        }
    }

    /**
     * Restore bunq context from stored file.
     *
     * @param $bunqContextFilePath
     * @return ApiContext
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function restoreContext($bunqContextFilePath): ApiContext
    {
        return ApiContext::fromJson($this->storage()->get($bunqContextFilePath));
    }

    /**
     * Create new Bunq context and store to file.
     *
     * @param string $bunqContextFilePath
     * @param bool $useSandbox
     * @param string $apiKey
     * @param array $permittedIps
     * @param string $deviceDescription
     * @return bool|ApiContext
     */
    private function createAndStoreContext(
        string $bunqContextFilePath,
        bool $useSandbox,
        string $apiKey,
        array $permittedIps,
        string $deviceDescription
    ) {
        if ($useSandbox) {
            $environmentType = BunqEnumApiEnvironmentType::SANDBOX();
        } else {
            $environmentType = BunqEnumApiEnvironmentType::PRODUCTION();
        }

        try {
            $apiContext = ApiContext::create(
                $environmentType,
                $apiKey,
                $deviceDescription,
                $permittedIps
            );

            $this->storage()->put($bunqContextFilePath, $apiContext->toJson());

            return $apiContext;
        } catch (\Exception $exception) {
            if ($logger = logger()) {
                $logger->error('BunqService->restoreContext: ' . $exception->getMessage());
            }
        }

        return false;
    }

    /**
     * Get bank monetary account balance amount.
     *
     * @return float
     * @noinspection PhpUnused
     */
    public function getBankAccountBalanceValue(): float
    {
        $monetaryAccount = MonetaryAccount::listing()->getValue()[0];

        return (float) $monetaryAccount->getMonetaryAccountBank()->getBalance()->getValue();
    }

    /**
     * Get bank monetary account iban value
     *
     * @return string|false
     */
    public function getBankAccountIban()
    {
        $monetaryAccount = MonetaryAccount::listing()->getValue()[0];

        $alias = collect(
            $monetaryAccount->getMonetaryAccountBank()->getAlias()
        )->filter(function(Pointer $alias) {
            return $alias->getType() === 'IBAN';
        })->first();

        return $alias ? $alias->getValue() : false;
    }

    /**
     * Make new payment.
     *
     * @param string $amount
     * @param string $iban
     * @param string $name
     * @param string $description
     * @return int
     */
    public function makePayment(
        string $amount,
        string $iban,
        string $name,
        string $description = ""
    ): int {
        return Payment::create(
            new Amount(number_format($amount, 2, '.', ''), 'EUR'),
            new Pointer('IBAN', $iban, $name),
            $description
        )->getValue();
    }

    /**
     * Get payment details.
     *
     * @param int $paymentId
     * @return Payment
     */
    public function paymentDetails(int $paymentId): Payment
    {
        return Payment::get($paymentId)->getValue();
    }

    /**
     * Get all payments.
     *
     * @return array|Payment[]
     */
    public function getPayments(): array
    {
        return Payment::listing(null, [
            'count' => 100
        ])->getValue();
    }

    /**
     * Check if transaction to iban has to be skipped
     * @param string|null $iban
     * @return bool
     */
    protected static function isIbanInSkipList(?string $iban): bool
    {
        $iban = trim(strtoupper($iban ?? ''));
        $ibanSkipList = array_filter(array_map(function($ibanNumber) {
            return trim(strtoupper($ibanNumber));
        }, config('bunq.skip_iban_numbers', [])));

        return in_array($iban, $ibanSkipList, true);
    }

    /**
     * Get pending transactions query
     *
     * @return VoucherTransaction|Builder|\Illuminate\Database\Query\Builder
     */
    public static function getNextPendingTransactionsInQueueQuery()
    {
        return VoucherTransaction::query()->orderBy('updated_at', 'ASC')
            ->where('state', '=', 'pending')
            ->where('attempts', '<', 5)
            ->where(function(Builder $query) {
                $query->whereNull('transfer_at');
                $query->orWhereDate('transfer_at', '<', now());
            })
            ->where(function(Builder $query) {
                $query->whereNull('last_attempt_at');
                $query->orWhereDate('last_attempt_at', '<', now()->subHours(8));
            });
    }

    /**
     * Get next pending transaction
     *
     * @return VoucherTransaction|null
     */
    public static function getNextPendingTransactionInQueue(): ?VoucherTransaction
    {
        if (!$transaction = self::getNextPendingTransactionsInQueueQuery()->first()) {
            return null;
        }

        $enoughBalance = $transaction->voucher->fund->budget_left >= $transaction->amount;
        $skipTransaction = self::isIbanInSkipList($transaction->provider->iban);

        if ($enoughBalance || $skipTransaction) {
            $transaction->forceFill([
                'last_attempt_at' => Carbon::now(),
                'attempts' => $transaction->attempts + 1
            ])->save();
        } else {
            $transaction->forceFill([
                'last_attempt_at' => Carbon::now(),
            ])->save();
        }

        return $skipTransaction || !$enoughBalance ? null : $transaction;
    }

    /**
     * Process pending transactions.
     *
     * @return void
     */
    public static function processQueue(): void
    {
        while ($transaction = self::getNextPendingTransactionInQueue()) {
            $voucher = $transaction->voucher;

            try {
                if (!$bunq = $voucher->fund->getBunq()) {
                    if ($logger = logger()) {
                        $logger->error(sprintf(
                            'BunqService: Could not make bunq instance: %s!',
                            $transaction->id
                        ));
                    }

                    continue;
                }

                $paymentDescription = trans('bunq.transaction.from_fund', [
                    'fund_name' => $transaction->voucher->fund->name,
                    'transaction_id' => $transaction->id
                ]);

                if ($transaction->amount <= 0) {
                    $transaction->forceFill([
                        'state' => 'success',
                    ])->save();

                    continue;
                }

                $payment_id = $bunq->makePayment(
                    $transaction->amount,
                    $transaction->provider->iban,
                    $transaction->provider->name,
                    $paymentDescription
                );

                if (is_numeric($payment_id)) {
                    $transaction->forceFill([
                        'state'             => 'success',
                        'payment_id'        => $payment_id,
                        'iban_from'         => $bunq->getBankAccountIban(),
                        'iban_to'           => $transaction->provider->iban,
                        'payment_time'      => $bunq->paymentDetails($payment_id)->getCreated()
                    ])->save();

                    $transaction->sendPushBunqTransactionSuccess();

                    sleep(1);
                } else if ($logger = logger()) {
                    $logger->error(sprintf(
                        'BunqService: invalid payment_id for transaction: %s!',
                        $transaction->id
                    ));
                }
            } catch (\Exception $e) {
                if ($logger = logger()) {
                    $logger->error(
                        'BunqService->processQueue error: ' .
                        sprintf(" [%s] - %s", Carbon::now(), $e->getMessage())
                    );
                }
            }

            sleep(1);
        }
    }

    /**
     * Check for new top-ups.
     *
     * @return void
     */
    public static function processTopUps(): void
    {
        /** @var Fund $fund */
        foreach (Fund::all() as $fund) {
            if (!$fund->getBunqKey() || (!$bunq = $fund->getBunq())) {
                continue;
            }

            $payments = $bunq->getPayments();
            $topUps = $fund->top_ups()->where('fund_id', $fund->id)->get();

            /** @var FundTopUp $topUp */
            foreach ($topUps as $topUp) {
                foreach ($payments as $payment) {
                    if (strpos(
                        $payment->getDescription(),
                        $topUp->code
                        ) === FALSE) {
                        continue;
                    }

                    if ($topUp->transactions()->where([
                        'bunq_transaction_id' => $payment->getId()
                        ])->count() > 0) {
                        continue;
                    }

                    try {
                        $transaction = $topUp->transactions()->firstOrCreate([
                            'bunq_transaction_id' => $payment->getId(),
                            'amount' => $payment->getAmount()->getValue()
                        ]);

                        FundBalanceSuppliedEvent::dispatch($transaction);
                    } catch (\Exception $exception) {
                        resolve('log')->error($exception->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Get storage.
     *
     * @return Filesystem
     */
    private function storage(): Filesystem
    {
        return resolve('filesystem')->disk($this->storageDriver);
    }
}
