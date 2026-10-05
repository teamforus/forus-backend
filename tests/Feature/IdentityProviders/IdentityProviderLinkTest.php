<?php

namespace Tests\Feature\IdentityProviders;

use App\Models\Employee;
use App\Models\IdentityProxy;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderErrorCode;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderOidcSession;
use App\Services\IdentityProviderService\Services\IdentityProviderAccountLinkService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\MakesTestIdentityProviders;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class IdentityProviderLinkTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestIdentityProviders;
    use MakesTestOrganizations;

    protected Employee $employee;
    protected string $objectId;
    protected IdentityProviderConnection $entraConnection;

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
        ]);

        Implementation::general()->update([
            'url_sponsor' => 'https://sponsor.example.test',
            'url_provider' => 'https://provider.example.test',
            'url_validator' => 'https://validator.example.test',
        ]);

        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        $this->employee = $organization->addEmployee($this->makeIdentity());
        $this->entraConnection = $this->makeEntraConnection($organization);
        $this->objectId = Str::uuid()->toString();

        $this->bindFakeOpenIdClient([
            'tid' => $this->entraConnection->tenant_id,
            'oid' => $this->objectId,
            'iss' => $this->entraConnection->issuer,
            'sub' => 'test-subject',
            'acct' => 0,
        ]);

        $this->withHeaders(['Client-Type' => Implementation::FRONTEND_PROVIDER_DASHBOARD]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testListingReturnsOwnClaimedLinksAndEligibleConnections(): void
    {
        $membership = $this->makeIdentityProviderMembership($this->entraConnection, $this->employee);

        $this->makeIdentityProviderMembership(
            $this->entraConnection,
            $this->entraConnection->organization->addEmployee($this->makeIdentity()),
        );

        $availableOrganization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        $availableOrganization->addEmployee($this->employee->identity);
        $availableConnection = $this->makeEntraConnection($availableOrganization);

        $ownedOrganization = $this->makeTestOrganization($this->employee->identity, [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        $this->makeEntraConnection($ownedOrganization);

        $this->apiGetIdentityProviderLinksRequest($this->employee->identity)
            ->assertOk()
            ->assertJsonPath('data.*.uid', [$membership->uid])
            ->assertJsonPath('data.0.organization.id', $this->entraConnection->organization_id)
            ->assertJsonPath('meta.can_manage_links', true)
            ->assertJsonPath('meta.available_connections.*.uid', [$availableConnection->uid]);

        $availableConnection->update(['status' => IdentityProviderConnection::STATUS_PAUSED]);

        $this->apiGetIdentityProviderLinksRequest($this->employee->identity)
            ->assertOk()
            ->assertJsonPath('meta.available_connections', []);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOwnersAndOutsidersCannotStartLinking(): void
    {
        foreach ([$this->entraConnection->organization->identity, $this->makeIdentity()] as $identity) {
            $this->apiStartIdentityProviderLinkRequest($this->entraConnection, $identity)->assertForbidden();
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testEntraSessionCannotManageLinks(): void
    {
        $otherOrganization = $this->makeTestOrganization($this->makeIdentity());
        $otherConnection = $this->makeEntraConnection($otherOrganization);

        $membership = $this->makeIdentityProviderMembership(
            $otherConnection,
            $otherOrganization->addEmployee($this->employee->identity),
        );

        $proxy = $this->makeIdentityProviderProxy($membership);

        $this->apiGetIdentityProviderLinksRequest($proxy)
            ->assertOk()
            ->assertJsonPath('data.*.uid', [$membership->uid])
            ->assertJsonPath('meta.can_manage_links', false)
            ->assertJsonPath('meta.available_connections', []);

        $this->apiStartIdentityProviderLinkRequest($this->entraConnection, $proxy)->assertForbidden();
        $this->apiDeleteIdentityProviderLinkRequest($membership, $proxy)->assertForbidden();

        $this->assertTrue($membership->refresh()->isClaimed());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRolelessEmployeeCanLinkFromProviderDashboardAndCannotReplayCallback(): void
    {
        [$session, $state, $browserToken] = $this->startLink();
        $finalUrl = 'https://provider.example.test/beveiliging/gekoppelde-accounts';
        $exchangeToken = $this->resolveLink($session, $state);

        $this->assertSame($finalUrl, $session->final_url);
        $this->assertFalse($this->entraConnection->memberships()->exists());
        $this->assertFalse($this->entraConnection->logs()->exists());

        $this->assertIdentityProviderCallbackError(
            $this->apiIdentityProviderOidcCallbackRequest(['state' => $state, 'code' => 'test-code']),
            IdentityProviderErrorCode::SESSION_EXPIRED,
            $finalUrl,
        );

        $credentials = ['exchange_token' => $exchangeToken, 'browser_token' => $browserToken];

        $this->apiExchangeIdentityProviderLoginRequest($credentials)->assertForbidden();
        $this->apiCompleteIdentityProviderLinkRequest($session, $credentials, $this->employee->identity)->assertNoContent();

        $membership = $this->entraConnection->memberships()->sole();
        $externalIdentity = $membership->external_identity;

        $this->assertTrue($membership->isClaimed());
        $this->assertSame($this->employee->id, $membership->employee_id);
        $this->assertSame($this->employee->identity->id, $membership->identity_id);
        $this->assertSame(IdentityProviderAccountLinkService::CONSENT_VERSION, $membership->consent_version);
        $this->assertNotNull($membership->consented_at);

        $this->assertSame([
            'tenant_id' => $this->entraConnection->tenant_id,
            'object_id' => $this->objectId,
            'issuer' => $this->entraConnection->issuer,
            'subject' => 'test-subject',
            'identity_id' => $this->employee->identity->id,
        ], $externalIdentity->only(['tenant_id', 'object_id', 'issuer', 'subject', 'identity_id']));

        $this->assertIdentityProviderCallbackError(
            $this->apiIdentityProviderOidcCallbackRequest(['state' => $state, 'code' => 'test-code']),
            IdentityProviderErrorCode::SESSION_EXPIRED,
            $finalUrl,
        );

        $this->assertTrue($session->refresh()->isResolved());
        $this->assertSame($membership->id, $session->membership_id);
        $this->assertNull($session->verified_account);
        $this->assertNull($session->browser_token_hash);
        $this->assertNotNull($session->exchange_consumed_at);

        $this->apiCompleteIdentityProviderLinkRequest($session, $credentials, $this->employee->identity)->assertConflict();

        $this->assertSame([$membership->id], $this->entraConnection->memberships()->pluck('id')->all());

        $this->assertSame([
            IdentityProviderConnection::EVENT_ENTRA_LINK_SELF_LINKED,
        ], $this->entraConnection->logs()->pluck('event')->all());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testExternalAccountLinkedToAnotherIdentityCannotBeClaimed(): void
    {
        $otherEmployee = $this->entraConnection->organization->addEmployee($this->makeIdentity());
        $membership = $this->makeIdentityProviderMembership($this->entraConnection, $otherEmployee);
        $membership->external_identity->update(['object_id' => $this->objectId]);
        [$session, $state, $browserToken] = $this->startLink();
        $exchangeToken = $this->resolveLink($session, $state);

        $response = $this->apiCompleteIdentityProviderLinkRequest($session, [
            'exchange_token' => $exchangeToken, 'browser_token' => $browserToken,
        ], $this->employee->identity)->assertConflict();

        $this->assertStringContainsString(rtrim(__('exceptions.identity_providers.account_link_conflict'), '.'), $response->json('message'));

        $this->assertSame([$membership->id], $this->entraConnection->memberships()->pluck('id')->all());
        $this->assertSame($otherEmployee->identity->id, $membership->refresh()->identity_id);
        $this->assertSame($otherEmployee->identity->id, $membership->external_identity->identity_id);
        $this->assertNull($session->refresh()->membership_id);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testCompletionRechecksEmploymentAfterReturningToValidatorDashboard(): void
    {
        $this->withHeaders(['Client-Type' => Implementation::FRONTEND_VALIDATOR_DASHBOARD]);
        [$session, $state, $browserToken] = $this->startLink();
        $exchangeToken = $this->resolveLink($session, $state);

        $this->assertSame('https://validator.example.test/beveiliging/gekoppelde-accounts', $session->final_url);
        $this->employee->delete();

        $response = $this->apiCompleteIdentityProviderLinkRequest($session, [
            'exchange_token' => $exchangeToken, 'browser_token' => $browserToken,
        ], $this->employee->identity)->assertConflict();

        $this->assertStringContainsString(rtrim(__('exceptions.identity_providers.account_link_unavailable'), '.'), $response->json('message'));

        $this->assertFalse($this->entraConnection->memberships()->exists());
        $this->assertNull($session->refresh()->membership_id);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testExpiredCallbackCannotLinkAndReturnsToSponsorDashboard(): void
    {
        $this->withHeaders(['Client-Type' => Implementation::FRONTEND_SPONSOR_DASHBOARD]);
        [$session, $state] = $this->startLink();
        $this->travelTo($session->expires_at->copy()->addSecond());

        $this->assertIdentityProviderCallbackError(
            $this->apiIdentityProviderOidcCallbackRequest(['state' => $state, 'code' => 'test-code']),
            IdentityProviderErrorCode::SESSION_EXPIRED,
            'https://sponsor.example.test/beveiliging/gekoppelde-accounts',
        );

        $this->assertSame(IdentityProviderOidcSession::STATUS_EXPIRED, $session->refresh()->status);
        $this->assertFalse($this->entraConnection->memberships()->exists());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAnotherIdentityCannotUnlinkMembership(): void
    {
        $membership = $this->makeIdentityProviderMembership($this->entraConnection, $this->employee);

        $this->apiDeleteIdentityProviderLinkRequest($membership, $this->makeIdentity())->assertNotFound();

        $this->assertTrue($membership->refresh()->isClaimed());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testCompletionRequiresInitiatingIdentityAndBrowserToken(): void
    {
        [$session, $state, $browserToken] = $this->startLink();
        $exchangeToken = $this->resolveLink($session, $state);
        $credentials = ['exchange_token' => $exchangeToken, 'browser_token' => $browserToken];

        $this->assertFalse($this->entraConnection->memberships()->exists());
        $this->apiCompleteIdentityProviderLinkRequest($session, $credentials)->assertUnauthorized();
        $this->apiCompleteIdentityProviderLinkRequest($session, $credentials, $this->makeIdentity())->assertNotFound();

        $this->apiCompleteIdentityProviderLinkRequest($session, [
            'exchange_token' => $exchangeToken,
        ], $this->employee->identity)->assertUnprocessable()->assertJsonValidationErrors('browser_token');

        $this->apiCompleteIdentityProviderLinkRequest($session, [
            ...$credentials, 'browser_token' => Str::random(64),
        ], $this->employee->identity)->assertConflict();

        $this->apiCompleteIdentityProviderLinkRequest($session, [
            ...$credentials, 'exchange_token' => Str::random(64),
        ], $this->employee->identity)->assertConflict();

        $this->assertFalse($this->entraConnection->memberships()->exists());

        $this->apiCompleteIdentityProviderLinkRequest($session, $credentials, $this->employee->identity)->assertNoContent();

        $this->assertSame($this->employee->identity->id, $this->entraConnection->memberships()->sole()->identity_id);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testCompletionRequiresActiveLocalSession(): void
    {
        $localProxy = $this->makeIdentityProxy($this->employee->identity);
        [$session, $state, $browserToken] = $this->startLink($localProxy);
        $exchangeToken = $this->resolveLink($session, $state);
        $credentials = ['exchange_token' => $exchangeToken, 'browser_token' => $browserToken];
        $otherOrganization = $this->makeTestOrganization($this->makeIdentity());

        $otherMembership = $this->makeIdentityProviderMembership(
            $this->makeEntraConnection($otherOrganization),
            $otherOrganization->addEmployee($this->employee->identity),
        );

        $this->apiCompleteIdentityProviderLinkRequest($session, $credentials, $this->makeIdentityProviderProxy($otherMembership))
            ->assertForbidden();

        $localProxy->deactivateByLogout();

        $this->apiCompleteIdentityProviderLinkRequest($session, $credentials, $localProxy)->assertUnauthorized();

        $this->assertFalse($this->entraConnection->memberships()->exists());
    }

    /**
     * @return void
     */
    public function testExpiredCompletionCannotLinkAccount(): void
    {
        [$session, $state, $browserToken] = $this->startLink();
        $exchangeToken = $this->resolveLink($session, $state);
        $this->travelTo($session->refresh()->exchange_expires_at->copy()->addSecond());

        $this->apiCompleteIdentityProviderLinkRequest($session, [
            'exchange_token' => $exchangeToken, 'browser_token' => $browserToken,
        ], $this->employee->identity)->assertConflict();

        $this->assertFalse($this->entraConnection->memberships()->exists());

        $this->artisan('identity-provider:sessions-clean')->assertSuccessful();
        $this->assertNull($session->refresh()->verified_account);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testCompletionRechecksConnectionState(): void
    {
        [$session, $state, $browserToken] = $this->startLink();
        $exchangeToken = $this->resolveLink($session, $state);

        $this->apiChangeIdentityProviderConnectionStateRequest(
            $this->entraConnection->organization,
            $this->entraConnection,
            'pause',
            $this->entraConnection->organization->identity,
        )->assertOk();

        $this->apiCompleteIdentityProviderLinkRequest($session, [
            'exchange_token' => $exchangeToken, 'browser_token' => $browserToken,
        ], $this->employee->identity)->assertConflict();

        $this->assertFalse($this->entraConnection->memberships()->exists());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUnlinkAfterCapabilityRemovalRevokesOnlyLinkedSessionsAndInvalidatesPendingLink(): void
    {
        [$pending, $state] = $this->startLink();
        [$prepared, $preparedState, $browserToken] = $this->startLink();
        $exchangeToken = $this->resolveLink($prepared, $preparedState);
        $membership = $this->makeIdentityProviderMembership($this->entraConnection, $this->employee);
        $entraHeaders = $this->makeApiHeaders($this->makeIdentityProviderProxy($membership));
        $localProxy = $this->makeIdentityProxy($this->employee->identity);

        $otherMembership = $this->makeIdentityProviderMembership(
            $this->entraConnection,
            $this->entraConnection->organization->addEmployee($this->makeIdentity()),
        );

        $otherHeaders = $this->makeApiHeaders($this->makeIdentityProviderProxy($otherMembership));

        $this->getJson('/api/v1/identity', $entraHeaders)->assertOk();
        $this->entraConnection->organization->forceFill(['allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_NO])->save();

        $this->apiGetIdentityProviderLinksRequest($localProxy)
            ->assertOk()
            ->assertJsonPath('data.*.uid', [$membership->uid])
            ->assertJsonPath('meta.can_manage_links', true)
            ->assertJsonPath('meta.available_connections', []);

        $this->apiDeleteIdentityProviderLinkRequest($membership, $localProxy)->assertNoContent();

        $this->assertTrue($membership->refresh()->isRetired());
        $this->assertNull($membership->identity_id);
        $this->assertNull($membership->employee_id);
        $this->assertNull($membership->consent_version);
        $this->assertNull($membership->consented_at);
        $this->assertNull($membership->external_identity->identity_id);
        $this->assertNotNull($this->employee->fresh());
        $this->assertSame(IdentityProviderOidcSession::STATUS_ERROR, $pending->refresh()->status);
        $this->assertNull($prepared->refresh()->verified_account);

        $this->getJson('/api/v1/identity', $entraHeaders)->assertUnauthorized();
        $this->getJson('/api/v1/identity', $otherHeaders)->assertOk();
        $this->getJson('/api/v1/identity', $this->makeApiHeaders($localProxy))->assertOk();

        $this->apiGetIdentityProviderLinksRequest($localProxy)->assertOk()->assertJsonPath('data', []);

        $this->assertSame([
            IdentityProviderConnection::EVENT_ENTRA_LINK_SELF_UNLINKED,
        ], $this->entraConnection->logs()->pluck('event')->all());

        $this->entraConnection->organization->forceFill(['allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO])->save();

        $this->apiCompleteIdentityProviderLinkRequest($prepared, [
            'exchange_token' => $exchangeToken, 'browser_token' => $browserToken,
        ], $localProxy)->assertConflict();

        $this->assertIdentityProviderCallbackError(
            $this->apiIdentityProviderOidcCallbackRequest(['state' => $state, 'code' => 'test-code']),
            IdentityProviderErrorCode::SESSION_EXPIRED,
            'https://provider.example.test/beveiliging/gekoppelde-accounts',
        );

        $this->assertTrue($membership->refresh()->isRetired());
        $this->assertSame([$otherMembership->id], $this->entraConnection->memberships_current_claimed()->pluck('id')->all());
    }

    /**
     * @param IdentityProxy|null $proxy
     * @return array{IdentityProviderOidcSession, string, string}
     */
    protected function startLink(?IdentityProxy $proxy = null): array
    {
        $response = $this->apiStartIdentityProviderLinkRequest($this->entraConnection, $proxy ?? $this->employee->identity)->assertCreated();
        parse_str(parse_url($response->json('data.redirect_url'), PHP_URL_QUERY), $query);

        return [
            IdentityProviderOidcSession::where('state', hash('sha256', $query['state']))->firstOrFail(),
            $query['state'],
            $response->json('data.browser_token'),
        ];
    }

    /**
     * @param IdentityProviderOidcSession $session
     * @param string $state
     * @return string
     */
    protected function resolveLink(IdentityProviderOidcSession $session, string $state): string
    {
        $response = $this->apiIdentityProviderOidcCallbackRequest(['state' => $state, 'code' => 'test-code'])->assertRedirect();
        $location = $response->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $query);

        $this->assertSame($session->final_url, explode('?', $location, 2)[0]);
        $this->assertSame($session->uid, $query['entra_session'] ?? null);
        $this->assertArrayHasKey('entra_exchange', $query);

        return $query['entra_exchange'];
    }
}
