<?php

namespace App\Services\KeyPairGeneratorService;

/**
 * Class KeyPairGenerator
 * @package App\Services\KeyPairGenerator
 */
class KeyPairGenerator
{
    /**
     * @return array|null
     * @throws \Exception
     */
    public function make()
    {
        $command = "cd " . storage_path('/bash/');
        $command .= "; ./ethereum-wallet-generator.sh;";

        try {
            $wallet = json_decode(shell_exec($command));

            if ($wallet->address ==
                '0xdcc703c0E500B653Ca82273B7BFAd8045D85a470') {
                throw new \Exception('Address is empty');
            }

            if (env("ETH_ADDRESS_SIMULATE")){
                $wallet->address = app()->make('token_generator')->address();
            }
        } catch (\Exception $e) {
            return null;
        }

        $wallet->passphrase = app('token_generator')->generate(32);

        return (array) $wallet;
    }
}