<?php

namespace App\Services\SAML2Service;

use App\Services\SAML2Service\Exceptions\InvalidConfigsException;
use App\Services\SAML2Service\Exceptions\InvalidMetadataException;
use App\Services\SAML2Service\Exceptions\Saml2Exception;
use App\Services\SAML2Service\Lib\Artifact;
use App\Services\SAML2Service\Lib\Settings;
use App\Services\SAML2Service\Responses\SamlArtifactResponse;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Error;
use Throwable;

class SamlAuth extends Auth
{
    /**
     * @param $config
     * @return self
     * @throws Saml2Exception
     */
    public static function make($config): self
    {
        try {
            return new self($config);
        } catch (Error $e) {
            if ($e->getCode() == $e::SETTINGS_INVALID || $e->getCode() == $e::SETTINGS_INVALID_SYNTAX) {
                throw new InvalidConfigsException($e);
            }

            throw new Saml2Exception($e);
        } catch (Throwable $e) {
            throw new Saml2Exception($e);
        }
    }

    /**
     * @param string $artifact
     * @return SamlArtifactResponse
     * @throws Saml2Exception
     */
    public function resolveArtifact(string $artifact): SamlArtifactResponse
    {
        try {
            $settings = $this->getSettings();
            $artifact = new Artifact($artifact, $settings);
            $response = $artifact->resolve();

            return new SamlArtifactResponse($response, $settings);
        } catch (Throwable $err) {
            throw new Saml2Exception($err);
        }
    }

    /**
     * @return Settings
     * @throws InvalidConfigsException
     */
    public function getSettings(): Settings
    {
        try {
            return Settings::fromBase(parent::getSettings());
        } catch (Throwable $e) {
            throw new InvalidConfigsException($e);
        }
    }

    /**
     * Get metadata about the local SP. Use this to configure your Saml2 IdP.
     *
     * @return string
     * @throws Saml2Exception
     */
    public function getMetadata(): string
    {
        try {
            $settings = $this->getSettings();
            $metadata = $settings->getSPMetadata();
            $errors = $settings->validateMetadata($metadata);

            if (!count($errors)) {
                return $metadata;
            }

            throw new InvalidMetadataException('Invalid SP metadata: ' . implode(', ', $errors));
        } catch (Saml2Exception $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new Saml2Exception($e);
        }
    }
}
