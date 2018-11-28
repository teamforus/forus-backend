<?php
namespace App\Services\BunqService;

use App\Models\Fund;
use App\Models\FundTopUp;
use App\Models\VoucherTransaction;
use bunq\Context\BunqContext;
use bunq\Model\Generated\Endpoint\MonetaryAccount;
use bunq\Model\Generated\Endpoint\Payment;
use bunq\Model\Generated\Endpoint\RequestInquiry;
use bunq\Model\Generated\Object\Amount;
use bunq\Model\Generated\Object\Pointer;
use bunq\Util\BunqEnumApiEnvironmentType;
use bunq\Context\ApiContext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;

class BunqService
{
    private $deviceDescription = "My Device Description";

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
     * BunqService constructor.
     * @param $fundId
     * @param $bunqKey
     * @param array $bunqAllowedIp
     * @param bool $bunqUseSandbox
     * @throws \Exception
     */
    function __construct(
        $fundId,
        $bunqKey,
        $bunqAllowedIp = [],
        $bunqUseSandbox = true
    ) {

        $this->storagePath = config('bunq.storage_path');
        $this->storageDriver = config('bunq.storage_driver');

        if (!is_numeric($fundId) || $this->storageDriver == 'public') {
            if ($this->storageDriver == 'public') {
                app('log')->error(
                    'Bunq service: can\'t use `public` storage ' .
                    'for sensitive bunq files.'
                );
            }

            abort(403, '');
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
            throw new \Exception("Can't create or restore api context.");
        }

        BunqContext::loadApiContext($apiContext);
    }

    /**
     * Create bunq service instance
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
            $bunqService = new static(
                $fundId,
                $bunqKey,
                $bunqAllowedIp,
                $bunqUseSandbox
            );

            return $bunqService;
        } catch (\Exception $exception) {}

        return false;
    }

    /**
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
     * @param $bunqContextFilePath
     * @return ApiContext
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function restoreContext(
        $bunqContextFilePath
    ) {
        return ApiContext::fromJson(
            $this->storage()->get($bunqContextFilePath)
        );
    }

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

            $this->storage()->put(
                $bunqContextFilePath,
                $apiContext->toJson()
            );

            return $apiContext;
        } catch (\Exception $exception) {
            app('log')->error(
                'Bunq service: ' . $exception->getMessage()
            );
        }

        return false;
    }

    /**
     * Get bank monetary account balance value
     * @return float
     */
    public function getBankAccountBalanceValue()
    {
        $monetaryAccount = MonetaryAccount::listing()->getValue()[0];

        return floatval(
            $monetaryAccount->getMonetaryAccountBank()->getBalance()->getValue()
        );
    }

    /**
     * Get bank monetary account iban value
     * @return string|false
     */
    public function getBankAccountIban()
    {
        $monetaryAccount = MonetaryAccount::listing()->getValue()[0];

        $alias = collect(
            $monetaryAccount->getMonetaryAccountBank()->getAlias()
        )->filter(function(Pointer $alias) {
            return $alias->getType() == 'IBAN';
        })->first();

        return $alias ? $alias->getValue() : false;
    }

    /**
     * @return array|MonetaryAccount[]
     */
    public function getMonetaryAccounts()
    {
        return MonetaryAccount::listing()->getValue();
    }

    /**
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
    ) {
        return Payment::create(
            new Amount(number_format($amount, 2, '.', ''), 'EUR'),
            new Pointer('IBAN', $iban, $name),
            $description
        )->getValue();
    }

    /**
     * @param $paymentId
     * @return Payment
     */
    public function paymentDetails(
        int $paymentId
    ) {
        return Payment::get($paymentId)->getValue();
    }

    /**
     * @param $amount
     * @param $email
     * @param $name
     * @param string $description
     * @return int
     */
    public function makePaymentRequest(
        float $amount,
        string $email,
        string $name,
        string $description = ""
    ) {
        return RequestInquiry::create(
            new Amount($amount, 'EUR'),
            new Pointer('EMAIL', $email, $name),
            $description,
            true
        )->getValue();
    }

    /**
     * @param $paymentRequestId
     * @return RequestInquiry
     */
    public function paymentRequestDetails(
        int $paymentRequestId
    ) {
        return RequestInquiry::get($paymentRequestId)->getValue();
    }

    /**
     * @param $paymentRequestId
     * @return \bunq\Model\Generated\Endpoint\BunqResponseRequestInquiry
     */
    public function revokePaymentRequest(
        int $paymentRequestId
    ) {
        return RequestInquiry::update($paymentRequestId, null, 'REVOKED');
    }

    /**
     * @return array|Payment[]
     */
    public function getPayments() {
        return Payment::listing(null)->getValue();
    }

    /**
     * @return Collection
     */
    public static function getQueue() {
        return VoucherTransaction::query()->orderBy('updated_at', 'ASC')
            ->where('state', '=', 'pending')
            ->where('attempts', '<', 5)
            ->where(function($query) {
                /** @var Builder $query */
                $query
                    ->whereNull('last_attempt_at')
                    ->orWhere('last_attempt_at', '<', Carbon::now()->subHours(8));
            })->get();
    }

    public static function processQueue() {
        $transactions = self::getQueue();

        if ($transactions->count() == 0) {
            return null;
        }

        /** @var VoucherTransaction $transaction */
        foreach($transactions as $transaction) {
            $voucher = $transaction->voucher;

            if ($voucher->fund->budget_left < $transaction->amount) {
                $transaction->forceFill([
                    'last_attempt_at'   => Carbon::now(),
                ])->save();

                continue;
            }

            $transaction->forceFill([
                'attempts'          => ++$transaction->attempts,
                'last_attempt_at'   => Carbon::now(),
            ])->save();

            try {
                $bunq = $voucher->fund->getBunq();

                if (!$bunq) {
                    app('log')->error(
                        'BunqService:processQueue invalid fund bunq - ' .
                        $voucher->fund_id
                    );
                    continue;
                }

                $paymentDescription = trans('bunq.transaction.from_fund', [
                    'fund_name' => $transaction->voucher->fund->name
                ]);

                $payment_id = $bunq->makePayment(
                    $transaction->amount,
                    $transaction->organization->iban,
                    $transaction->organization->name,
                    $paymentDescription
                );

                if (is_numeric($payment_id)) {
                    $transaction->forceFill([
                        'state'             => 'success',
                        'payment_id'        => $payment_id
                    ])->save();
                }

            } catch(\Exception $e) {
                app('log')->error(sprintf(
                    "[%s] - %s",
                    Carbon::now(),
                    $e->getMessage()
                ));
            }
        }
    }

    public static function processTopUps() {
        $funds = Fund::all()->filter(function(Fund $fund) {
            return $fund->getBunqKey();
        });

        /** @var Fund $fund */
        foreach ($funds as $fund) {
            $bunq = $fund->getBunq();

            if (!$bunq) {
                app('log')->error(
                    'BunqService:processQueue invalid fund bunq - ' .
                    $fund->id
                );
                continue;
            }

            $payments = $bunq->getPayments();
            $topUps = $fund->top_ups()->where([
                'state' => 'pending',
                'fund_id' => $fund->id
            ])->get();

            /** @var FundTopUp $topUp */
            foreach ($topUps as $topUp) {
                foreach ($payments as $payment) {
                    if (strpos($payment->getDescription(), $topUp->code) !== FALSE) {
                        try {
                            $topUp->update([
                                'state' => 'confirmed',
                                'bunq_transaction_id' => $payment->getId(),
                                'amount' => $payment->getAmount()->getValue()
                            ]);
                        } catch (\Exception $exception) {};
                    }
                }
            }
        }
    }

    /**
     * Get bunq costs
     * @param Carbon $fromDate
     * @return float|int
     */
    public function getBunqCosts(
        Carbon $fromDate
    ) {
        $amount = 0;
        $amount += VoucherTransaction::query()->whereNotNull(
            'payment_id'
        )->where(
            'created_at', '>=', $fromDate->format('Y-m-d')
        )->count() * .1;
        $amount += ($fromDate->diffInMonths(new Carbon()) * 9.99);

        return $amount;
    }

    /**
     * Get storage
     * @return \Storage
     */
    private function storage() {
        return app()->make('filesystem')->disk($this->storageDriver);
    }
}