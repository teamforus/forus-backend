<?php

namespace App\Services\Forus\EthereumWallet\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class IdentityService
 * @package App\Services\Forus\Identities\Facades
 */
class EthereumWalletService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'forus.services.ethereum_wallet';
    }
}