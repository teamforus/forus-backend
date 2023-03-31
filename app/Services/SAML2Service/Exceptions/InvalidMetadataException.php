<?php

namespace App\Services\SAML2Service\Exceptions;

class InvalidMetadataException extends Saml2Exception
{
    /**
     * @param string $message
     */
    public function __construct(string $message)
    {
        parent::__construct($message, 2);
    }
}
