<?php

namespace App\Services\Forus\EthereumWallet\Traits;

use App\Services\Forus\EthereumWallet\Models\EthereumWallet;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait HasEthereumWallet
 * @property EthereumWallet $ethereum_wallet
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
     * @param Model $model
     * @param $amount
     * @return \App\Services\Forus\EthereumWallet\Models\EthereumWalletTransaction|bool
     * @throws \Exception
     */
    public function transferEtherToModel(Model $model, $amount)
    {
        /** @var EthereumWallet $walletFrom */
        $walletFrom = $this->ethereum_wallet()->first();

        if (!$walletFrom) {
            return false;
        }

        /** @var EthereumWallet $walletTo */
        $walletTo = $model->ethereum_wallet()->first();

        if (!$walletTo) {
            $walletTo = $this->createWalletInstance($model);

            if (!$walletTo) {
                return false;
            }
        }

        return $walletFrom->makeTransaction($walletTo, $amount);
    }

    /**
     * @param $walletTo
     * @param $amount
     * @return \App\Services\Forus\EthereumWallet\Models\EthereumWalletTransaction|bool
     */
    public function transferEtherToWallet($walletTo, $amount)
    {
        /** @var EthereumWallet $walletFrom */
        $walletFrom = $this->ethereum_wallet()->first();

        if (!$walletFrom) {
            return false;
        }

        return $walletFrom->makeTransaction($walletTo, $amount);
    }

    /**
     * @return EthereumWallet|bool
     * @throws \Exception
     */
    public function createWallet()
    {
        /** @var EthereumWallet $wallet */
        $wallet = $this->ethereum_wallet()->first();

        if (!$wallet) {
            /** @var Model $model */
            $model = $this;

            return $this->createWalletInstance($model);
        }

        return $wallet;
    }

    /**
     * @return null|string
     */
    public function getWalletBalance()
    {
        /** @var EthereumWallet $wallet */
        $wallet = $this->ethereum_wallet()->first();

        return $wallet ? $wallet->getWalletBalance() : null;
    }

    /**
     * @param Model $model
     * @return EthereumWallet|bool
     * @throws \Exception
     */
    private function createWalletInstance(Model $model)
    {
        return (new EthereumWallet())->createWallet($model);
    }
}