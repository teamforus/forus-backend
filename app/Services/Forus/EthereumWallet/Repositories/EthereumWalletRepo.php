<?php

namespace App\Services\Forus\EthereumWallet\Repositories;

use App\Services\ApiRequestService\ApiRequest;
use App\Services\Forus\EthereumWallet\Repositories\Interfaces\IEthereumWalletRepo;

/**
 * Class EthereumWalletRepo
 * @package App\Services\Forus\EthereumWallet\Repositories
 */
class EthereumWalletRepo implements IEthereumWalletRepo
{
    private $serviceUrl;

    /** @var ApiRequest $apiRequest  */
    private $apiRequest;

    /**
     * EthereumWalletRepo constructor.
     */
    public function __construct() {
        $this->serviceUrl = config('forus.ethereum.url');
        $this->apiRequest = app()->make('api_request');
    }

    /**
     * Create wallet
     * @param string $passphrase
     * @return array|boolean
     */
    public function createWallet(
        string $passphrase
    ) {
        $res = $this->apiRequest->post(
            $this->serviceUrl . "accounts",
            ["passphrase" => $passphrase],
            ['Api-Key' => config('forus.ethereum.api_key')]
        );

        if (!in_array($res->getStatusCode(), [200, 201])) {
            app()->make('log')->error(
                sprintf(
                    'Error create wallet %s',
                    $res->getBody()
                )
            );

            return false;
        }

        $response = json_decode($res->getBody(), true);

        if (isset($response['account'])) {
            return [
                'address' => $response['account']['address'],
                'private_key' => $response['account']['privateKey'] ?? '',
            ];
        }

        return false;
    }

    /**
     * Make transaction
     * @param string $targetAddress
     * @param string $fromAddress
     * @param string $secret
     * @param string $amount
     * @return array|boolean
     */
    public function makeTransaction(
        string $targetAddress,
        string $fromAddress,
        string $secret,
        string $amount
    ) {
        $res = $this->apiRequest->post(
            $this->serviceUrl . "transactions",
            [
                "transaction" => [
                    "from" => $fromAddress,
                    "to" => $targetAddress,
                    "amount" => $amount
                ],
                "private" => $secret
            ],
            ['Api-Key' => config('forus.ethereum.api_key')]
        );

        if (!in_array($res->getStatusCode(), [200, 201])) {
            app()->make('log')->error(
                sprintf(
                    'Error transfer ether %s',
                    $res->getBody()
                )
            );

            return false;
        }

        $response = json_decode($res->getBody(), true);

        return isset($response['account'])
            ? $response['account']
            : false;
    }

    /**
     * Create wallet
     * @param string $address
     * @return float|null
     */
    public function getBalance(
        string $address
    ) {
        $res = $this->apiRequest->get(
            $this->serviceUrl . "accounts/$address",
            [],
            ['Api-Key' => config('forus.ethereum.api_key')]
        );

        if (!in_array($res->getStatusCode(), [200, 201])) {
            app()->make('log')->error(
                sprintf(
                    'Error get wallet balance %s',
                    $res->getBody()
                )
            );

            return null;
        }

        $response = json_decode($res->getBody(), true);

        return isset($response['balance']) ? $response['balance'] : null;
    }
}