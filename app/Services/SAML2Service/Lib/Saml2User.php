<?php

namespace App\Services\SAML2Service\Lib;

use SAML2\Assertion;
use Exception;

class Saml2User
{
    /**
     * OneLogin authentication handler.
     *
     * @var Assertion
     */
    protected Assertion $auth;

    /**
     * Saml2User constructor.
     *
     * @param Assertion $auth
     */
    public function __construct(Assertion $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Get the attributes retrieved from assertion processed this request
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->auth->getAttributes();
    }

    /**
     * Get user's name ID.
     *
     * @return string|null
     * @throws Exception
     */
    public function getNameId(): ?string
    {
        return $this->auth->getNameId()?->getValue();
    }
}
