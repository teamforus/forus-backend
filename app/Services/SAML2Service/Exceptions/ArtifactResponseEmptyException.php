<?php

namespace App\Services\SAML2Service\Exceptions;

class ArtifactResponseEmptyException extends Saml2Exception
{
    /**
     * @param string $message
     */
    public function __construct(string $message = 'Empty ArtifactResponse received, maybe a replay?')
    {
        parent::__construct($message, 4);
    }
}
