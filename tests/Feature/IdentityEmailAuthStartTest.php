<?php

namespace Tests\Feature;

use App\Models\Identity;
use App\Models\Implementation;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class IdentityEmailAuthStartTest extends TestCase
{
    /**
     * @var int
     */
    protected int $requestIpIndex = 1;

    /**
     * @return void
     */
    public function testValidateEmailDoesNotExposeExistingEmail(): void
    {
        $existingEmail = $this->makeIdentity($this->makeUniqueEmail())->email;
        $newEmail = $this->makeUniqueEmail();

        $this->postAuthJson('/api/v1/identity/validate/email', [
            'email' => $existingEmail,
        ])->assertOk()
            ->assertJsonPath('email.used', false)
            ->assertJsonPath('email.unique', true)
            ->assertJsonPath('email.valid', true);

        $this->postAuthJson('/api/v1/identity/validate/email', [
            'email' => $newEmail,
        ])->assertOk()
            ->assertJsonPath('email.used', false)
            ->assertJsonPath('email.unique', true)
            ->assertJsonPath('email.valid', true);

        $this->postAuthJson('/api/v1/identity/validate/email', [
            'email' => 'invalid-email',
        ])->assertOk()
            ->assertJsonPath('email.used', false)
            ->assertJsonPath('email.unique', true)
            ->assertJsonPath('email.valid', false);
    }

    /**
     * @return void
     */
    public function testIdentityStartCreatesIdentityAndSendsConfirmationEmailForNewEmail(): void
    {
        $email = $this->makeUniqueEmail();

        $this->assertUnifiedStartResponse($this->postAuthJson('/api/v1/identity', [
            'email' => $email,
        ]));

        $this->assertNotNull(Identity::findByEmail($email));
        $this->assertEmailConfirmationLinkSent($email);
        $this->assertNull($this->findFirstEmailRestoreEmail($email));
    }

    /**
     * @return void
     */
    public function testIdentityStartSendsRestoreEmailForExistingEmail(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $this->assertUnifiedStartResponse($this->postAuthJson('/api/v1/identity', [
            'email' => $identity->email,
        ]));

        $this->assertEmailRestoreLinkSent($identity->email);
        $this->assertArrayNotHasKey('target', $this->getEmailRestoreRedirectQuery($identity->email));
        $this->assertNull($this->findFirstEmailConfirmationEmail($identity->email));
    }

    /**
     * @return void
     */
    public function testEmailProxyAliasCreatesIdentityAndSendsConfirmationEmailForNewEmail(): void
    {
        $email = $this->makeUniqueEmail();

        $this->assertUnifiedStartResponse($this->postAuthJson('/api/v1/identity/proxy/email', [
            'email' => $email,
        ]));

        $this->assertNotNull(Identity::findByEmail($email));
        $this->assertEmailConfirmationLinkSent($email);
        $this->assertNull($this->findFirstEmailRestoreEmail($email));
    }

    /**
     * @return void
     */
    public function testEmailProxyAliasSendsRestoreEmailForExistingEmail(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $this->assertUnifiedStartResponse($this->postAuthJson('/api/v1/identity/proxy/email', [
            'email' => $identity->email,
            'source' => 'ignored_compatibility_value',
        ]));

        $this->assertEmailRestoreLinkSent($identity->email);
        $this->assertNull($this->findFirstEmailConfirmationEmail($identity->email));
    }

    /**
     * @return void
     */
    public function testIdentityStartRestoreEmailLinkPreservesTargetWhenProvided(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $this->assertUnifiedStartResponse($this->postAuthJson('/api/v1/identity', [
            'email' => $identity->email,
            'target' => 'newSignup',
        ]));

        $this->assertSame('newSignup', $this->getEmailRestoreRedirectQuery($identity->email)['target'] ?? null);
    }

    /**
     * @return void
     */
    public function testUnifiedStartRejectsExistingNonPrimaryEmails(): void
    {
        $this->assertNonPrimaryEmailRejected('/api/v1/identity', false);
        $this->assertNonPrimaryEmailRejected('/api/v1/identity', true);
        $this->assertNonPrimaryEmailRejected('/api/v1/identity/proxy/email', false);
        $this->assertNonPrimaryEmailRejected('/api/v1/identity/proxy/email', true);
    }

    /**
     * @return void
     */
    public function testUnifiedStartRejectsInvalidEmailSyntax(): void
    {
        $this->postAuthJson('/api/v1/identity', [
            'email' => 'invalid-email',
        ])->assertJsonValidationErrorFor('email');

        $this->postAuthJson('/api/v1/identity/proxy/email', [
            'email' => 'invalid-email',
        ])->assertJsonValidationErrorFor('email');
    }

    /**
     * @return void
     */
    public function testEmailConfirmationRedirectOmitsTargetWhenNotProvided(): void
    {
        $exchangeToken = 'test-confirmation-token';

        $this->getEmailConfirmationRedirect($exchangeToken)->assertRedirect(
            Implementation::general()->urlSponsorDashboard("confirmation/email/$exchangeToken")
        );
    }

    /**
     * @return void
     */
    public function testEmailConfirmationRedirectPreservesTargetWhenProvided(): void
    {
        $exchangeToken = 'test-confirmation-token';

        $this->getEmailConfirmationRedirect($exchangeToken, 'newSignup')->assertRedirect(
            Implementation::general()->urlSponsorDashboard("confirmation/email/$exchangeToken", [
                'target' => 'newSignup',
            ])
        );
    }

    /**
     * @return void
     */
    public function testEmailRestoreRedirectPreservesZeroTargetWhenProvided(): void
    {
        $emailToken = 'test-email-token';

        $this->getEmailRestoreRedirect($emailToken, '0')->assertRedirect(
            Implementation::general()->urlSponsorDashboard('identity-restore', [
                'token' => $emailToken,
                'target' => '0',
            ])
        );
    }

    /**
     * @param string $uri
     * @param array $data
     * @return TestResponse
     */
    protected function postAuthJson(string $uri, array $data): TestResponse
    {
        return $this->withServerVariables([
            'REMOTE_ADDR' => sprintf('10.0.0.%s', $this->requestIpIndex++),
        ])->postJson($uri, $data);
    }

    /**
     * @param TestResponse $response
     * @return void
     */
    protected function assertUnifiedStartResponse(TestResponse $response): void
    {
        $response->assertCreated();
        $this->assertSame('{}', $response->getContent());
    }

    /**
     * @param string $uri
     * @param bool $verified
     * @return void
     */
    protected function assertNonPrimaryEmailRejected(string $uri, bool $verified): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $email = $this->makeUniqueEmail();

        $identity->addEmail($email, $verified);

        $this->postAuthJson($uri, [
            'email' => $email,
        ])->assertJsonValidationErrorFor('email');

        $this->assertNull(Identity::findByEmail($email));
        $this->assertSame(1, $identity->emails()->whereEmail($email)->count());
        $this->assertNull($this->findFirstEmailConfirmationEmail($email));
        $this->assertNull($this->findFirstEmailRestoreEmail($email));
    }

    /**
     * @param string $exchangeToken
     * @param string|null $target
     * @return TestResponse
     */
    protected function getEmailConfirmationRedirect(string $exchangeToken, ?string $target = null): TestResponse
    {
        return $this->get('/api/v1/identity/proxy/confirmation/redirect/' . $exchangeToken . '?' . http_build_query([
            'client_type' => Implementation::FRONTEND_SPONSOR_DASHBOARD,
            'implementation_key' => Implementation::KEY_GENERAL,
            'is_mobile' => 0,
            'target' => $target,
        ]));
    }

    /**
     * @param string $emailToken
     * @param string|null $target
     * @return TestResponse
     */
    protected function getEmailRestoreRedirect(string $emailToken, ?string $target = null): TestResponse
    {
        return $this->get('/api/v1/identity/proxy/email/redirect/' . $emailToken . '?' . http_build_query([
            'client_type' => Implementation::FRONTEND_SPONSOR_DASHBOARD,
            'implementation_key' => Implementation::KEY_GENERAL,
            'is_mobile' => 0,
            'target' => $target,
        ]));
    }

    /**
     * @param string $email
     * @return array
     */
    protected function getEmailRestoreRedirectQuery(string $email): array
    {
        $link = $this->getEmailLink(
            $this->findFirstEmailRestoreEmail($email)?->content ?: '',
            'identity/proxy/email/redirect'
        );

        $this->assertNotNull($link);

        parse_str(parse_url(html_entity_decode($link), PHP_URL_QUERY) ?: '', $query);

        return $query;
    }
}
