<?php

declare(strict_types=1);

namespace App\Services\SAML2Service\Lib;

use App\Services\SAML2Service\Exceptions\Saml2Exception;
use App\Services\SAML2Service\Utils\TmpFile;
use DOMDocument;
use DOMElement;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\ArtifactResolve;
use SAML2\ArtifactResponse;
use SAML2\DOMDocumentFactory;
use SAML2\Exception\RuntimeException;
use SAML2\Exception\InvalidArgumentException;
use SAML2\Exception\UnparseableXmlException;
use SAML2\Utils;
use Throwable;

/**
 * Implementation of the SAML 2.0 SOAP binding.
 *
 * @author Shoaib Ali
 * @package SimpleSAMLphp
 */
class SOAPClient
{
    const START_SOAP_ENVELOPE = '<soap-env:Envelope xmlns:soap-env="http://schemas.xmlsoap.org/soap/envelope/">\
        <soap-env:Header/><soap-env:Body>';
    const END_SOAP_ENVELOPE = '</soap-env:Body></soap-env:Envelope>';

    /**
     * This function sends the SOAP message to the service location and returns SOAP response
     *
     * @param ArtifactResolve $msg The request that should be sent.
     * @param Settings $settings
     * @return ArtifactResponse The response we received.
     * @throws Throwable
     * @psalm-suppress UndefinedClass
     */
    public function send(ArtifactResolve $msg, Settings $settings): ArtifactResponse
    {
        $issuer = $msg->getIssuer();
        $localCertData = $settings->getSPkey() . $settings->getSPcert();
        $localCertFile = new TmpFile($localCertData);
        $peerCertData = $settings->getIdpPeerCertificate();
        $peerCertFile = $peerCertData ? new TmpFile($peerCertData) : null;
        $ctxOpts = [];

        $ctxOpts['ssl']['capture_peer_cert'] = true;
        $ctxOpts['ssl']['allow_self_signed'] = true;
        $ctxOpts['ssl']['local_cert'] = $localCertFile->path();

        if ($peerCertFile) {
            $ctxOpts['ssl']['verify_peer'] = true;
            $ctxOpts['ssl']['verify_depth'] = 10;
            $ctxOpts['ssl']['cafile'] = $peerCertFile->path();
        }

        $context = stream_context_create($ctxOpts);

        $options = [
            'uri' => $issuer->getValue(),
            'location' => $msg->getDestination(),
            'stream_context' => $context,
        ];

        $x = new \SoapClient(null, $options);

        // Add soap-envelopes
        $request = $msg->toSignedXML();
        $request = self::START_SOAP_ENVELOPE . $request->ownerDocument->saveXML($request) . self::END_SOAP_ENVELOPE;

        // Perform SOAP Request over HTTP
        $action = 'http://www.oasis-open.org/committees/security';
        $soapResponseXML = $x->__doRequest($request, $options['location'], $action, SOAP_1_1);

        $peerCertFile?->close();
        $localCertFile->close();

        if (empty($soapResponseXML)) {
            throw new Saml2Exception('Empty SOAP response, check peer certificate.');
        }

        try {
            $dom = DOMDocumentFactory::fromString($soapResponseXML);
        } catch (InvalidArgumentException | UnparseableXmlException | RuntimeException $e) {
            throw new Saml2Exception($e->getMessage());
        }

        if (empty(Utils::xpQuery($dom->firstChild, '/soap-env:Envelope/soap-env:Body/*[1]'))) {
            throw new Saml2Exception('Not a SOAP response');
        }

        if (!empty($error = $this->getSOAPFault($dom))) {
            throw new Saml2Exception($error);
        }

        /** @var DOMElement[] $samlResponse */
        $samlResponse = Utils::xpQuery($dom->firstChild, '/soap-env:Envelope/soap-env:Body/*[1]');
        $samlResponse = ArtifactResponse::fromXML($samlResponse[0]);

        if ($samlResponse instanceof ArtifactResponse) {
            // Add validator to message which uses the SSL context.
            self::addSSLValidator($samlResponse, $context);

            if ($samlResponse->validate($settings->getSPXmlSecurityKey())) {
                return $samlResponse;
            }

            throw new Saml2Exception("Artifact response failed validation.");
        }

        throw new Saml2Exception("Invalid artifact response type.");
    }

    /**
     * Add a signature validator based on an SSL context.
     *
     * @param ArtifactResponse $msg The message we should add a validator to.
     * @param resource $context The stream context.
     * @return void
     */
    private static function addSSLValidator(ArtifactResponse $msg, $context) : void
    {
        $peer_cert = Arr::get(stream_context_get_options($context), 'ssl.peer_certificate');
        $peer_key = $peer_cert ? openssl_pkey_get_public($peer_cert) : null;
        $peer_key_info = $peer_key ? openssl_pkey_get_details($peer_key) : null;

        if ($peer_key_info['key'] ?? null) {
            $msg->addValidator([SOAPClient::class, 'validateSSL'], $peer_key_info['key']);
        }
    }

    /**
     * Validate a SOAP message against the certificate on the SSL connection.
     *
     * @param string $data The public key that was used on the connection.
     * @param XMLSecurityKey $key The key we should validate the certificate against.
     * @param bool $debugLog
     * @return void
     * @throws Saml2Exception
     */
    public static function validateSSL(string $data, XMLSecurityKey $key, bool $debugLog = false) : void
    {
        $keyInfo = openssl_pkey_get_details($key->key);
        $debugLog = !Config::get('forus.digid.saml.disable_ssl_debug_log');

        if ($keyInfo === false) {
            throw new Saml2Exception('Unable to get key details from XMLSecurityKey.');
        }

        if (!isset($keyInfo['key'])) {
            throw new Saml2Exception('Missing key in public key details.');
        }

        if ($keyInfo['key'] !== $data && $debugLog) {
            Log::debug('Key on SSL connection did not match key we validated against.');

            return;
        }

        if ($debugLog) {
            Log::debug('Message validated based on SSL certificate.');
        }
    }


    /**
     * Extracts the SOAP Fault from SOAP message
     *
     * @param DOMDocument $soapMessage
     * @return string|null
     */
    private function getSOAPFault(DOMDocument $soapMessage) : ?string
    {
        $soapFault = Utils::xpQuery($soapMessage->firstChild, '/soap-env:Envelope/soap-env:Body/soap-env:Fault');

        if (empty($soapFault)) {
            return null;
        }

        $faultStringElement = Utils::xpQuery($soapFault[0], './soap-env:faultstring');

        if (!empty($faultStringElement)) {
            return $faultStringElement[0]->textContent;
        }

        return "Unknown fault string found";
    }
}
