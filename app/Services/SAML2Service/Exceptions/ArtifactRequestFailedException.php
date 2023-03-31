<?php

namespace App\Services\SAML2Service\Exceptions;

class ArtifactRequestFailedException extends Saml2Exception
{
    /**
     * @param string $message
     */
    public function __construct(string $message = 'Received error from ArtifactResolutionService.')
    {
        parent::__construct($message, 5);
    }
}
