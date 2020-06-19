<?php
namespace App\Services\BunqService;

use App\Events\Funds\FundBalanceSuppliedEvent;
use App\Models\Fund;
use App\Models\FundTopUp;
use App\Models\VoucherTransaction;
use App\Services\BunqService\Endpoint\BunqMeTabB;
use App\Services\BunqService\Models\BunqIdealIssuer;
use bunq\Context\BunqContext;
use bunq\Model\Generated\Endpoint\BunqMeTab;
use bunq\Model\Generated\Endpoint\BunqMeTabEntry;
use bunq\Model\Generated\Endpoint\MonetaryAccount;
use bunq\Model\Generated\Endpoint\MonetaryAccountBank;
use bunq\Model\Generated\Endpoint\Payment;
use bunq\Model\Generated\Object\Amount;
use bunq\Model\Generated\Object\Pointer;
use bunq\Util\BunqEnumApiEnvironmentType;
use bunq\Context\ApiContext;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;

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

        if (!is_numeric($fundId) || $this->storageDriver == 'public') {
            if ($this->storageDriver == 'public') {
                app('log')->alert(
                    'BunqService->__construct: ' .
                    "can't use `public` storage for bunq context files."
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
            return new static(
                $fundId,
                $bunqKey,
                $bunqAllowedIp,
                $bunqUseSandbox
            );
        } catch (\Exception $exception) {
            logger()->error($exception);
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
    private function restoreContext(
        $bunqContextFilePath
    ) {
        return ApiContext::fromJson(
            $this->storage()->get($bunqContextFilePath)
        );
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

            $this->storage()->put(
                $bunqContextFilePath,
                $apiContext->toJson()
            );

            return $apiContext;
        } catch (\Exception $exception) {
            app('log')->error(
                'BunqService->restoreContext: ' . $exception->getMessage()
            );
        }

        return false;
    }

    /**
     * Get bank monetary account balance amount.
     *
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
     *
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
    ) {
        return Payment::create(
            new Amount(number_format($amount, 2, '.', ''), 'EUR'),
            new Pointer('IBAN', $iban, $name),
            $description
        )->getValue();
    }

    /**
     * Get payment details.
     *
     * @param $paymentId
     * @return Payment
     */
    public function paymentDetails(
        int $paymentId
    ) {
        return Payment::get($paymentId)->getValue();
    }

    /**
     * Get all payments.
     *
     * @return array|Payment[]
     */
    public function getPayments() {
        return Payment::listing(null, [
            'count' => 100
        ])->getValue();
    }

    /**
     * @return VoucherTransaction|Builder|\Illuminate\Database\Query\Builder
     */
    private static function getNextInTransactionsQueueQuery() {
        return VoucherTransaction::query()->orderBy('updated_at', 'ASC')
            ->where('state', '=', 'pending')
            ->where('attempts', '<', 5)
            ->where(function(Builder $query) {
                $query->whereNull('last_attempt_at')->orWhere(
                    'last_attempt_at', '<', Carbon::now()->subHours(8)
                );
            });
    }

    /**
     * Get next pending transaction
     *
     * @return VoucherTransaction|null
     */
    public static function getNextInTransactionsQueue() {
        if (!$transaction = self::getNextInTransactionsQueueQuery()->first()) {
            return $transaction;
        }

        if ($transaction->voucher->fund->budget_left >= $transaction->amount) {
            $transaction->forceFill([
                'last_attempt_at' => Carbon::now(),
                'attempts' => $transaction->attempts + 1
            ])->save();
        } else {
            $transaction->forceFill([
                'last_attempt_at' => Carbon::now(),
            ])->save();
        }

        return $transaction;
    }

    /**
     * Process pending transactions.
     *
     * @return void
     */
    public static function processQueue() {
        while ($transaction = self::getNextInTransactionsQueue()) {
            $voucher = $transaction->voucher;

            if ($voucher->fund->budget_left < $transaction->amount) {
                continue;
            }

            try {
                if (!$bunq = $voucher->fund->getBunq()) {
                    logger()->error(sprintf(
                        'BunqService: Could not make bunq instance: %s!',
                        $transaction->id
                    ));
                    continue;
                }

                $paymentDescription = trans('bunq.transaction.from_fund', [
                    'fund_name' => $transaction->voucher->fund->name,
                    'transaction_id' => $transaction->id
                ]);

                $amount = $transaction->amount;
                $voucher = $transaction->voucher;

                if ($voucher->fund->fund_config->subtract_transaction_costs) {
                    $amount = number_format($amount - .1, 2, '.', '');
                }

                $payment_id = $bunq->makePayment(
                    $amount,
                    $transaction->provider->iban,
                    $transaction->provider->name,
                    $paymentDescription
                );

                if (is_numeric($payment_id)) {
                    $transaction->forceFill([
                        'state'             => 'success',
                        'payment_id'        => $payment_id
                    ])->save();

                    $transaction->sendPushBunqTransactionSuccess();
                } else {
                    logger()->error(sprintf(
                        'BunqService: invalid payment_id for transaction: %s!',
                        $transaction->id
                    ));
                }
            } catch (\Exception $e) {
                logger()->error(
                    'BunqService->processQueue error: ' .
                    sprintf(" [%s] - %s", Carbon::now(), $e->getMessage())
                );
            }
            sleep(1);
        }
    }

    /**
     * Check for new top-ups.
     *
     * @return void
     */
    public static function processTopUps() {
        $funds = Fund::all()->filter(function(Fund $fund) {
            return $fund->getBunqKey();
        });

        /** @var Fund $fund */
        foreach ($funds as $fund) {
            if (!$bunq = $fund->getBunq()) {
                continue;
            }

            $payments = $bunq->getPayments();
            $topUps = $fund->top_ups()->where([
                'fund_id' => $fund->id
            ])->get();

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
     * Get bunq ideal issuers from db (local db cache)
     *
     * @param bool $sandbox
     * @return BunqIdealIssuer[]|Builder[]|Collection
     */
    public static function getIdealIssuers($sandbox = false) {
        return BunqIdealIssuer::query()->where([
            'sandbox' => $sandbox
        ])->get();
    }

    /**
     * Get bunq ideal issuers list from bunq API
     *
     * @param bool $sandbox
     * @return array
     * @throws \Exception
     */
    private static function _getIdealIssuers($sandbox = false): array {
        $data = (new Client())->get(
            implode('', [
                ForusBunqMeTabRequest::getApiUrl($sandbox),
                "bunqme-merchant-directory-ideal"
            ]), [
                'headers' => [
                    'X-Bunq-Client-Request-Id' => '',
                    'X-Bunq-Language' => 'en_US',
                ]
            ]
        );

        if ($data->getStatusCode() === 200) {
            try {
                return json_decode(
                    $data->getBody()->getContents()
                )->Response[0]->IdealDirectory->country[0]->issuer;
            } catch (\Exception $exception) {
                throwException(new \Exception(
                    "Error while parsing iDEAL issuers list.\n" .
                    $exception->getMessage(), 503
                ));

                return [];
            }
        }

        throw new \RuntimeException(
            "Error while parsing iDEAL issuers list.\n" .
            "Api response code: " . $data->getStatusCode(), 503
        );
    }

    /**
     * @param bool $sandbox
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public static function updateIdealIssuers(
        bool $sandbox
    ): \Illuminate\Support\Collection {
        $issuers = collect(self::_getIdealIssuers($sandbox));

        BunqIdealIssuer::query()
            ->whereNotIn('bic', $issuers->pluck('bic'))
            ->where('sandbox', $sandbox)->delete();

        return $issuers->map(function($issuer) use ($sandbox) {
            return BunqIdealIssuer::query()->firstOrCreate([
                'bic'       => $issuer->bic,
                'sandbox'   => $sandbox,
            ])->update([
                'name'      => $issuer->name,
            ]);
        });
    }

    /**
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public static function updateIdealSandboxIssuers() {
        return self::updateIdealIssuers(true);
    }

    /**
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public static function updateIdealProductionIssuers() {
        return self::updateIdealIssuers(false);
    }

    /**
     * @param float $amount
     * @param string $description
     * @return ForusBunqMeTabRequest
     */
    public function makeBunqMeTabRequest(
        float $amount,
        string $description = ''
    ) {
        $amount = number_format($amount, 2, '.', '');
        $monetaryAccount = MonetaryAccountBank::listing()->getValue()[0];

        $bunqMeTabEntryId = BunqMeTab::create(
            new BunqMeTabEntry(new Amount($amount, 'EUR'), $description),
            $monetaryAccount->getId()
        )->getValue();

        return new ForusBunqMeTabRequest(
            $this->bunqUseSandbox,
            BunqMeTab::get(
            $bunqMeTabEntryId,
            $monetaryAccount->getId()
        )->getValue());
    }

    public function getBunqMeTabRequest(
        int $bunqMeTabEntryId
    ) {
        return BunqMeTab::get(
            $bunqMeTabEntryId// ,
            // MonetaryAccountBank::listing()->getValue()[0]->getId()
        )->getValue();
    }

    /**
     * Get bunq costs.
     *
     * @param Carbon $fromDate
     * @return float|int
     * @throws \Exception
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
     * @param Fund $fund
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Database\Query\Builder
     */
    public static function getBunqMeTabsQueue(
        Fund $fund
    ) {
        return $fund->bunq_me_tabs()->whereNotIn('status', [
            'PAID', 'CANCELLED', 'EXPIRED'
        ])->where('created_at', '>', now()->subDays(5));
    }

    /**
     * @param Fund $fund
     */
    public static function processBunqMeTabQueue(
        Fund $fund
    ) {
        $queue = self::getBunqMeTabsQueue($fund)->get();

        $queue = $queue->filter(function(\App\Models\BunqMeTab $bunqMeTab) {
            if (($last_check_at = $bunqMeTab->last_check_at) == null) {
                return true;
            }

            // first 15 minutes check once per minute
            if ($bunqMeTab->created_at > now()->subMinutes(15)) {
                return $last_check_at < now()->subMinute();
            }

            // between 15 and 30 minutes check once per 5 minutes
            if ($bunqMeTab->created_at > now()->subMinutes(30)) {
                return $last_check_at < now()->subMinutes(5);
            }

            // between 30 and 60 minutes check once per 10 minutes
            if ($bunqMeTab->created_at > now()->subMinutes(60)) {
                return $last_check_at < now()->subMinutes(10);
            }

            // between 1 and 5 hours check once per 30 minutes
            if ($bunqMeTab->created_at > now()->subHours(5)) {
                return $last_check_at < now()->subMinutes(30);
            }

            // between 5 and 10 hours check once per hour
            if ($bunqMeTab->created_at > now()->subHours(10)) {
                return $last_check_at < now()->subHour();
            }

            // between 10 and 24 hours check once per 2 hours
            if ($bunqMeTab->created_at > now()->subDay()) {
                return $last_check_at < now()->subHours(2);
            }

            // between 1 and 10 days check once per day
            if ($bunqMeTab->created_at > now()->subDays(10)) {
                return $last_check_at < now()->subDay();
            }

            // between 10 and 30 days check once per 5 days
            if ($bunqMeTab->created_at > now()->subDays(30)) {
                return $last_check_at < now()->subDays(5);
            }

            // between 1 and 12 months check once per month
            if ($bunqMeTab->created_at > now()->subMonths(12)) {
                return $last_check_at < now()->subMonth();
            }

            $bunqMeTab->update([
                'status' => 'EXPIRED'
            ]);

            return false;
        });

        // load bunq context
        if ($queue->count() == 0 || !$fund->getBunq()) {
            return;
        }

        $queue->each(function (\App\Models\BunqMeTab $bunqMeTab) {
            $status = BunqMeTabB::checkStatus($bunqMeTab->bunq_me_tab_id);

            $bunqMeTab->update([
                'last_check_at' => now(),
                'status' => $status['amount_paid'] >= $bunqMeTab->amount ?
                    'PAID' : $status['status'],
            ]);

            sleep(1);
        });
    }

    /**
     * Get storage.
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    private function storage() {
        return resolve('filesystem')->disk($this->storageDriver);
    }
}
