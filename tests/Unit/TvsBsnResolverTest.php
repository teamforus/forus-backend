<?php

namespace Tests\Unit;

use App\Services\DigIdService\TvsBsnResolver;
use App\Services\SAML2Service\Exceptions\Saml2Exception;
use App\Services\SAML2Service\Lib\Saml2User;
use App\Services\SAML2Service\Lib\Settings;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RobRichards\XMLSecLibs\XMLSecEnc;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use RuntimeException;
use SAML2\Assertion;
use Throwable;

class TvsBsnResolverTest extends TestCase
{
    private const string ENTITY_ID = 'https://dv.example.test';
    private const string LOA_BASIC = 'http://eID.logius.nl/LoA/basic';
    private const string LOA_LOW = 'http://eidas.europa.eu/LoA/low';
    private const string LOA_SUBSTANTIAL = 'http://eidas.europa.eu/LoA/substantial';
    private const string LOA_HIGH = 'http://eidas.europa.eu/LoA/high';
    private const string SAML_NAMESPACE = 'urn:oasis:names:tc:SAML:2.0:assertion';

    /**
     * @throws Throwable
     * @return void
     */
    public function testDecryptsBsnForMatchingRecipientAfterUnrelatedRecipient(): void
    {
        $recipient = $this->makeKeyPair();
        $otherRecipient = $this->makeKeyPair();

        $response = $this->makeEncryptedResponse([
            'https://other-dv.example.test' => $otherRecipient['public'],
            self::ENTITY_ID => $recipient['public'],
        ]);

        $this->assertSame('123456782', (new TvsBsnResolver())->resolve(
            $this->makeUser($response, $recipient['private']),
            self::LOA_SUBSTANTIAL,
        ));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testDecryptsBsnFromMatchingAttributeValueAfterUnrelatedValue(): void
    {
        $recipient = $this->makeKeyPair();
        $otherRecipient = $this->makeKeyPair();
        $response = $this->makeEncryptedResponse(['https://other-dv.example.test' => $otherRecipient['public']]);
        $matchingResponse = $this->makeEncryptedResponse([self::ENTITY_ID => $recipient['public']]);

        $attribute = $response->getElementsByTagNameNS(self::SAML_NAMESPACE, 'Attribute')->item(0);

        $attribute->appendChild($response->ownerDocument->importNode(
            $matchingResponse->getElementsByTagNameNS(self::SAML_NAMESPACE, 'AttributeValue')->item(0),
            true,
        ));

        $this->assertSame('123456782', (new TvsBsnResolver())->resolve(
            $this->makeUser($response, $recipient['private']),
            self::LOA_SUBSTANTIAL,
        ));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUsesTvsAssertionWhenAdviceContainsOriginalAssertion(): void
    {
        $recipient = $this->makeKeyPair();
        $response = $this->makeEncryptedResponse([self::ENTITY_ID => $recipient['public']]);

        $originalResponse = $this->makeEncryptedResponse(
            ['https://other-dv.example.test' => $recipient['public']],
            'urn:oasis:names:tc:SAML:2.0:ac:classes:Smartcard',
        );

        $document = $response->ownerDocument;
        $assertion = $response->getElementsByTagNameNS(self::SAML_NAMESPACE, 'Assertion')->item(0);
        $originalAssertion = $originalResponse->getElementsByTagNameNS(self::SAML_NAMESPACE, 'Assertion')->item(0);
        $originalAssertion->setAttribute('ID', '_assertion_2');

        $advice = $document->createElementNS(self::SAML_NAMESPACE, 'saml:Advice');
        $advice->appendChild($document->importNode($originalAssertion, true));

        $assertion->insertBefore(
            $advice,
            $assertion->getElementsByTagNameNS(self::SAML_NAMESPACE, 'AuthnStatement')->item(0),
        );

        $this->assertSame('123456782', (new TvsBsnResolver())->resolve(
            $this->makeUser($response, $recipient['private']),
            self::LOA_SUBSTANTIAL,
        ));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectsEncryptedBsnWithoutMatchingRecipient(): void
    {
        $recipient = $this->makeKeyPair();

        $response = $this->makeEncryptedResponse([
            'https://other-dv.example.test' => $recipient['public'],
        ]);

        $user = $this->makeUser($response, $recipient['private']);

        $this->expectException(Saml2Exception::class);

        (new TvsBsnResolver())->resolve($user, self::LOA_SUBSTANTIAL);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectsEncryptedBsnWithIncorrectPrivateKey(): void
    {
        $recipient = $this->makeKeyPair();
        $otherRecipient = $this->makeKeyPair();
        $response = $this->makeEncryptedResponse([self::ENTITY_ID => $recipient['public']]);
        $user = $this->makeUser($response, $otherRecipient['private']);

        $this->expectException(Saml2Exception::class);

        (new TvsBsnResolver())->resolve($user, self::LOA_SUBSTANTIAL);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function acceptedAssuranceLevels(): array
    {
        return [
            'basic meets basic' => [self::LOA_BASIC, self::LOA_BASIC],
            'low meets low' => [self::LOA_LOW, self::LOA_LOW],
            'substantial meets substantial' => [self::LOA_SUBSTANTIAL, self::LOA_SUBSTANTIAL],
            'high meets high' => [self::LOA_HIGH, self::LOA_HIGH],
            'low exceeds basic' => [self::LOA_LOW, self::LOA_BASIC],
            'substantial exceeds low' => [self::LOA_SUBSTANTIAL, self::LOA_LOW],
            'high exceeds substantial' => [self::LOA_HIGH, self::LOA_SUBSTANTIAL],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function rejectedAssuranceLevels(): array
    {
        return [
            'basic below low' => [self::LOA_BASIC, self::LOA_LOW],
            'low below substantial' => [self::LOA_LOW, self::LOA_SUBSTANTIAL],
            'substantial below high' => [self::LOA_SUBSTANTIAL, self::LOA_HIGH],
            'unknown actual level' => ['urn:test:loa:unknown', self::LOA_BASIC],
            'unknown minimum level' => [self::LOA_HIGH, 'urn:test:loa:unknown'],
        ];
    }

    /**
     * @param string $actualLoa
     * @param string $minimumLoa
     * @throws Throwable
     * @return void
     */
    #[DataProvider('acceptedAssuranceLevels')]
    public function testReturnsBsnWhenAssuranceLevelMeetsMinimum(string $actualLoa, string $minimumLoa): void
    {
        $recipient = $this->makeKeyPair();
        $response = $this->makeEncryptedResponse([self::ENTITY_ID => $recipient['public']], $actualLoa);
        $user = $this->makeUser($response, $recipient['private']);

        $this->assertSame('123456782', (new TvsBsnResolver())->resolve($user, $minimumLoa));
    }

    /**
     * @param string $actualLoa
     * @param string $minimumLoa
     * @throws Throwable
     * @return void
     */
    #[DataProvider('rejectedAssuranceLevels')]
    public function testRejectsInsufficientOrUnknownAssuranceLevel(string $actualLoa, string $minimumLoa): void
    {
        $recipient = $this->makeKeyPair();
        $response = $this->makeEncryptedResponse([self::ENTITY_ID => $recipient['public']], $actualLoa);
        $user = $this->makeUser($response, $recipient['private']);

        $this->expectException(Saml2Exception::class);

        (new TvsBsnResolver())->resolve($user, $minimumLoa);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReturnsBsnWithMatchingCustomQualifier(): void
    {
        $user = $this->makeUserWithBsnQualifier('urn:test:identifier:bsn', 'urn:test:identifier:bsn');

        $this->assertSame('123456782', (new TvsBsnResolver())->resolve($user, self::LOA_SUBSTANTIAL));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectsIdentifierWithIncorrectBsnQualifier(): void
    {
        $user = $this->makeUserWithBsnQualifier('urn:test:identifier:other');

        $this->expectException(Saml2Exception::class);

        (new TvsBsnResolver())->resolve($user, self::LOA_SUBSTANTIAL);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectsIdentifierWithoutBsnQualifier(): void
    {
        $user = $this->makeUserWithBsnQualifier(null);

        $this->expectException(Saml2Exception::class);

        (new TvsBsnResolver())->resolve($user, self::LOA_SUBSTANTIAL);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectsDefaultBsnQualifierWhenCustomQualifierIsConfigured(): void
    {
        $user = $this->makeUserWithBsnQualifier('urn:nl-eid-gdi:1.0:id:legacy-BSN', 'urn:test:identifier:bsn');

        $this->expectException(Saml2Exception::class);

        (new TvsBsnResolver())->resolve($user, self::LOA_SUBSTANTIAL);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectsResponseWithoutActingSubjectId(): void
    {
        $recipient = $this->makeKeyPair();
        $response = $this->makeEncryptedResponse([self::ENTITY_ID => $recipient['public']]);
        $attribute = $response->getElementsByTagNameNS(self::SAML_NAMESPACE, 'Attribute')->item(0);
        $attribute->parentNode->removeChild($attribute);
        $user = $this->makeUser($response, $recipient['private']);

        $this->expectException(Saml2Exception::class);

        (new TvsBsnResolver())->resolve($user, self::LOA_SUBSTANTIAL);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectsResponseWithoutAuthenticationLevel(): void
    {
        $recipient = $this->makeKeyPair();
        $response = $this->makeEncryptedResponse([self::ENTITY_ID => $recipient['public']]);
        $authnContext = $response->getElementsByTagNameNS(self::SAML_NAMESPACE, 'AuthnContextClassRef')->item(0);
        $authnContext->parentNode->removeChild($authnContext);
        $user = $this->makeUser($response, $recipient['private']);

        $this->expectException(Saml2Exception::class);

        (new TvsBsnResolver())->resolve($user, self::LOA_SUBSTANTIAL);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectsDecryptedIdentifierThatIsNotNameId(): void
    {
        $recipient = $this->makeKeyPair();

        $response = $this->makeEncryptedResponse(
            [self::ENTITY_ID => $recipient['public']],
            identifierElement: 'AttributeValue',
        );

        $user = $this->makeUser($response, $recipient['private']);

        $this->expectException(Saml2Exception::class);

        (new TvsBsnResolver())->resolve($user, self::LOA_SUBSTANTIAL);
    }

    /**
     * @param string|null $nameQualifier
     * @param string|null $configuredQualifier
     * @throws Throwable
     * @return Saml2User
     */
    protected function makeUserWithBsnQualifier(?string $nameQualifier, ?string $configuredQualifier = null): Saml2User
    {
        $recipient = $this->makeKeyPair();

        $response = $this->makeEncryptedResponse(
            [self::ENTITY_ID => $recipient['public']],
            nameQualifier: $nameQualifier,
        );

        return $this->makeUser($response, $recipient['private'], $configuredQualifier);
    }

    /**
     * @throws RuntimeException
     * @return array{private: string, public: string}
     */
    protected function makeKeyPair(): array
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);

        if (!$key || !openssl_pkey_export($key, $privateKey)) {
            throw new RuntimeException('Unable to generate a test RSA private key.');
        }

        $details = openssl_pkey_get_details($key);

        if (!$details) {
            throw new RuntimeException('Unable to extract the test RSA public key.');
        }

        return ['private' => $privateKey, 'public' => $details['key']];
    }

    /**
     * @param array<string, string> $recipientKeys
     * @param string $actualLoa
     * @param string|null $nameQualifier
     * @param string $identifierElement
     * @throws Throwable
     * @return DOMElement
     */
    protected function makeEncryptedResponse(
        array $recipientKeys,
        string $actualLoa = self::LOA_SUBSTANTIAL,
        ?string $nameQualifier = 'urn:nl-eid-gdi:1.0:id:legacy-BSN',
        string $identifierElement = 'NameID',
    ): DOMElement {
        $document = new DOMDocument();

        $document->loadXML(<<<'XML'
            <samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
                            xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
                            ID="_response_1" Version="2.0" IssueInstant="2026-01-01T00:00:00Z">
                <samlp:Status>
                    <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
                </samlp:Status>
                <saml:Assertion ID="_assertion_1" Version="2.0" IssueInstant="2026-01-01T00:00:00Z">
                    <saml:Issuer>https://idp.example.test</saml:Issuer>
                    <saml:AuthnStatement AuthnInstant="2026-01-01T00:00:00Z">
                        <saml:AuthnContext>
                            <saml:AuthnContextClassRef/>
                        </saml:AuthnContext>
                    </saml:AuthnStatement>
                    <saml:AttributeStatement>
                        <saml:Attribute Name="urn:nl-eid-gdi:1.0:ActingSubjectID">
                            <saml:AttributeValue><saml:EncryptedID/></saml:AttributeValue>
                        </saml:Attribute>
                    </saml:AttributeStatement>
                </saml:Assertion>
            </samlp:Response>
            XML);

        $document->getElementsByTagNameNS(
            self::SAML_NAMESPACE,
            'AuthnContextClassRef',
        )->item(0)->nodeValue = $actualLoa;

        $nameId = new DOMDocument();
        $nameId->appendChild($nameId->createElementNS(self::SAML_NAMESPACE, "saml:$identifierElement", '123456782'));

        if ($nameQualifier !== null) {
            $nameId->documentElement->setAttribute('NameQualifier', $nameQualifier);
        }

        $sessionKey = new XMLSecurityKey(XMLSecurityKey::AES256_CBC);
        $sessionKey->generateSessionKey();

        $encryption = new XMLSecEnc();
        $encryption->setNode($nameId->documentElement);
        $encryption->type = XMLSecEnc::Element;

        $encryptedId = $document->getElementsByTagNameNS(self::SAML_NAMESPACE, 'EncryptedID')->item(0);
        $encryptedId->appendChild($document->importNode($encryption->encryptNode($sessionKey, false), true));

        foreach ($recipientKeys as $recipient => $publicKey) {
            $key = new XMLSecurityKey(XMLSecurityKey::RSA_OAEP_MGF1P, ['type' => 'public']);
            $key->loadKey($publicKey);

            $keyEncryption = new XMLSecEnc();
            $keyEncryption->encryptKey($key, $sessionKey, false);
            $keyEncryption->encKey->setAttribute('Recipient', $recipient);

            $encryptedId->appendChild($document->importNode($keyEncryption->encKey, true));
        }

        return $document->documentElement;
    }

    /**
     * @param DOMElement $response
     * @param string $privateKey
     * @param string|null $bsnNameQualifier
     * @throws Throwable
     * @return Saml2User
     */
    protected function makeUser(
        DOMElement $response,
        string $privateKey,
        ?string $bsnNameQualifier = null,
    ): Saml2User {
        $settings = new Settings([
            'sp' => [
                'entityId' => self::ENTITY_ID,
                'assertionConsumerService' => ['url' => 'https://dv.example.test/resolve'],
                'privateKey' => $privateKey,
            ],
            ...($bsnNameQualifier !== null ? ['security' => ['bsnNameQualifier' => $bsnNameQualifier]] : []),
        ], spValidationOnly: true);

        return new Saml2User($this->createStub(Assertion::class), $response, $settings);
    }
}
