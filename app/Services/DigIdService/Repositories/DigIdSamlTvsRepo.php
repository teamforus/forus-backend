<?php

namespace App\Services\DigIdService\Repositories;

use App\Services\DigIdService\DigIdException;
use App\Services\DigIdService\Objects\DigidAuthRequestData;
use App\Services\DigIdService\Objects\DigidAuthResolveData;
use App\Services\DigIdService\Objects\DigIdResolveContext;
use App\Services\DigIdService\Objects\DigIdStartContext;
use App\Services\DigIdService\TvsBsnResolver;
use App\Services\SAML2Service\Exceptions\Saml2Exception;
use App\Services\SAML2Service\Responses\SamlArtifactResponse;
use App\Services\SAML2Service\SamlAuth;
use DOMDocument;
use DOMException;
use Exception;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\AuthnRequest;
use SAML2\XML\saml\Attribute;
use SAML2\XML\saml\AttributeValue;
use Throwable;

class DigIdSamlTvsRepo extends BaseDigIdSamlRepo
{
    private const string NS_SAML = 'urn:oasis:names:tc:SAML:2.0:assertion';

    /**
     * @param array $configs
     * @param array $organizationConfiguration
     */
    public function __construct(array $configs, private readonly array $organizationConfiguration)
    {
        parent::__construct($configs);
    }

    /**
     * @param DigIdStartContext $context
     * @throws Saml2Exception
     * @return DigidAuthRequestData
     */
    public function makeAuthRequest(DigIdStartContext $context): DigidAuthRequestData
    {
        if ($context->requestId === null) {
            throw new InvalidArgumentException('TVS authentication requires a request ID.');
        }

        $auth = SamlAuth::make($this->configs);
        $authnRequest = $this->buildTvsAuthnRequest($auth, $context);

        return (new DigidAuthRequestData())
            ->setMeta([
                'destination' => $authnRequest['destination'],
                'saml_request' => $authnRequest['saml_request'],
            ])
            ->setRequestId($authnRequest['request_id'])
            ->setAuthResolveUrl($context->callbackUrl);
    }

    /**
     * @param Request|SamlArtifactResponse $response
     * @param DigIdResolveContext $context
     * @throws DigIdException
     * @throws Throwable
     * @return DigidAuthResolveData
     */
    public function resolveResponse(
        Request|SamlArtifactResponse $response,
        DigIdResolveContext $context,
    ): DigidAuthResolveData {
        if (!$response instanceof SamlArtifactResponse) {
            throw new InvalidArgumentException('TVS resolution requires an exchanged SAML artifact response.');
        }

        $this->validateSamlResponse($response, $context->requestId);

        return new DigidAuthResolveData((new TvsBsnResolver())->resolve(
            $response->getUser(),
            $this->organizationConfiguration['authn_context'],
        ));
    }

    /**
     * @param SamlAuth $auth
     * @param DigIdStartContext $context
     * @throws Saml2Exception
     * @return array{destination: string, saml_request: string, request_id: string}
     */
    protected function buildTvsAuthnRequest(SamlAuth $auth, DigIdStartContext $context): array
    {
        try {
            $settings = $auth->getSettings();

            $dvEntityId = $this->organizationConfiguration['entity_id'];
            $serviceUuid = $this->organizationConfiguration['service_uuid'];
            $requestedAuthnContext = $this->organizationConfiguration['authn_context'];

            $request = new AuthnRequest();
            $request->setId($context->requestId);
            $request->setIssuer($settings->getSPIssuer());
            $request->setDestination($settings->getOptional('idp.singleSignOnService.url'));
            $request->setForceAuthn((bool) $settings->getOptional('security.forceAuthn', true));

            $acsIndex = $settings->getOptional('sp.assertionConsumerService.index');

            if ($acsIndex !== null) {
                $request->setAssertionConsumerServiceIndex((int) $acsIndex);
            }

            $authnContextClassRefs = (array) $requestedAuthnContext;

            if (!empty($authnContextClassRefs)) {
                $request->setRequestedAuthnContext([
                    'AuthnContextClassRef' => $authnContextClassRefs,
                    'Comparison' => $settings->getOptional('security.requestedAuthnContextComparison', 'exact'),
                ]);
            }

            $request->setExtensions([
                $this->makeExtensionAttribute('urn:nl-eid-gdi:1.0:IntendedAudience', $dvEntityId),
                $this->makeExtensionAttribute('urn:nl-eid-gdi:1.0:ServiceUUID', $serviceUuid),
            ]);

            $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
            $key->loadKey($settings->getSPkey());
            $request->setSignatureKey($key);
            $request->setCertificates([$settings->getSPcert()]);

            $signedNode = $request->toSignedXML();

            return [
                'destination' => $request->getDestination(),
                'saml_request' => base64_encode($signedNode->ownerDocument->saveXML($signedNode)),
                'request_id' => $request->getId(),
            ];
        } catch (Throwable $e) {
            throw new Saml2Exception($e);
        }
    }

    /**
     * @param string $name
     * @param string $value
     * @throws DOMException
     * @throws Exception
     * @return Attribute
     */
    private function makeExtensionAttribute(string $name, string $value): Attribute
    {
        $doc = new DOMDocument();

        $attribute = $doc->createElementNS(self::NS_SAML, 'saml:Attribute');
        $attribute->setAttribute('Name', $name);
        $doc->appendChild($attribute);

        $attributeValue = new AttributeValue($value);
        $attributeValue->toXML($attribute);

        return new Attribute($attribute);
    }
}
