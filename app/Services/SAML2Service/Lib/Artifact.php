<?php

namespace App\Services\SAML2Service\Lib;

use App\Services\SAML2Service\Exceptions\ArtifactRequestFailedException;
use App\Services\SAML2Service\Exceptions\ArtifactResponseEmptyException;
use App\Services\SAML2Service\Exceptions\Saml2Exception;
use Exception;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\ArtifactResolve;
use SAML2\ArtifactResponse;
use SAML2\Response;
use Throwable;

class Artifact
{
    protected string $artifact;
    protected Settings $settings;

    /**
     * @param string $artifact
     * @param Settings $settings
     */
    public function __construct(string $artifact, Settings $settings)
    {
        $this->artifact = $artifact;
        $this->settings = $settings;
    }

    /**
     * @return Response
     * @throws
     */
    public function resolve(): Response
    {
        $client = new SOAPClient();
        $artifactResolve = $this->makeArtifactResolve();
        $artifactResponse = $client->send($artifactResolve, $this->settings);

        $artifactXML = $artifactResponse->getAny();
        $samlResponse = $artifactXML ? Response::fromXML($artifactXML) : null;
        $samlResponse->addValidator([get_class($this), 'validateSignature'], $artifactResponse);

        if (!$artifactResponse->isSuccess()) {
            throw new ArtifactRequestFailedException();
        }

        if (empty($artifactXML)) {
            throw new ArtifactResponseEmptyException();
        }

        if (!$samlResponse instanceof Response) {
            throw new Saml2Exception('Invalid response type.');
        }

        return $samlResponse;
    }

    /**
     * @return ArtifactResolve
     * @throws Throwable
     */
    protected function makeArtifactResolve(): ArtifactResolve
    {
        $artifactResolve = new ArtifactResolve();

        $artifactResolve->setIssuer($this->settings->getSPIssuer());
        $artifactResolve->setArtifact($this->artifact);
        $artifactResolve->setDestination($this->settings->getIdPArSUrl());

        $privateKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $privateKey->loadKey($this->settings->getSPkey());

        $artifactResolve->setSignatureKey($privateKey);
        $artifactResolve->setCertificates([$this->settings->getSPcert()]);

        return $artifactResolve;
    }

    /**
     * A validator which returns true if the ArtifactResponse was signed with the given key
     *
     * @param ArtifactResponse $message
     * @param XMLSecurityKey $key
     * @return bool
     * @throws Exception
     * @noinspection PhpUnused
     */
    public static function validateSignature(ArtifactResponse $message, XMLSecurityKey $key) : bool
    {
        return $message->validate($key);
    }
}
