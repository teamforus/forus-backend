<?php

namespace App\Services\DigIdService;

use App\Services\SAML2Service\Exceptions\Saml2Exception;
use App\Services\SAML2Service\Lib\Settings;
use DOMDocument;
use DOMElement;
use DOMException;
use Exception;
use Illuminate\Support\Arr;
use Random\RandomException;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use Throwable;

class TvsMetadataBuilder
{
    private const string NS_MD = 'urn:oasis:names:tc:SAML:2.0:metadata';
    private const string NS_DS = 'http://www.w3.org/2000/09/xmldsig#';
    private const string SAML_PROTOCOL = 'urn:oasis:names:tc:SAML:2.0:protocol';

    /**
     * @return static
     */
    public static function make(): static
    {
        return new static();
    }

    /**
     * @param array $dvEntities
     * @param Settings $settings
     * @throws Saml2Exception
     * @return string
     */
    public function buildTvsMetadataForEntities(array $dvEntities, Settings $settings): string
    {
        $lcEntityId = $settings->getSPId();

        try {
            $doc = new DOMDocument('1.0', 'UTF-8');
            $doc->preserveWhiteSpace = false;
            $doc->formatOutput = false;

            $entitiesDescriptor = $doc->createElementNS(self::NS_MD, 'md:EntitiesDescriptor');
            $entitiesDescriptor->setAttribute('ID', $this->generateMetadataId());
            $entitiesDescriptor->setAttribute('cacheDuration', $this->getMetadataCacheDuration());

            $doc->appendChild($entitiesDescriptor);

            // LC must be present, followed by every supported DV.
            $entitiesDescriptor->appendChild($this->buildLcEntityDescriptor($doc, $lcEntityId, $settings));

            foreach ($dvEntities as $dvEntity) {
                $entitiesDescriptor->appendChild($this->buildDvEntityDescriptor($doc, $dvEntity, $settings));
            }

            return $this->signMetadata($doc, $entitiesDescriptor, $settings);
        } catch (Throwable $e) {
            if ($e instanceof Saml2Exception) {
                throw $e;
            }

            throw new Saml2Exception($e);
        }
    }

    /**
     * @param DOMDocument $doc
     * @param DOMElement $spSsoDescriptor
     * @param Settings $settings
     * @throws DOMException
     * @return void
     */
    protected function appendAssertionConsumerService(
        DOMDocument $doc,
        DOMElement $spSsoDescriptor,
        Settings $settings,
    ): void {
        $acs = $doc->createElementNS(self::NS_MD, 'md:AssertionConsumerService');
        $acs->setAttribute('Binding', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact');
        $acs->setAttribute('Location', $settings->getOptional('sp.assertionConsumerService.url'));
        $acs->setAttribute('index', '0');
        $acs->setAttribute('isDefault', 'true');
        $spSsoDescriptor->appendChild($acs);
    }

    /**
     * @param DOMDocument $doc
     * @param string $entityId
     * @param Settings $settings
     * @throws DOMException
     * @throws RandomException
     * @throws Saml2Exception
     * @return DOMElement
     */
    private function buildLcEntityDescriptor(
        DOMDocument $doc,
        string $entityId,
        Settings $settings,
    ): DOMElement {
        $entityDescriptor = $doc->createElementNS(self::NS_MD, 'md:EntityDescriptor');
        $entityDescriptor->setAttribute('ID', $this->generateMetadataId());
        $entityDescriptor->setAttribute('entityID', $entityId);

        $spSsoDescriptor = $this->buildSpSsoDescriptor($doc, authnRequestsSigned: true, wantAssertionsSigned: true);
        $entityDescriptor->appendChild($spSsoDescriptor);

        foreach ($this->getLcSigningCertificates($settings) as $certificate) {
            $spSsoDescriptor->appendChild(
                $this->buildKeyDescriptor($doc, certificatePem: $certificate, use: 'signing'),
            );
        }

        $this->appendAssertionConsumerService($doc, $spSsoDescriptor, $settings);

        return $entityDescriptor;
    }

    /**
     * @param DOMDocument $doc
     * @param array $entity
     * @param Settings $settings
     * @throws DOMException
     * @throws RandomException
     * @throws Saml2Exception
     * @return DOMElement
     */
    private function buildDvEntityDescriptor(
        DOMDocument $doc,
        array $entity,
        Settings $settings,
    ): DOMElement {
        $entityDescriptor = $doc->createElementNS(self::NS_MD, 'md:EntityDescriptor');
        $entityDescriptor->setAttribute('ID', $this->generateMetadataId());
        $entityDescriptor->setAttribute('entityID', Arr::get($entity, 'entityID'));

        $spSsoDescriptor = $this->buildSpSsoDescriptor($doc, authnRequestsSigned: false, wantAssertionsSigned: false);
        $entityDescriptor->appendChild($spSsoDescriptor);

        $encryptionCertificate = Arr::get($entity, 'x509cert');

        if ($encryptionCertificate === null || trim($encryptionCertificate) === '') {
            throw new Saml2Exception('No DV encryption certificate configured. ');
        }

        $spSsoDescriptor->appendChild(
            $this->buildKeyDescriptor($doc, certificatePem: $encryptionCertificate, use: 'encryption'),
        );

        $this->appendAssertionConsumerService($doc, $spSsoDescriptor, $settings);

        return $entityDescriptor;
    }

    /**
     * @param DOMDocument $doc
     * @param bool $authnRequestsSigned
     * @param bool $wantAssertionsSigned
     * @throws DOMException
     * @return DOMElement
     */
    private function buildSpSsoDescriptor(
        DOMDocument $doc,
        bool $authnRequestsSigned,
        bool $wantAssertionsSigned,
    ): DOMElement {
        $spSsoDescriptor = $doc->createElementNS(self::NS_MD, 'md:SPSSODescriptor');
        $spSsoDescriptor->setAttribute('protocolSupportEnumeration', self::SAML_PROTOCOL);

        $spSsoDescriptor->setAttribute('AuthnRequestsSigned', $authnRequestsSigned ? 'true' : 'false');
        $spSsoDescriptor->setAttribute('WantAssertionsSigned', $wantAssertionsSigned ? 'true' : 'false');

        return $spSsoDescriptor;
    }

    /**
     * @param DOMDocument $doc
     * @param string $certificatePem
     * @param string $use
     * @throws DOMException
     * @throws Saml2Exception
     * @return DOMElement
     */
    private function buildKeyDescriptor(
        DOMDocument $doc,
        string $certificatePem,
        string $use,
    ): DOMElement {
        if (trim($certificatePem) === '') {
            throw new Saml2Exception(sprintf('Cannot build %s KeyDescriptor without a certificate.', $use));
        }

        $keyDescriptor = $doc->createElementNS(self::NS_MD, 'md:KeyDescriptor');
        $keyDescriptor->setAttribute('use', $use);
        $keyInfo = $doc->createElementNS(self::NS_DS, 'ds:KeyInfo');
        $keyDescriptor->appendChild($keyInfo);

        $keyName = $doc->createElementNS(self::NS_DS, 'ds:KeyName', $this->certFingerprint($certificatePem));
        $keyInfo->appendChild($keyName);

        $x509Data = $doc->createElementNS(self::NS_DS, 'ds:X509Data');
        $keyInfo->appendChild($x509Data);

        $x509Certificate = $doc->createElementNS(
            self::NS_DS,
            'ds:X509Certificate',
            $this->stripPemHeaders($certificatePem),
        );

        $x509Data->appendChild($x509Certificate);

        return $keyDescriptor;
    }

    /**
     * @param Settings $settings
     * @throws Saml2Exception
     * @return array
     */
    private function getLcSigningCertificates(Settings $settings): array
    {
        $certificates = [];
        $primaryCertificate = $settings->getSPcert();

        if (is_string($primaryCertificate) && trim($primaryCertificate) !== '') {
            $certificates[] = $primaryCertificate;
        }

        $tlsCertificate = $settings->getOptional('sp.x509certTls');

        if (is_string($tlsCertificate) && trim($tlsCertificate) !== '') {
            $certificates[] = $tlsCertificate;
        }

        if ($certificates === []) {
            throw new Saml2Exception(
                'No LC signing/TLS certificates configured. ' .
                'Configure sp.x509cert and, if applicable, sp.x509certTls.',
            );
        }

        return $certificates;
    }

    /**
     * @param DOMDocument $doc
     * @param DOMElement $root
     * @param Settings $settings
     * @throws Exception
     * @return string
     */
    private function signMetadata(DOMDocument $doc, DOMElement $root, Settings $settings): string
    {
        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $key->loadKey($settings->getSPkey());

        $dsig = new XMLSecurityDSig();
        $dsig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);

        $dsig->addReferenceList(
            [$root],
            XMLSecurityDSig::SHA256,
            ['http://www.w3.org/2000/09/xmldsig#enveloped-signature', XMLSecurityDSig::EXC_C14N],
            ['id_name' => 'ID', 'overwrite' => false]
        );

        $dsig->sign($key);

        $certPem = $settings->getSPcert();
        $dsig->add509Cert($certPem, true, false, ['issuerSerial' => false]);
        $dsig->insertSignature($root, $root->firstChild);

        return $doc->saveXML();
    }

    /**
     * @throws RandomException
     * @return string
     */
    private function generateMetadataId(): string
    {
        return '_' . bin2hex(random_bytes(20));
    }

    /**
     * @return string
     */
    private function getMetadataCacheDuration(): string
    {
        return 'PT24H';
    }

    /**
     * @param string $pem
     * @return string
     */
    private static function stripPemHeaders(string $pem): string
    {
        return trim(preg_replace('/-----(BEGIN|END) CERTIFICATE-----|\s+/', '', $pem));
    }

    /**
     * @param string $pem
     * @return string
     */
    private static function certFingerprint(string $pem): string
    {
        return sha1(base64_decode(self::stripPemHeaders($pem)));
    }
}
