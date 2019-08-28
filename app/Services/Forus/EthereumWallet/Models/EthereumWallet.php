<?php

namespace App\Services\Forus\EthereumWallet\Models;

use App\Models\Fund;
use App\Models\Traits\EloquentModel;
use App\Models\VoucherTransaction;
use App\Services\Forus\EthereumWallet\Repositories\Interfaces\IEthereumWalletRepo;
use App\Services\Forus\EthereumWallet\Traits\HasEthereumWallet;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Identity
 * @property mixed                                          $id
 * @property string                                         $address
 * @property string                                         $private_key
 * @property string                                         $passphrase
 * @property EthereumWalletTransaction[]|Collection         $transactions_from
 * @property EthereumWalletTransaction[]|Collection         $transactions_to
 * @property Carbon                                         $created_at
 * @property Carbon                                         $updated_at
 * @package App\Models
 */
class EthereumWallet extends Model
{
    use EloquentModel;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'address', 'passphrase', 'private_key', 'walletable_type',
        'walletable_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function walletable()
    {
        return $this->morphTo();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions_from()
    {
        return $this->hasMany(EthereumWalletTransaction::class, 'wallet_from_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions_to()
    {
        return $this->hasMany(EthereumWalletTransaction::class, 'wallet_to_id');
    }

    /**
     * @return IEthereumWalletRepo
     */
    public function getWalletRepo()
    {
        return resolve('forus.services.ethereum_wallet');
    }

    /**
     * @return array
     */
    public function getPublic()
    {
        return [
            'address' => $this->address,
            'balance' => $this->getWalletBalance()
        ];
    }

    /**
     * @return float|null
     */
    public function getWalletBalance()
    {
        return $this->getWalletRepo()->getBalance($this->address);
    }

    /**
     * @param Model|HasEthereumWallet $walletable
     * @param $passphrase
     * @return EthereumWallet|bool
     * @throws \Exception
     */
    public function createWallet(Model $walletable, $passphrase = null)
    {
        $passphrase = $passphrase ?: resolve('token_generator')->generate(32);
        $wallet = $this->getWalletRepo()->createWallet($passphrase);

        if ($wallet) {
            $walletable->ethereum_wallet()->create([
                'address' => $wallet['address'],
                'passphrase' => $passphrase,
                'private_key' => $wallet['private_key']
            ]);
            $walletable->load('ethereum_wallet');

            return $walletable->ethereum_wallet;
        }

        return false;
    }

    /**
     * Transfer $amount ether to $address
     *
     * @param string $address
     * @param float $amount
     * @return EthereumWalletTransaction|bool
     */
    public function makeTransaction(string $address, float $amount)
    {
        $amount = currency_format($amount, 5);

        if (!$transaction = $this->getWalletRepo()->makeTransaction(
            $address,
            $this->address,
            substr($this->private_key, 2),
            $amount
        )) {
            return false;
        };

        return EthereumWalletTransaction::create([
            'hash'                  => $transaction['transactionHash'] ?? '',
            'block_hash'            => $transaction['blockHash'] ?? '',
            'block_number'          => $transaction['blockNumber'] ?? '',
            'amount'                => $amount,
            'gas'                   => $transaction['gasUsed'] ?? '',
            'wallet_from_address'   => $this->address,
            'wallet_to_address'     => $address,
            "raw"                   => json_encode($transaction),
        ]);
    }

    /**
     * Get list pending transactions.
     *
     * @return Collection
     */
    public static function getTransactionsQueue() {
        return VoucherTransaction::query()->orderBy('updated_at', 'ASC')
            ->where('state', '=', 'pending')
            ->where('attempts', '<', 5)
            ->whereHas('voucher.fund', function (Builder $query) {
                $query->where('currency', Fund::CURRENCY_ETHER);
            })
            ->where(function($query) {
                /** @var Builder $query */
                $query
                    ->whereNull('last_attempt_at')
                    ->orWhere('last_attempt_at', '<', Carbon::now()->subHours(8));
            })->get();
    }

    /**
     * Process pending transactions.
     *
     * @param int $time
     * @return void
     */
    public static function processQueue(int $time) {
        $transactions = self::getTransactionsQueue();

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
                $voucherWallet = $transaction->voucher->ethereum_wallet;

                $identityRepo = app()->make('forus.services.identity');
                $walletTo = $identityRepo->getEthereumWallet($transaction->provider->identity_address);

                if (!$walletTo || !$voucherWallet) {
                    continue;
                }

                $res = $voucherWallet->makeTransaction($walletTo->address, $transaction->amount);

                if ($res) {
                    $transaction->forceFill([
                        'state'             => 'success',
                        'payment_id'        => $res->id
                    ])->save();

                    //$transaction->sendPushBunqTransactionSuccess();
                }

            } catch (\Exception $e) {
                app('log')->error(
                    'Ethereum payment->processQueue: ' .
                    sprintf(" [%s] - %s", Carbon::now(), $e->getMessage())
                );
            }
        }

        sleep(1);

        if (time() - $time < 59) {
            self::processQueue($time);
        }
    }
}
