<?php

namespace Tests\Feature\IdentityProviders;

use App\Models\Employee;
use App\Models\IdentityProxy;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Role;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderErrorCode;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use App\Services\IdentityProviderService\Models\IdentityProviderOidcSession;
use App\Services\IdentityProviderService\Models\IdentityProviderProxyBinding;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\Fakes\FakeOpenIdClientService;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentityProviders;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class IdentityProviderLoginTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestFunds;
    use MakesTestIdentityProviders;
    use MakesTestOrganizations;

    protected array $claims;
    protected Employee $employee;
    protected FakeOpenIdClientService $openIdClient;
    protected IdentityProviderMembership $membership;
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
            'identity_providers.entra.redirect_url' => 'https://api.example.test/oidc/callback',
        ]);

        Implementation::general()->update(['url_sponsor' => 'https://sponsor.example.test']);

        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        $this->employee = $organization->addEmployee($this->makeIdentity(), [Role::where('key', 'admin')->firstOrFail()->id]);
        $this->entraConnection = $this->makeEntraConnection($organization);
        $this->membership = $this->makeIdentityProviderMembership($this->entraConnection, $this->employee);

        $this->claims = [
            'tid' => $this->entraConnection->tenant_id,
            'oid' => $this->membership->external_identity->object_id,
            'iss' => $this->entraConnection->issuer,
            'sub' => 'test-subject',
            'acct' => 0,
        ];

        $this->openIdClient = $this->bindFakeOpenIdClient($this->claims);
        $this->withHeaders(['Client-Type' => Implementation::FRONTEND_SPONSOR_DASHBOARD]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testDashboardLoginRequiresBrowserSecretAndCanOnlyBeExchangedOnce(): void
    {
        [$session, $browserToken, $state] = $this->startLogin();

        $this->assertNull($session->connection_id);
        $this->assertSame(hash('sha256', $browserToken), $session->browser_token_hash);
        $this->assertSame('https://sponsor.example.test/auth/entra', $session->final_url);

        $query = $this->resolveLogin($session, $state);

        $this->assertSame($session->uid, $query['entra_session']);
        $this->assertSame($this->entraConnection->id, $session->refresh()->connection_id);
        $this->assertSame($this->membership->id, $session->membership_id);

        $this->assertIdentityProviderCallbackError(
            $this->apiIdentityProviderOidcCallbackRequest(['state' => $state, 'code' => 'test-code']),
            IdentityProviderErrorCode::SESSION_EXPIRED,
            $session->final_url,
        );

        $this->assertTrue($session->refresh()->isResolved());
        $this->assertSame(hash('sha256', $query['entra_exchange']), $session->exchange_token_hash);

        $this->apiExchangeIdentityProviderLoginRequest([
            'exchange_token' => Str::random(64), 'browser_token' => $browserToken,
        ])->assertForbidden();

        $this->apiExchangeIdentityProviderLoginRequest([
            'exchange_token' => $query['entra_exchange'], 'browser_token' => Str::random(64),
        ])->assertForbidden()->assertJsonPath('message', __('exceptions.forbidden'));

        $credentials = ['exchange_token' => $query['entra_exchange'], 'browser_token' => $browserToken];
        $response = $this->apiExchangeIdentityProviderLoginRequest($credentials)->assertOk();
        $proxy = $this->assertAuthenticatedProxy($response, $session);

        $this->apiExchangeIdentityProviderLoginRequest($credentials)->assertForbidden();

        $this->assertSame($proxy->id, $session->refresh()->identity_proxy_id);
        $this->assertNotNull($session->exchange_consumed_at);
        $this->assertSame(1, IdentityProviderProxyBinding::where('membership_id', $this->membership->id)->count());

        $this->assertSame([
            IdentityProviderConnection::EVENT_ENTRA_LOGIN_SUCCEEDED,
        ], $this->entraConnection->logs()->pluck('event')->all());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testWebshopLoginUsesSelectedConnectionAndDoesNotRequireDashboardRole(): void
    {
        $this->employee->roles()->detach();
        $implementation = $this->useWebshop();
        [$session, $browserToken, $state] = $this->startLogin(['target' => 'vouchers']);

        $this->assertSame($implementation->id, $session->implementation_id);
        $this->assertSame($this->entraConnection->id, $session->connection_id);
        $this->assertSame('https://webshop.example.test/auth-link', $session->final_url);

        $query = $this->resolveLogin($session, $state);

        $this->assertSame('vouchers', $query['target']);

        $this->assertAuthenticatedProxy($this->apiExchangeIdentityProviderLoginRequest([
            'exchange_token' => $query['entra_exchange'], 'browser_token' => $browserToken,
        ])->assertOk(), $session);
    }

    /**
     * @return void
     */
    public function testLoginRequiresSupportedClientAndEnabledModule(): void
    {
        $this->withHeaders(['Client-Type' => Implementation::FRONTEND_PROVIDER_DASHBOARD]);
        $this->apiStartIdentityProviderLoginRequest()->assertForbidden();

        $this->withHeaders(['Client-Type' => Implementation::FRONTEND_SPONSOR_DASHBOARD]);
        Config::set('identity_providers.enabled', false);

        $this->apiStartIdentityProviderLoginRequest()->assertForbidden();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testWebshopLoginRequiresSelectionAndCurrentConnection(): void
    {
        $implementation = $this->useWebshop(['entra_login_enabled' => false]);
        $this->apiStartIdentityProviderLoginRequest()->assertConflict();

        $implementation->update(['entra_login_enabled' => true]);
        $this->entraConnection->update(['status' => IdentityProviderConnection::STATUS_DISCONNECTED]);
        Implementation::clearMemo();

        $this->apiStartIdentityProviderLoginRequest()->assertConflict();

        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        $implementation->forceFill(['organization_id' => $organization->id])->save();
        Implementation::clearMemo();

        $this->apiStartIdentityProviderLoginRequest()->assertConflict();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testMissingLinkFailureIsAttributedOnceToVerifiedConnection(): void
    {
        $otherConnection = $this->makeEntraConnection($this->makeTestOrganization($this->makeIdentity()));
        $this->openIdClient->setClaims([...$this->claims, 'oid' => Str::uuid()->toString()]);
        [$session, , $state] = $this->startLogin();

        $this->assertIdentityProviderCallbackError(
            $this->apiIdentityProviderOidcCallbackRequest(['state' => $state, 'code' => 'test-code']),
            IdentityProviderErrorCode::LINK_NOT_FOUND,
            $session->final_url,
        );

        $this->assertSame(IdentityProviderOidcSession::STATUS_ERROR, $session->refresh()->status);
        $this->assertSame($this->entraConnection->id, $session->connection_id);
        $this->assertNull($session->exchange_token_hash);
        $this->assertNotNull($this->entraConnection->refresh()->last_auth_failure_at);
        $this->assertSame(IdentityProviderErrorCode::LINK_NOT_FOUND, $this->entraConnection->last_auth_failure_code);

        $this->assertIdentityProviderCallbackError(
            $this->apiIdentityProviderOidcCallbackRequest(['state' => $state, 'code' => 'test-code']),
            IdentityProviderErrorCode::SESSION_EXPIRED,
            $session->final_url,
        );

        $this->assertSame([
            IdentityProviderConnection::EVENT_ENTRA_LOGIN_FAILED,
        ], $this->entraConnection->logs()->pluck('event')->all());

        $this->assertSame(IdentityProviderErrorCode::LINK_NOT_FOUND, $this->entraConnection->refresh()->last_auth_failure_code);
        $this->assertNull($otherConnection->refresh()->last_auth_failure_at);
        $this->assertFalse($otherConnection->logs()->exists());
    }

    /**
     * @return void
     */
    public function testRetiredMembershipCannotLogIn(): void
    {
        $this->membership->update([
            'claim_state' => IdentityProviderMembership::CLAIM_RETIRED,
            'identity_id' => null, 'employee_id' => null, 'retired_at' => now(),
        ]);

        $this->membership->external_identity->update(['identity_id' => null]);

        $this->assertLoginRejected(IdentityProviderErrorCode::MEMBERSHIP_INACTIVE);
    }

    /**
     * @return void
     */
    public function testDeletedEmployeeCannotLogIn(): void
    {
        $this->employee->delete();

        $this->assertLoginRejected(IdentityProviderErrorCode::MEMBERSHIP_INACTIVE);
    }

    /**
     * @return void
     */
    public function testDashboardLoginRequiresEmployeeRole(): void
    {
        $this->employee->roles()->detach();

        $this->assertLoginRejected(IdentityProviderErrorCode::DASHBOARD_ROLE_MISSING);
    }

    /**
     * @return void
     */
    public function testGuestRejectionDoesNotAttributeUnverifiedTenantToConnection(): void
    {
        $this->openIdClient->setClaims([...$this->claims, 'acct' => 1]);

        $session = $this->assertLoginRejected(IdentityProviderErrorCode::ENTRA_GUEST_NOT_SUPPORTED);

        $this->assertNull($session->connection_id);
        $this->assertNull($this->entraConnection->refresh()->last_auth_failure_at);
        $this->assertFalse($this->entraConnection->logs()->exists());
    }

    /**
     * @return void
     */
    public function testWebshopCallbackCannotUseAnotherTenant(): void
    {
        $this->useWebshop();
        $this->openIdClient->setClaims([...$this->claims, 'tid' => Str::uuid()->toString()]);

        $this->assertLoginRejected(IdentityProviderErrorCode::TENANT_NOT_CONNECTED);
    }

    /**
     * @return void
     */
    public function testExpiredExchangeCannotCreateProxy(): void
    {
        [$session, $browserToken, $state] = $this->startLogin();
        $query = $this->resolveLogin($session, $state);
        $this->travelTo($session->refresh()->exchange_expires_at->copy()->addSecond());

        $this->apiExchangeIdentityProviderLoginRequest([
            'exchange_token' => $query['entra_exchange'], 'browser_token' => $browserToken,
        ])->assertForbidden();

        $this->assertNull($session->refresh()->identity_proxy_id);
        $this->assertFalse(IdentityProviderProxyBinding::where('membership_id', $this->membership->id)->exists());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUnlinkBeforeExchangePreventsAuthentication(): void
    {
        [$session, $browserToken, $state] = $this->startLogin();
        $query = $this->resolveLogin($session, $state);

        $this->apiDeleteIdentityProviderLinkRequest($this->membership, $this->employee->identity)->assertNoContent();

        $this->assertExchangeRejected($session, $query['entra_exchange'], $browserToken);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testDisconnectBeforeExchangePreventsAuthentication(): void
    {
        [$session, $browserToken, $state] = $this->startLogin();
        $query = $this->resolveLogin($session, $state);

        $this->apiChangeIdentityProviderConnectionStateRequest(
            $this->entraConnection->organization,
            $this->entraConnection,
            'disconnect',
            $this->entraConnection->organization->identity,
        )->assertOk();

        $this->assertExchangeRejected($session, $query['entra_exchange'], $browserToken);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPauseBeforeExchangePreventsAuthentication(): void
    {
        [$session, $browserToken, $state] = $this->startLogin();
        $query = $this->resolveLogin($session, $state);

        $this->apiChangeIdentityProviderConnectionStateRequest(
            $this->entraConnection->organization,
            $this->entraConnection,
            'pause',
            $this->entraConnection->organization->identity,
        )->assertOk();

        $this->assertExchangeRejected($session, $query['entra_exchange'], $browserToken);
    }

    /**
     * @return void
     */
    public function testUnexpectedStartFailureReturnsSafeMessageWithDiagnosticReference(): void
    {
        $this->openIdClient->setAuthorizationException(new RuntimeException('Internal provider credential failure'));

        $response = $this->apiStartIdentityProviderLoginRequest()->assertStatus(503);
        $message = $response->json('message');
        preg_match('/[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}/', $message, $matches);

        $this->assertNotEmpty($matches);

        $this->assertSame(__('exceptions.identity_providers.with_reference', [
            'message' => rtrim(__('exceptions.identity_providers.oidc_start_failed'), '.'),
            'reference' => $matches[0],
        ]), $message);

        $this->assertStringNotContainsString('Internal provider credential failure', $response->getContent());
    }

    /**
     * @param array $attributes
     * @return Implementation
     */
    protected function useWebshop(array $attributes = []): Implementation
    {
        $implementation = $this->makeTestImplementation($this->entraConnection->organization, [
            'url_webshop' => 'https://webshop.example.test',
            'entra_login_enabled' => true,
            ...$attributes,
        ]);

        $this->withHeaders([
            'Client-Type' => Implementation::FRONTEND_WEBSHOP,
            'Client-Key' => $implementation->key,
        ]);

        return $implementation;
    }

    /**
     * @param array $data
     * @return array{IdentityProviderOidcSession, string, string}
     */
    protected function startLogin(array $data = []): array
    {
        $response = $this->apiStartIdentityProviderLoginRequest($data)->assertCreated();
        parse_str(parse_url($response->json('data.redirect_url'), PHP_URL_QUERY), $query);

        return [
            IdentityProviderOidcSession::where('uid', $response->json('data.session_uid'))->firstOrFail(),
            $response->json('data.browser_token'),
            $query['state'],
        ];
    }

    /**
     * @param IdentityProviderOidcSession $session
     * @param string $state
     * @return array
     */
    protected function resolveLogin(IdentityProviderOidcSession $session, string $state): array
    {
        $response = $this->apiIdentityProviderOidcCallbackRequest(['state' => $state, 'code' => 'test-code'])->assertRedirect();
        $location = $response->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $query);

        $this->assertSame($session->final_url, explode('?', $location, 2)[0]);
        $this->assertArrayHasKey('entra_exchange', $query);

        return $query;
    }

    /**
     * @param TestResponse $response
     * @param IdentityProviderOidcSession $session
     * @return IdentityProxy
     */
    protected function assertAuthenticatedProxy(TestResponse $response, IdentityProviderOidcSession $session): IdentityProxy
    {
        $proxy = IdentityProxy::where('access_token', $response->json('access_token'))->firstOrFail();

        $this->assertIdentityProviderProxy($proxy, $this->membership);
        $this->assertSame($proxy->id, $session->refresh()->identity_proxy_id);
        $response->assertJsonPath('organization_id', $this->entraConnection->organization_id);

        return $proxy;
    }

    /**
     * @param string $errorCode
     * @return IdentityProviderOidcSession
     */
    protected function assertLoginRejected(string $errorCode): IdentityProviderOidcSession
    {
        [$session, , $state] = $this->startLogin();

        $this->assertIdentityProviderCallbackError(
            $this->apiIdentityProviderOidcCallbackRequest(['state' => $state, 'code' => 'test-code']),
            $errorCode,
            $session->final_url,
        );

        $this->assertSame(IdentityProviderOidcSession::STATUS_ERROR, $session->refresh()->status);
        $this->assertNull($session->exchange_token_hash);

        return $session;
    }

    /**
     * @param IdentityProviderOidcSession $session
     * @param string $exchangeToken
     * @param string $browserToken
     * @return void
     */
    protected function assertExchangeRejected(IdentityProviderOidcSession $session, string $exchangeToken, string $browserToken): void
    {
        $this->apiExchangeIdentityProviderLoginRequest([
            'exchange_token' => $exchangeToken, 'browser_token' => $browserToken,
        ])->assertForbidden();

        $this->assertNull($session->refresh()->identity_proxy_id);
        $this->assertFalse(IdentityProviderProxyBinding::where('membership_id', $this->membership->id)->exists());
    }
}
