<?php

namespace App\Services\Forus\EthereumWallet\Models;

use App\Models\Fund;
use App\Models\VoucherTransaction;
use App\Services\Forus\EthereumWallet\Repositories\Interfaces\IEthereumWalletRepo;
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
        return app('forus.services.ethereum_wallet');
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
     * @param Model $walletable
     * @param $passphrase
     * @return EthereumWallet|bool
     * @throws \Exception
     */
    public function createWallet(Model $walletable, $passphrase = null)
    {
        $passphrase = $passphrase
            ? $passphrase
            : app('key_pair_generator')->make()['passphrase'];

        $wallet = $this->getWalletRepo()->createWallet($passphrase);

        if ($wallet) {
            return $walletable->ethereum_wallet()->create([
                'address' => $wallet['address'],
                'passphrase' => $passphrase,
                'private_key' => $wallet['private_key']
            ]);
        }

        return false;
    }


    /**
     * $addressTo can be instance of EthereumWallet or id or address of wallet
     * @param $addressTo
     * @param $amount
     * @return EthereumWalletTransaction|bool
     */
    public function makeTransaction($addressTo, $amount)
    {
        if (!($addressTo instanceof self)) {
            $addressTo = self::query()->where('id', $addressTo)
                ->orWhere('address', $addressTo)
                ->first();

            if (!$addressTo) {
                return false;
            }
        }

        $transaction = $this->getWalletRepo()->makeTransaction(
            $addressTo->address,
            $this->address,
            str_replace('0x', '', $this->private_key),
            $amount
        );

        if ($transaction) {
            return EthereumWalletTransaction::create([
                'hash' => $transaction['transactionHash'] ?? '',
                'block_hash' => $transaction['blockHash'] ?? '',
                'block_number' => $transaction['blockNumber'] ?? '',
                'amount' => $amount,
                'gas' => $transaction['gasUsed'] ?? '',
                'wallet_from_id' => $this->id,
                'wallet_to_id' => $addressTo->id
            ]);
        }

        return false;
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
     * @return void
     */
    public static function processQueue() {
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
                $walletTo = $identityRepo->getEthereumWallet($transaction->provider->identity_address, true);

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
    }

}
