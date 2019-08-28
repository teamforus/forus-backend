<?php

namespace App\Services\Forus\EthereumWallet\Repositories;

use App\Services\Forus\EthereumWallet\Repositories\Interfaces\IEthereumWalletRepo;
use Psr\Http\Message\ResponseInterface;

/**
 * Class EthereumWalletRepo
 * @package App\Services\Forus\EthereumWallet\Repositories
 */
class EthereumWalletRepo implements IEthereumWalletRepo
{
    private $serviceUrl;
    private $apiRequest;

    private $loggerTag = "Ethereum service: ";

    /**
     * EthereumWalletRepo constructor.
     */
    public function __construct() {
        $this->serviceUrl = config('forus.ethereum.url');
        $this->apiRequest = resolve('api_request');
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
            $this->getApiUrl("accounts"),
            ["passphrase" => $passphrase],
            $this->getAuthHeaders()
        );

        if ($res->getStatusCode() != 201) {
            return $this->logError($res, "Error while creating wallet");
        }

        $account = json_decode($res->getBody(), true)['account'];

        return array_merge(array_only($account, [
            'address'
        ]), [
            'private_key' => $account['privateKey']
        ]);
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
            $this->getApiUrl("transactions"),
            [
                "transaction" => [
                    "from" => $fromAddress,
                    "to" => $targetAddress,
                    "value" => $amount
                ],
                "private" => $secret
            ],
            $this->getAuthHeaders()
        );

        if ($res->getStatusCode() != 200) {
            return $this->logError($res, "Error while making transaction");
        }

        return json_decode($res->getBody(), true)['account'] ?? false;
    }

    /**
     * Get address balance
     * @param string $address
     * @return float|null
     */
    public function getBalance(
        string $address
    ) {
        $res = $this->apiRequest->get(
            $this->getApiUrl("accounts/$address"),
            [],
            $this->getAuthHeaders()
        );

        if ($res->getStatusCode() != 200) {
            return $this->logError($res, "Error while getting balance");
        }

        return json_decode($res->getBody(), true)['balance'] ?? null;
    }

    /**
     * @param string $endpoint
     * @return string
     */
    private function getApiUrl(string $endpoint = '') {
        return $this->serviceUrl . $endpoint;
    }

    /**
     * @return array
     */
    private function getAuthHeaders() {
        return [
            'Api-Key' => config('forus.ethereum.api_key')
        ];
    }

    /**
     * @param ResponseInterface $res
     * @param string $message
     * @param null $return
     * @return mixed|null
     */
    private function logError(
        ResponseInterface $res,
        string $message,
        $return = null
    ) {
        logger()->error(sprintf(
            "%s: %s \nResponse code: %s\n Response body: %s",
            $this->loggerTag,
            $message,
            $res->getStatusCode(),
            json_encode(json_decode($res->getBody()), JSON_PRETTY_PRINT)
        ));

        return $return;
    }
}