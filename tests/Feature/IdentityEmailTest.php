<?php

namespace Tests\Feature;

use App\Mail\User\IdentityEmailVerificationMail;
use App\Models\IdentityEmail;
use App\Models\IdentityProxy;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class IdentityEmailTest extends TestCase
{
    use AssertsSentEmails;

    /**
     * @var string
     */
    protected string $apiEmailUrl = '/api/v1/identity/emails';

    /**
     * @var array
     */
    protected array $resourceStructure = [
        'id',
        'identity_address',
        'email',
        'verified',
        'primary',
        'created_at',
        'updated_at',
        'created_at_locale',
        'updated_at_locale',
    ];

    /**
     * @return void
     */
    public function testStoreNewEmail(): void
    {
        $this->storeNewEmail();
    }

    /**
     * @return void
     */
    public function testStoreNewEmailAsGuest(): void
    {
        $email = $this->makeUniqueEmail();
        $this->post($this->apiEmailUrl, ['email' => $email], $this->makeApiHeaders())->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testStoreInvalidEmail(): void
    {
        $this
            ->storeNewEmailRequest('not_valid_email')
            ->assertJsonValidationErrorFor('email');
    }

    /**
     * @return void
     */
    public function testDeleteAsAuthorEmail(): void
    {
        $proxy = $this->makeIdentityProxy($this->makeIdentity());
        $identityEmail = $this->storeNewEmail($proxy);
        $headers = $this->makeApiHeaders($proxy);

        // Delete as creator
        $this->delete("$this->apiEmailUrl/$identityEmail->id", [], $headers)->assertSuccessful();
        $this->assertNull(IdentityEmail::find($identityEmail->id));
    }

    /**
     * @return void
     */
    public function testDeleteAsDifferentUserEmail(): void
    {
        $proxy = $this->makeIdentityProxy($this->makeIdentity());
        $identityEmail = $this->storeNewEmail($proxy);
        $headers = $this->makeApiHeaders(true);

        // Delete as different user
        $this->refreshApplication();
        $this->delete("$this->apiEmailUrl/$identityEmail->id", [], $headers)->assertForbidden();
    }

    /**
     * @return void
     */
    public function testDeleteAsGuestEmail(): void
    {
        $proxy = $this->makeIdentityProxy($this->makeIdentity());
        $identityEmail = $this->storeNewEmail($proxy);
        $headers = $this->makeApiHeaders();

        // Delete as guest
        $this->refreshApplication();
        $this->delete("$this->apiEmailUrl/$identityEmail->id", [], $headers)->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testVerificationEmail(): void
    {
        $link = $this->createEmailAndGetVerificationLink();
        $this->get($link)->assertRedirectContains('redirect');
    }

    /**
     * @return void
     */
    public function testAlreadyVerifiedEmail(): void
    {
        $link = $this->createEmailAndGetVerificationLink();
        $this->get($link)->assertRedirectContains('redirect');
        $this->get($link)->assertForbidden();
    }

    /**
     * @return void
     */
    public function testVerificationEmailWithTarget(): void
    {
        $link = $this->createEmailAndGetVerificationLink('fundRequest');
        $this->get($link)->assertRedirectContains('target=fundRequest');
    }

    /**
     * @param string|null $target
     * @return string|null
     */
    private function createEmailAndGetVerificationLink(?string $target = null): ?string
    {
        $startTime = now();

        $proxy = $this->makeIdentityProxy($this->makeIdentity());
        $identityEmail = $this->storeNewEmail($proxy, null, $target);

        return $this->findFirstEmailVerificationLink($identityEmail->email, $startTime);
    }

    /**
     * @return void
     */
    public function testPrimaryEmail(): void
    {
        $primaryEmail = $this->makeUniqueEmail();
        $startTime = now();

        $identity = $this->makeIdentity($primaryEmail);
        $proxy = $this->makeIdentityProxy($identity);

        $this->assertEquals($primaryEmail, $identity->email);

        $identityEmail = $this->storeNewEmail($proxy);
        $headers = $this->makeApiHeaders($proxy);
        $setPrimaryLink = "$this->apiEmailUrl/$identityEmail->id/primary";

        // Set as primary not verified email
        $this->patch($setPrimaryLink, [], $headers)->assertForbidden();

        // Verify email
        $verificationLink = $this->findFirstEmailVerificationLink($identityEmail->email, $startTime);
        $this->get($verificationLink)->assertRedirectContains('redirect');

        // Set as primary
        $this->patch($setPrimaryLink, [], $headers)->assertSuccessful();

        $identity->unsetRelations();
        $this->assertEquals($identityEmail->email, $identity->email);

        // Set as primary already primary email
        $this->patch($setPrimaryLink, [], $headers)->assertForbidden();
    }

    /**
     * @return void
     */
    public function testResendEmail(): void
    {
        $proxy = $this->makeIdentityProxy($this->makeIdentity());
        $identityEmail = $this->storeNewEmail($proxy);
        $headers = $this->makeApiHeaders($proxy);

        $startTime = now();
        $this->post("$this->apiEmailUrl/$identityEmail->id/resend", [], $headers)->assertSuccessful();

        $this->assertMailableSent($identityEmail->email, IdentityEmailVerificationMail::class, $startTime);
        $this->assertEmailVerificationLinkSent($identityEmail->email, $startTime);
    }

    /**
     * @param IdentityProxy|bool|null $authProxy
     * @param string|null $email
     * @param string|null $target
     * @return IdentityEmail
     */
    protected function storeNewEmail(
        IdentityProxy|bool $authProxy = true,
        ?string $email = null,
        ?string $target = null
    ): IdentityEmail {
        $startTime = now();

        $email = $email ?: microtime(true) . "@example.com";
        $response = $this->storeNewEmailRequest($email, $authProxy, $target);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => $this->resourceStructure]);

        $identityEmail = IdentityEmail::find($response->json('data.id'));

        $this->assertNotEmpty($identityEmail);
        $this->assertModelExists($identityEmail);

        $this->assertMailableSent($identityEmail->email, IdentityEmailVerificationMail::class, $startTime);
        $this->assertEmailVerificationLinkSent($identityEmail->email, $startTime);

        return $identityEmail;
    }

    /**
     * @param string $email
     * @param IdentityProxy|bool $authProxy
     * @param string|null $target
     * @return TestResponse
     */
    protected function storeNewEmailRequest(
        string $email,
        IdentityProxy|bool $authProxy = true,
        ?string $target = null,
    ): TestResponse {
        return $this->post($this->apiEmailUrl, [
            'target' => $target,
            'email' => $email,
        ], $this->makeApiHeaders($authProxy, [
            'Client-Type' => 'webshop'
        ]));
    }
}
