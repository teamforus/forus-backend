<?php

namespace Tests\Feature\IdentityProviders;

use App\Models\IdentityProxy;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderErrorCode;
use App\Services\IdentityProviderService\Models\IdentityProviderAdminSession;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderTenantReservation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Fakes\FakeOpenIdClientService;
use Tests\TestCase;
use Tests\Traits\MakesTestIdentityProviders;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class IdentityProviderConsentTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestIdentityProviders;
    use MakesTestOrganizations;

    protected string $tenantId;
    protected FakeOpenIdClientService $openIdClient;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Config::set([
            'identity_providers.enabled' => true,
            'identity_providers.entra.client_id' => 'test-client',
            'identity_providers.entra.client_secret' => 'test-secret',
            'identity_providers.entra.admin_consent_redirect_url' => 'https://api.example.test/admin-consent/callback',
        ]);

        Implementation::general()->update([
            'url_sponsor' => 'https://sponsor.example.test',
            'url_provider' => 'https://provider.example.test',
        ]);

        $this->withHeaders(['Client-Type' => Implementation::FRONTEND_PROVIDER_DASHBOARD]);
        $this->tenantId = Str::uuid()->toString();

        $this->openIdClient = $this->bindFakeOpenIdClient([
            'tid' => $this->tenantId,
            'oid' => Str::uuid()->toString(),
            'wids' => ['62e90394-69f5-4237-9190-012177145e10'],
        ]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOwnerCanCompleteConsentWithoutLocalSessionAndCannotReplayIt(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        $ownerProxy = $this->makeIdentityProxy($organization->identity);
        [$session, $state] = $this->startConsent($organization, $ownerProxy);

        $this->assertSame(IdentityProviderAdminSession::STATUS_VERIFYING_TENANT, $session->status);
        $ownerProxy->deactivateByLogout();

        $consentState = $this->verifyTenant($state);

        $this->assertSame(IdentityProviderAdminSession::STATUS_AWAITING_CONSENT, $session->refresh()->status);
        $this->assertSame($this->tenantId, $session->expected_tenant_id);

        $callback = ['state' => $consentState, 'tenant' => $this->tenantId, 'admin_consent' => 'True'];

        $this->assertConsentRedirect(
            $this->apiIdentityProviderAdminConsentCallbackRequest($callback),
            "https://provider.example.test/organisaties/$organization->id/single-sign-on",
        )
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('Cache-Control', 'no-store, private');

        $connection = $organization->identity_provider_connection()->firstOrFail();

        $this->assertTrue($connection->isEnabled());
        $this->assertSame($this->tenantId, $connection->tenant_id);
        $this->assertSame(IdentityProviderAdminSession::STATUS_CONFIRMED, $session->refresh()->status);
        $this->assertSame($connection->id, $session->connection_id);
        $this->assertSame($connection->id, IdentityProviderTenantReservation::where('tenant_id', $this->tenantId)->value('connection_id'));

        $this->assertIdentityProviderCallbackError(
            $this->apiIdentityProviderAdminConsentCallbackRequest($callback),
            IdentityProviderErrorCode::ADMIN_CONSENT_SESSION_REPLAYED,
            $session->final_url,
        );

        $this->assertSame(IdentityProviderAdminSession::STATUS_CONFIRMED, $session->refresh()->status);
        $this->assertSame([$connection->id], $organization->identity_provider_connections()->pluck('id')->all());

        $this->assertSame([
            IdentityProviderConnection::EVENT_CONNECTION_CONNECTED,
        ], $connection->logs()->pluck('event')->all());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testTenantVerificationRequiresAdministratorRole(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        $this->openIdClient->setClaims(['tid' => $this->tenantId, 'oid' => Str::uuid()->toString(), 'wids' => []]);
        [$session, $state] = $this->startConsent($organization);

        $this->assertIdentityProviderCallbackError(
            $this->apiIdentityProviderOidcCallbackRequest(['state' => $state, 'code' => 'test-code']),
            IdentityProviderErrorCode::ENTRA_ADMIN_ROLE_REQUIRED,
            $session->final_url,
        );

        $this->assertSame(IdentityProviderAdminSession::STATUS_ERROR, $session->refresh()->status);
        $this->assertFalse($organization->identity_provider_connections()->exists());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testDeniedConsentDoesNotCreateConnection(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        [$session, $state] = $this->startConsent($organization);

        $this->assertIdentityProviderCallbackError(
            $this->apiIdentityProviderAdminConsentCallbackRequest([
                'state' => $this->verifyTenant($state),
                'admin_consent' => 'False',
                'error' => 'access_denied',
            ]),
            IdentityProviderErrorCode::ADMIN_CONSENT_DENIED,
            $session->final_url,
        );

        $this->assertFalse($organization->identity_provider_connections()->exists());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testConsentMustMatchVerifiedTenant(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        [$session, $state] = $this->startConsent($organization);

        $this->assertIdentityProviderCallbackError(
            $this->apiIdentityProviderAdminConsentCallbackRequest([
                'state' => $this->verifyTenant($state),
                'tenant' => Str::uuid()->toString(),
                'admin_consent' => 'True',
            ]),
            IdentityProviderErrorCode::ADMIN_CONSENT_TENANT_MISMATCH,
            $session->final_url,
        );

        $this->assertFalse($organization->identity_provider_connections()->exists());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testExpiredTenantVerificationCannotAdvance(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        [$session, $state] = $this->startConsent($organization);
        $this->travelTo($session->expires_at->copy()->addSecond());

        $this->assertIdentityProviderCallbackError(
            $this->apiIdentityProviderOidcCallbackRequest(['state' => $state, 'code' => 'test-code']),
            IdentityProviderErrorCode::ENTRA_SESSION_EXPIRED,
            $session->final_url,
        );

        $this->assertSame(IdentityProviderAdminSession::STATUS_EXPIRED, $session->refresh()->status);
        $this->assertFalse($organization->identity_provider_connections()->exists());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testExpiredConsentCannotCreateConnection(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        [$session, $state] = $this->startConsent($organization);
        $consentState = $this->verifyTenant($state);
        $this->travelTo($session->expires_at->copy()->addSecond());

        $this->assertIdentityProviderCallbackError(
            $this->apiIdentityProviderAdminConsentCallbackRequest([
                'state' => $consentState, 'tenant' => $this->tenantId, 'admin_consent' => 'True',
            ]),
            IdentityProviderErrorCode::ADMIN_CONSENT_SESSION_EXPIRED,
            $session->final_url,
        );

        $this->assertSame(IdentityProviderAdminSession::STATUS_EXPIRED, $session->refresh()->status);
        $this->assertFalse($organization->identity_provider_connections()->exists());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testConsentRechecksOrganizationOwner(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        [$session, $state] = $this->startConsent($organization);
        $consentState = $this->verifyTenant($state);
        $organization->update(['identity_address' => $this->makeIdentity()->address]);

        $this->assertIdentityProviderCallbackError(
            $this->apiIdentityProviderAdminConsentCallbackRequest([
                'state' => $consentState, 'tenant' => $this->tenantId, 'admin_consent' => 'True',
            ]),
            IdentityProviderErrorCode::ADMIN_CONSENT_OWNER_CHANGED,
            $session->final_url,
        );

        $this->assertFalse($organization->identity_provider_connections()->exists());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testConsentRechecksOrganizationCapability(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        [$session, $state] = $this->startConsent($organization);
        $consentState = $this->verifyTenant($state);
        $organization->forceFill(['allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_NO])->save();

        $this->assertIdentityProviderCallbackError(
            $this->apiIdentityProviderAdminConsentCallbackRequest([
                'state' => $consentState, 'tenant' => $this->tenantId, 'admin_consent' => 'True',
            ]),
            IdentityProviderErrorCode::ORGANIZATION_IDENTITY_PROVIDER_DISABLED,
            $session->final_url,
        );

        $this->assertFalse($organization->identity_provider_connections()->exists());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testTenantRemainsReservedForItsOrganizationAfterDisconnect(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        [$session, $state] = $this->startConsent($organization);

        $this->assertConsentRedirect(
            $this->apiIdentityProviderAdminConsentCallbackRequest([
                'state' => $this->verifyTenant($state), 'tenant' => $this->tenantId, 'admin_consent' => 'True',
            ]),
            $session->final_url,
        );

        $connection = $organization->identity_provider_connection()->firstOrFail();

        $otherOrganization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        foreach ([false, true] as $disconnected) {
            if ($disconnected) {
                $this->apiChangeIdentityProviderConnectionStateRequest(
                    $organization,
                    $connection,
                    'disconnect',
                    $organization->identity,
                )->assertOk();
            }

            [$otherSession, $otherState] = $this->startConsent($otherOrganization);

            $this->assertIdentityProviderCallbackError(
                $this->apiIdentityProviderOidcCallbackRequest(['state' => $otherState, 'code' => 'test-code']),
                IdentityProviderErrorCode::ENTRA_TENANT_ALREADY_CONNECTED,
                $otherSession->final_url,
            );

            $this->assertFalse($otherOrganization->identity_provider_connections()->exists());
        }

        $this->assertSame($organization->id, IdentityProviderTenantReservation::where('tenant_id', $this->tenantId)->value('organization_id'));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testNewConsentPreservesHistoryAndRejectsAnOlderAttempt(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        [$session, $state] = $this->startConsent($organization);

        $this->assertConsentRedirect(
            $this->apiIdentityProviderAdminConsentCallbackRequest([
                'state' => $this->verifyTenant($state), 'tenant' => $this->tenantId, 'admin_consent' => 'True',
            ]),
            $session->final_url,
        );

        $previousConnection = $organization->identity_provider_connection()->firstOrFail();

        $this->apiChangeIdentityProviderConnectionStateRequest(
            $organization,
            $previousConnection,
            'disconnect',
            $organization->identity,
        )->assertOk();

        [$olderSession, $olderState] = $this->startConsent($organization);
        $olderConsentState = $this->verifyTenant($olderState);
        [$newSession, $newState] = $this->startConsent($organization);

        $this->assertConsentRedirect(
            $this->apiIdentityProviderAdminConsentCallbackRequest([
                'state' => $this->verifyTenant($newState), 'tenant' => $this->tenantId, 'admin_consent' => 'True',
            ]),
            $newSession->final_url,
        );

        $connection = $organization->identity_provider_connection()->firstOrFail();

        $this->assertNotSame($previousConnection->id, $connection->id);

        $this->assertIdentityProviderCallbackError(
            $this->apiIdentityProviderAdminConsentCallbackRequest([
                'state' => $olderConsentState, 'tenant' => $this->tenantId, 'admin_consent' => 'True',
            ]),
            IdentityProviderErrorCode::CONNECTION_CHANGED_CONCURRENTLY,
            $olderSession->final_url,
        );

        $this->assertSame($connection->id, $organization->identity_provider_connection()->firstOrFail()->id);
        $this->assertSame($connection->id, IdentityProviderTenantReservation::where('tenant_id', $this->tenantId)->value('connection_id'));

        $this->apiGetIdentityProviderConnectionHistoryRequest($organization, [], $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.*.uid', [$previousConnection->uid]);
    }

    /**
     * @param Organization $organization
     * @param IdentityProxy|null $proxy
     * @return array{IdentityProviderAdminSession, string}
     */
    protected function startConsent(Organization $organization, ?IdentityProxy $proxy = null): array
    {
        $response = $this->apiStartIdentityProviderConsentRequest($organization, $proxy ?? $organization->identity)->assertCreated();

        parse_str(parse_url($response->json('data.redirect_url'), PHP_URL_QUERY), $query);

        $session = IdentityProviderAdminSession::where('oidc_state_hash', hash('sha256', $query['state']))->firstOrFail();

        return [$session, $query['state']];
    }

    /**
     * @param string $state
     * @return string
     */
    protected function verifyTenant(string $state): string
    {
        $response = $this->apiIdentityProviderOidcCallbackRequest(['state' => $state, 'code' => 'test-code'])->assertRedirect();

        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('state', $query);

        return $query['state'];
    }

    /**
     * @param TestResponse $response
     * @param string $finalUrl
     * @return TestResponse
     */
    protected function assertConsentRedirect(TestResponse $response, string $finalUrl): TestResponse
    {
        $response->assertRedirect();
        $this->assertSame($finalUrl, rtrim($response->headers->get('Location'), '?'));

        return $response;
    }
}
