<?php

namespace App\Services\SAML2Service\Lib;

use DOMElement;
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
     * Raw SAML response associated with the user.
     */
    protected DOMElement $rawResponse;

    protected Settings $settings;

    /**
     * Saml2User constructor.
     *
     * @param Assertion $auth
     * @param DOMElement $rawResponse
     * @param Settings $settings
     */
    public function __construct(Assertion $auth, DOMElement $rawResponse, Settings $settings)
    {
        $this->auth = $auth;
        $this->rawResponse = $rawResponse;
        $this->settings = $settings;
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

    /**
     * @return DOMElement
     */
    public function getRawResponse(): DOMElement
    {
        return $this->rawResponse;
    }

    /**
     * @return Settings
     */
    public function getSettings(): Settings
    {
        return $this->settings;
    }
}
