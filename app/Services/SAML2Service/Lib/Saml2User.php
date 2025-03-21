<?php

namespace App\Services\SAML2Service\Lib;

use Exception;
use SAML2\Assertion;

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
     * Get the attributes retrieved from assertion processed this request.
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
     * @throws Exception
     * @return string|null
     */
    public function getNameId(): ?string
    {
        return $this->auth->getNameId()?->getValue();
    }
}
