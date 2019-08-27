<?php

namespace App\Services\Forus\EthereumWallet\Repositories\Interfaces;

/**
 * Interface IEthereumWalletRepo
 * @package App\Services\Forus\EthereumWallet\Repositories\Interfaces
 */
interface IEthereumWalletRepo {
    /**
     * Create wallet
     * @param string $passphrase
     * @return array|boolean
     */
    public function createWallet(
        string $passphrase
    );

    /**
     * Make transaction
     * @param string $targetAddress
     * @param string $fromAddress
     * @param string $secret
     * @param string $amount
     * @return array
     */
    public function makeTransaction(
        string $targetAddress,
        string $fromAddress,
        string $secret,
        string $amount
    );

    /**
     * Create wallet
     * @param string $address
     * @return float|null
     */
    public function getBalance(
        string $address
    );
}
