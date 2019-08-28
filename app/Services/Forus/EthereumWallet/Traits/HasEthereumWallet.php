<?php

namespace App\Services\Forus\EthereumWallet\Traits;

use Illuminate\Database\Eloquent\Model;
use App\Services\Forus\EthereumWallet\Models\EthereumWallet;

/**
 * @property EthereumWallet $ethereum_wallet
 * @uses \App\Models\Model
 * @package App\Models\Traits
 */
trait HasEthereumWallet
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function ethereum_wallet()
    {
        return $this->morphOne(EthereumWallet::class, 'walletable');
    }

    /**
     * @param Model|HasEthereumWallet $model
     * @param $amount
     * @return \App\Services\Forus\EthereumWallet\Models\EthereumWalletTransaction|bool
     * @throws \Exception
     */
    public function transferEtherToModel(Model $model, $amount)
    {
        $walletFrom = $this->ethereum_wallet;

        if (!$walletFrom) {
            return false;
        }

        /** @var EthereumWallet $walletTo */
        $walletTo = $model->ethereum_wallet;

        if (!$walletTo) {
            $walletTo = $this->createWalletInstance($model);

            if (!$walletTo) {
                return false;
            }
        }

        return $walletFrom->makeTransaction($walletTo, $amount);
    }

    /**
     * @param $address
     * @param $amount
     * @return \App\Services\Forus\EthereumWallet\Models\EthereumWalletTransaction|bool
     */
    public function transferEtherToAddress($address, $amount)
    {
        if ($this->ethereum_wallet) {
            return $this->ethereum_wallet->makeTransaction($address, $amount);
        }

        return false;
    }

    /**
     * @return EthereumWallet|bool
     * @throws \Exception
     */
    public function getWallet()
    {
        return $this->ethereum_wallet ?: $this->createWallet($this);
    }

    /**
     * @return EthereumWallet|bool
     * @throws \Exception
     */
    public function createWallet()
    {
        return $this->ethereum_wallet ? false :
            $this->createWalletInstance($this);
    }

    /**
     * @return float|null
     */
    public function getWalletBalance()
    {
        return $this->ethereum_wallet ?
            $this->ethereum_wallet->getWalletBalance() : null;
    }

    /**
     * @param Model|HasEthereumWallet $model
     * @return EthereumWallet|bool
     * @throws \Exception
     */
    private function createWalletInstance(Model $model)
    {
        return (new EthereumWallet())->createWallet($model);
    }
}