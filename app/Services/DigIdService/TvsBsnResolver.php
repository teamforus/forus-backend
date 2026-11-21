<?php

namespace App\Services\DigIdService;

use App\Services\SAML2Service\Exceptions\Saml2Exception;
use App\Services\SAML2Service\Lib\Saml2User;
use App\Services\SAML2Service\Lib\Settings;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use Exception;
use RobRichards\XMLSecLibs\XMLSecEnc;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class TvsBsnResolver
{
    private const array LOA_LEVELS = [
        'http://eID.logius.nl/LoA/basic' => 1,
        'http://eidas.europa.eu/LoA/low' => 2,
        'http://eidas.europa.eu/LoA/substantial' => 3,
        'http://eidas.europa.eu/LoA/high' => 4,
    ];

    private const string NS_SAML = 'urn:oasis:names:tc:SAML:2.0:assertion';
    private const string NS_XENC = 'http://www.w3.org/2001/04/xmlenc#';

    /**
     * @param Saml2User $user
     * @param string $minLoa
     * @throws Saml2Exception
     * @return string
     */
    public function resolve(Saml2User $user, string $minLoa): string
    {
        $rawResponse = $user->getRawResponse();
        $settings = $user->getSettings();
        $xpath = new DOMXPath($rawResponse->ownerDocument);
        $xpath->registerNamespace('saml', self::NS_SAML);
        $xpath->registerNamespace('xenc', self::NS_XENC);

        $encryptedIdNodes = $xpath->query(
            './saml:Assertion[last()]/saml:AttributeStatement'
            . '/saml:Attribute[@Name="urn:nl-eid-gdi:1.0:ActingSubjectID"]'
            . '/saml:AttributeValue/saml:EncryptedID',
            $rawResponse
        );

        if ($encryptedIdNodes->length === 0) {
            throw new Saml2Exception('No ActingSubjectID / EncryptedID found in AttributeStatement');
        }

        $authnContextClassRef = $xpath->query(
            './saml:Assertion[last()]/saml:AuthnStatement/saml:AuthnContext/saml:AuthnContextClassRef',
            $rawResponse
        )->item(0);

        if (!$authnContextClassRef instanceof DOMElement) {
            throw new Saml2Exception('No AuthnContextClassRef found in AuthnStatement');
        }

        $this->validateLoa($authnContextClassRef->textContent, $minLoa);

        $nameId = $this->decryptMatchingEncryptedId($encryptedIdNodes, $xpath, $settings);

        $expectedQualifier = $this->getBsnNameQualifier($settings);
        $actualQualifier = $nameId->getAttribute('NameQualifier');

        if ($actualQualifier !== $expectedQualifier) {
            throw new Saml2Exception(
                "Decrypted identifier is not a BSN (NameQualifier was '$actualQualifier', " .
                "expected '$expectedQualifier')"
            );
        }

        return trim($nameId->nodeValue);
    }

    /**
     * @param DOMNodeList<DOMElement> $encryptedIdNodes
     * @param DOMXPath $xpath
     * @param Settings $settings
     * @throws Saml2Exception
     * @return DOMElement
     */
    private function decryptMatchingEncryptedId(
        DOMNodeList $encryptedIdNodes,
        DOMXPath $xpath,
        Settings $settings,
    ): DOMElement {
        // here we use DV entityID, set previously when resolve artifacts
        $ourEntityId = $settings->getSPId();
        $matchingKeyNode = null;

        foreach ($encryptedIdNodes as $encryptedIdNode) {
            foreach ($xpath->query('./xenc:EncryptedKey', $encryptedIdNode) as $keyNode) {
                if ($keyNode instanceof DOMElement && $keyNode->getAttribute('Recipient') === $ourEntityId) {
                    $matchingKeyNode = $keyNode;
                    break 2;
                }
            }
        }

        if ($matchingKeyNode === null) {
            throw new Saml2Exception("No EncryptedKey in EncryptedID addressed to our EntityID ({$ourEntityId}).");
        }

        $encryptedDataNode = $xpath->query('./xenc:EncryptedData', $matchingKeyNode->parentNode)->item(0);

        if (!$encryptedDataNode instanceof DOMElement) {
            throw new Saml2Exception('EncryptedID contained no EncryptedData.');
        }

        try {
            $privateKey = $settings->getSPEncryptionXmlSecurityKey();

            $encKey = new XMLSecEnc();
            $encKey->setNode($matchingKeyNode);
            $encKey->type = $matchingKeyNode->getAttribute('Type') ?: XMLSecEnc::Element;

            $symmetricKeyPlaintext = $privateKey->decryptData($encKey->getCipherValue());

            $symmetricKey = new XMLSecurityKey(XMLSecurityKey::AES256_CBC, ['type' => 'private']);
            $symmetricKey->loadKey($symmetricKeyPlaintext);
        } catch (Exception) {
            throw new Saml2Exception('Load DV key for decryption failed.');
        }

        try {
            $enc = new XMLSecEnc();
            $enc->setNode($encryptedDataNode);
            $enc->type = $encryptedDataNode->getAttribute('Type');

            $decrypted = $enc->decryptNode($symmetricKey, false);
        } catch (Exception) {
            throw new Saml2Exception('Decrypt failed with DV key.');
        }

        if (is_string($decrypted)) {
            $doc = new DOMDocument();
            $doc->loadXML($decrypted);
            $nameId = $doc->documentElement;
        } else {
            $nameId = $decrypted;
        }

        if (!$nameId instanceof DOMElement || $nameId->localName !== 'NameID') {
            throw new Saml2Exception('Decrypted EncryptedID did not contain a <NameID> element.');
        }

        return $nameId;
    }

    /**
     * @param string $actualLoa
     * @param string $minimumLoa
     * @throws Saml2Exception
     * @return void
     */
    private function validateLoa(string $actualLoa, string $minimumLoa): void
    {
        if (!isset(self::LOA_LEVELS[$actualLoa])) {
            throw new Saml2Exception(
                "Unsupported authentication LoA: $actualLoa"
            );
        }

        if (!isset(self::LOA_LEVELS[$minimumLoa])) {
            throw new Saml2Exception(
                "Unsupported configured minimum LoA: $minimumLoa"
            );
        }

        if (self::LOA_LEVELS[$actualLoa] < self::LOA_LEVELS[$minimumLoa]) {
            throw new Saml2Exception(
                "Authentication LoA $actualLoa is below the required minimum $minimumLoa."
            );
        }
    }

    /**
     * @param Settings $settings
     * @return string
     */
    private function getBsnNameQualifier(Settings $settings): string
    {
        return $settings->getOptional('security.bsnNameQualifier', 'urn:nl-eid-gdi:1.0:id:legacy-BSN');
    }
}
