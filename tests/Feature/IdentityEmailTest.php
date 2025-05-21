<?php

namespace Tests\Feature;

use App\Mail\User\IdentityEmailVerificationMail;
use App\Models\Identity;
use App\Models\IdentityEmail;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class IdentityEmailTest extends TestCase
{
    use AssertsSentEmails;
    use DatabaseTransactions;

    /**
     * The API endpoint for handling identity emails.
     *
     * @var string
     */
    protected string $apiEmailUrl = '/api/v1/identity/emails';

    /**
     * The structure of the identity email resource in JSON responses.
     *
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
     * Tests storing a new email for an identity.
     *
     * @return void
     */
    public function testStoreNewEmail(): void
    {
        $this->addEmailAndAssertLinkeSent($this->makeIdentity(), $this->makeUniqueEmail());
    }

    /**
     * Tests attempting to store a new email as a guest user, expecting unauthorized access.
     *
     * @return void
     */
    public function testStoreNewEmailAsGuest(): void
    {
        $this->postJson($this->apiEmailUrl, [
            'email' => $this->makeUniqueEmail(),
        ], $this->makeApiHeaders())->assertUnauthorized();
    }

    /**
     * Tests storing an invalid email, expecting a JSON validation error for the 'email' field.
     *
     * @return void
     */
    public function testStoreInvalidEmail(): void
    {
        $this->addEmail($this->makeIdentity(), 'not_a_valid_email')->assertJsonValidationErrorFor('email');
    }

    /**
     * Tests deleting an email as the author of the identity, expecting a successful deletion.
     *
     * @return void
     */
    public function testDeleteAsAuthorEmail(): void
    {
        $identity = $this->makeIdentity();
        $identityEmail = $this->addEmailAndAssertLinkeSent($identity, $this->makeUniqueEmail());

        $this->deleteEmail($identity, $identityEmail)->assertSuccessful();
        $this->assertNull(IdentityEmail::find($identityEmail->id));
    }

    /**
     * Tests attempting to delete an email as a different user, expecting forbidden access.
     *
     * @return void
     */
    public function testDeleteAsDifferentUserEmail(): void
    {
        $identity = $this->makeIdentity();
        $identityEmail = $this->addEmailAndAssertLinkeSent($identity, $this->makeUniqueEmail());

        // Delete as different user
        $this->deleteEmail($this->makeIdentity(), $identityEmail)->assertForbidden();
    }

    /**
     * Tests attempting to delete an email as a guest, expecting unauthorized access.
     *
     * @return void
     */
    public function testDeleteAsGuestEmail(): void
    {
        $identity = $this->makeIdentity();
        $identityEmail = $this->addEmailAndAssertLinkeSent($identity, $this->makeUniqueEmail());

        // Delete as guest
        $this->deleteEmail(null, $identityEmail)->assertUnauthorized();
    }

    /**
     * Tests the verification process of an email when no target is specified.
     *
     * @param string|null $target The target parameter for the verification link.
     * @return void
     */
    public function testVerificationEmailNoTarget(string $target = null): void
    {
        $verification = $this->createEmailAndGetVerificationLink($target);
        $redirectString = 'redirect' . ($target ? ('?target=' . $target) : '');

        $this->get($verification->link)->assertRedirectContains($redirectString);

        $this->verifyEmail($verification->email->identity, $verification->email->verification_token)->assertSuccessful();
        $this->assertTrue($verification->email->fresh()->verified);
    }

    /**
     * Tests sending a verification email with a target specified for an identity.
     *
     * @return void
     */
    public function testVerificationEmailWithTarget(): void
    {
        $this->testVerificationEmailNoTarget('fundRequest');
    }

    /**
     * Tests attempting to verify an already verified email, expecting forbidden access.
     *
     * @return void
     */
    public function testAlreadyVerifiedEmail(): void
    {
        $verification = $this->createEmailAndGetVerificationLink();

        $this->verifyEmail($verification->email->identity, $verification->email->verification_token)->assertSuccessful();
        $this->assertTrue($verification->email->fresh()->verified);

        $this->verifyEmail($verification->email->identity, $verification->email->verification_token)->assertForbidden();
    }

    /**
     * Tests the limit on the number of identity emails per identity, expecting validation error when exceeding the limit.
     *
     * @return void
     */
    public function testMaxIdentityEmails()
    {
        $identity = $this->makeIdentity();

        // Loop to store the maximum allowed emails for an identity
        for ($i = 1; $i <= Config::get('forus.mail.max_identity_emails'); $i++) {
            // Generate a unique random email
            $email = $this->makeUniqueEmail();

            // Store the new email request and assert status and JSON structure
            $this->addEmail($identity, $email)
                ->assertStatus(201)
                ->assertJsonStructure(['data' => $this->resourceStructure]);
        }

        // Attempt to store one more email than allowed and assert JSON validation error for 'email'
        $email = $this->makeUniqueEmail();
        $response = $this->addEmail($identity, $email);
        $response->assertJsonValidationErrorFor('email');
    }

    /**
     * Tests the functionality of setting an email as the primary email for an identity.
     *
     * This test ensures that:
     * 1. The initial email set during identity creation is marked as primary.
     * 2. A non-verified email cannot be set as the primary email.
     * 3. Once verified, an email can be successfully set as the primary email.
     * 4. Setting an already primary email as primary again results in a forbidden action.
     *
     * @return void
     */
    public function testPrimaryEmail(): void
    {
        $primaryEmail = $this->makeUniqueEmail();

        $identity = $this->makeIdentity($primaryEmail);
        $identityEmail = $this->addEmailAndAssertLinkeSent($identity, $this->makeUniqueEmail());

        // Assert initial email is primary
        $this->assertEquals($primaryEmail, $identity->email);

        // Assert non verified email can't be set as primary
        $this->setEmailPrimary($identity, $identityEmail)->assertForbidden();

        // Verify email
        $this->verifyEmail($identity, $identityEmail->verification_token)->assertSuccessful();

        // Assert verified email can be used as primary
        $this->setEmailPrimary($identity, $identityEmail)->assertSuccessful();
        $this->assertEquals($identityEmail->email, $identity->fresh()->email);

        // Set as primary already primary email
        $this->setEmailPrimary($identity, $identityEmail)->assertForbidden();
    }

    /**
     * Tests resending the verification email for an identity email.
     *
     * @return void
     */
    public function testResendEmail(): void
    {
        $identity = $this->makeIdentity();
        $startTime = now();
        $identityEmail = $this->addEmailAndAssertLinkeSent($identity, $this->makeUniqueEmail());

        $this->resendEmail($identity, $identityEmail)->assertSuccessful();

        $this->assertMailableSent($identityEmail->email, IdentityEmailVerificationMail::class, $startTime);
        $this->assertEmailVerificationLinkSent($identityEmail->email, $startTime);
    }

    /**
     * Adds an email to the given identity and asserts that a verification link has been sent.
     *
     * @param Identity $identity The identity to which the email will be added.
     * @param string $email The email address to add.
     * @param string|null $target The target for the email addition (optional).
     *
     * @return IdentityEmail The newly created identity email.
     */
    protected function addEmailAndAssertLinkeSent(Identity $identity, string $email, ?string $target = null): IdentityEmail
    {
        $startTime = now();
        $response = $this->addEmail($identity, $email, $target);

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
     * Adds an email to the specified identity.
     *
     * @param Identity $identity The identity to which the email is being added.
     * @param string $email The email address to add.
     * @param string|null $target An optional target parameter for the email addition process.
     * @return TestResponse The response from the API call.
     */
    protected function addEmail(Identity $identity, string $email, ?string $target = null): TestResponse
    {
        return $this->postJson($this->apiEmailUrl, [
            'target' => $target,
            'email' => $email,
        ], $this->makeApiHeaders($identity, [
            'Client-Type' => 'webshop',
        ]));
    }

    /**
     * Verifies an email for a given identity using a verification token.
     *
     * @param Identity $identity The identity associated with the email to be verified.
     * @param string $token The verification token used to verify the email.
     * @return TestResponse The response from the API after attempting to verify the email.
     */
    protected function verifyEmail(Identity $identity, string $token): TestResponse
    {
        return $this->postJson("$this->apiEmailUrl/$token/verify", [], $this->makeApiHeaders($identity));
    }

    /**
     * Deletes an email associated with an identity.
     *
     * @param Identity|null $identity The identity to which the email belongs.
     * @param IdentityEmail $identityEmail The email to be deleted.
     *
     * @return TestResponse The response from the delete request.
     */
    protected function deleteEmail(?Identity $identity, IdentityEmail $identityEmail): TestResponse
    {
        return $this->deleteJson("$this->apiEmailUrl/$identityEmail->id", [], $identity ? $this->makeApiHeaders($identity) : []);
    }

    /**
     * Resends an email verification for a given identity and its associated email.
     *
     * @param Identity $identity The identity to which the email belongs.
     * @param IdentityEmail $identityEmail The specific email to resend the verification for.
     * @return TestResponse The response from the API request to resend the email.
     */
    protected function resendEmail(Identity $identity, IdentityEmail $identityEmail): TestResponse
    {
        return $this->postJson("$this->apiEmailUrl/$identityEmail->id/resend", [], $this->makeApiHeaders($identity));
    }

    /**
     * Resends the primary email for a given identity and email.
     *
     * @param Identity $identity The identity associated with the email.
     * @param IdentityEmail $identityEmail The email to be set as primary.
     * @return TestResponse The response from the API request.
     */
    protected function setEmailPrimary(Identity $identity, IdentityEmail $identityEmail): TestResponse
    {
        return $this->patchJson("$this->apiEmailUrl/$identityEmail->id/primary", [], $this->makeApiHeaders($identity));
    }

    /**
     * Creates a new email for an identity and retrieves its verification link.
     *
     * @param string|null $target The target parameter to pass to storeNewEmail method.
     * @return object An anonymous class instance containing the verification link and the IdentityEmail object.
     */
    private function createEmailAndGetVerificationLink(?string $target = null): object
    {
        $startTime = now();

        // Create an identity proxy and store a new email with it
        $identity = $this->makeIdentity();
        $identityEmail = $this->addEmailAndAssertLinkeSent($identity, $this->makeUniqueEmail(), $target);

        // Find the first email verification link sent after the start time
        $link = $this->findFirstEmailVerificationLink($identityEmail->email, $startTime);

        // Return an anonymous class instance with the link and identity email
        return new class ($link, $identityEmail) {
            public function __construct(public string $link, public IdentityEmail $email)
            {
            }
        };
    }
}
