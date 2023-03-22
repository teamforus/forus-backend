<?php

namespace App\Services\SAML2Service\Lib;

use App\Services\SAML2Service\Exceptions\Saml2Exception;
use Illuminate\Support\Arr;
use OneLogin\Saml2\Error;
use OneLogin\Saml2\Settings as OneLoginSettings;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\XML\saml\Issuer;
use Throwable;

class Settings extends OneLoginSettings
{
    protected array $_settings;

    /**
     * @param array|null $settings
     * @param bool $spValidationOnly
     * @throws Error
     */
    public function __construct(array $settings = null, bool $spValidationOnly = false)
    {
        parent::__construct($settings, $spValidationOnly);
        $this->_settings = $settings;
    }

    /**
     * @param OneLoginSettings $settings
     * @return static
     * @throws Error
     */
    public static function fromBase(OneLoginSettings $settings): static
    {
        return new static([
            'security' => $settings->getSecurityData(),
            'idp' => $settings->getIdPData(),
            'sp' => $settings->getSPData(),
        ]);
    }

    /**
     * @return mixed|null
     */
    public function getIdPArSUrl(): ?string
    {
        return $this->getOptional('idp.artifactResolutionService.url');
    }

    /**
     * @return string|null
     */
    public function getSPId(): ?string
    {
        return $this->getOptional('sp.entityId');
    }

    /**
     * @return string|null
     */
    public function getIdpPeerCertificate(): ?string
    {
        return $this->getOptional('idp.certData');
    }

    /**
     * @return Issuer
     */
    public function getSPIssuer(): Issuer
    {
        $issuer = new Issuer();
        $issuer->setValue($this->getSPId());

        return $issuer;
    }

    /**
     * @return XMLSecurityKey
     * @throws Saml2Exception
     */
    public function getSPXmlSecurityKey(): XMLSecurityKey
    {
        return $this->getXmlSecurityKey($this->getSPkey());
    }

    /**
     * @return XMLSecurityKey
     * @throws Saml2Exception
     * @noinspection PhpUnused
     */
    public function getSPXmlSecurityCertKey(): XMLSecurityKey
    {
        return $this->getXmlSecurityKey($this->getSPcert(), 'public');
    }

    /**
     * @param string|null $certOrKey
     * @param string $type
     * @return XMLSecurityKey
     * @throws Saml2Exception
     */
    protected function getXmlSecurityKey(?string $certOrKey, string $type = 'private'): XMLSecurityKey
    {
        try {
            $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, compact('type'));
            $key->loadKey($certOrKey);

            return $key;
        } catch (Throwable $e) {
            throw new Saml2Exception($e->getMessage());
        }
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return $this->_settings;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getOptional(string $key, mixed $default = null) : mixed
    {
        return Arr::get($this->getSettings(), $key, $default);
    }
}
