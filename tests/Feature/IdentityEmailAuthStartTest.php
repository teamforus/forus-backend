<?php

namespace Tests\Feature;

use App\Mail\Auth\IdentityProviderLoginMail;
use App\Models\Identity;
use App\Models\Implementation;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesTestIdentityProviders;
use Tests\Traits\MakesTestOrganizations;

class IdentityEmailAuthStartTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestIdentityProviders;
    use MakesTestOrganizations;

    /**
     * @var int
     */
    protected int $requestIpIndex = 1;

    /**
     * @return void
     */
    public function testManagedRequesterReceivesGuidanceInsteadOfEmailLoginTokens(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => true,
            'allow_identity_provider_requester_provisioning' => true,
        ]);

        $connection = $this->makeEntraConnection($organization);

        foreach ([
            IdentityProviderMembership::PROVISIONING_STATUS_ACTIVE,
            IdentityProviderMembership::PROVISIONING_STATUS_DISABLED,
            IdentityProviderMembership::PROVISIONING_STATUS_DELETED,
        ] as $status) {
            foreach (['/api/v1/identity', '/api/v1/identity/proxy/email'] as $uri) {
                $membership = $this->makeIdentityProviderRequester($connection, ['provisioning_status' => $status]);

                $this->assertRequesterEmailGuidance($uri, $membership->identity);
            }
        }
    }

    /**
     * @return void
     */
    public function testDisablingProvisioningDoesNotRestoreRequesterEmailLogin(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => true,
            'allow_identity_provider_requester_provisioning' => true,
        ]);

        $membership = $this->makeIdentityProviderRequester($this->makeEntraConnection($organization));

        $organization->forceFill(['allow_identity_provider_requester_provisioning' => false])->save();

        foreach (['/api/v1/identity', '/api/v1/identity/proxy/email'] as $uri) {
            $this->assertRequesterEmailGuidance($uri, $membership->identity);
        }
    }

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
    public function testEmailConfirmationRedirectsToWebsite(): void
    {
        $exchangeToken = 'test-confirmation-token';

        Config::set('forus.front_ends.website-default', 'https://forus.test/');

        $this->getEmailConfirmationRedirect(
            $exchangeToken,
            null,
            Implementation::FRONTEND_WEBSITE
        )->assertRedirect("https://forus.test/confirmation/email/$exchangeToken");
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
     * @param string $uri
     * @param Identity $identity
     * @return void
     */
    protected function assertRequesterEmailGuidance(string $uri, Identity $identity): void
    {
        $emails = $this->getEmailOfTypeQuery($identity->email, IdentityProviderLoginMail::class);
        $count = $emails->count();

        $this->assertUnifiedStartResponse($this->postAuthJson($uri, ['email' => $identity->email]));

        $this->assertSame($count + 1, $emails->count());
        $this->assertFalse($identity->proxies()->exists());
        $this->assertNull($this->findFirstEmailRestoreEmail($identity->email));
        $this->assertNull($this->findFirstEmailConfirmationEmail($identity->email));
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
     * @param string $clientType
     * @return TestResponse
     */
    protected function getEmailConfirmationRedirect(
        string $exchangeToken,
        ?string $target = null,
        string $clientType = Implementation::FRONTEND_SPONSOR_DASHBOARD
    ): TestResponse {
        return $this->get('/api/v1/identity/proxy/confirmation/redirect/' . $exchangeToken . '?' . http_build_query([
            'client_type' => $clientType,
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
