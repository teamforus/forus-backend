<?php

namespace App\Services\SAML2Service\Exceptions;

use Exception;
use Throwable;

class Saml2Exception extends Exception
{
    /**
     * @param string|Throwable $message
     * @param int $code
     */
    public function __construct(string|Throwable $message = "", int $code = 1)
    {
        if ($message instanceof Throwable) {
            return parent::__construct($message->getMessage(), $code, $message);
        }

        return parent::__construct($message, $code);
    }
}
