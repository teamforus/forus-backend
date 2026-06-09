<?php

namespace Tests\Feature\OpenId;

use App\Models\Identity;
use App\Models\IdentityProxy;
use App\Services\OpenIdService\Models\OpenIdSession;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesOpenIdTestData;
use Tests\Traits\MakesTestFunds;
use Throwable;

class OpenIdCallbackTest extends TestCase
{
    use DatabaseTransactions;
    use MakesOpenIdTestData;
    use MakesTestFunds;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('openid.enabled', true);
    }

    /**
     * @return void
     */
    public function testCallbackWithMissingStateRedirectsToFallbackWithSessionExpired(): void
    {
        $fallbackUrl = 'https://webshop.example/openid-fallback';
        $sessionCount = OpenIdSession::count();

        $this->assertOpenIdErrorRedirect(
            $this->openIdCallbackRequest(fallbackUrl: $fallbackUrl),
            $fallbackUrl,
            'session_expired',
        );

        $this->assertSame($sessionCount, OpenIdSession::count());
    }

    /**
     * @return void
     */
    public function testCallbackWithUnknownStateRedirectsToFallbackWithSessionExpired(): void
    {
        $fallbackUrl = 'https://webshop.example/openid-fallback';
        $sessionCount = OpenIdSession::count();

        $this->assertOpenIdErrorRedirect(
            $this->openIdCallbackRequest(query: ['state' => 'unknown-state'], fallbackUrl: $fallbackUrl),
            $fallbackUrl,
            'session_expired',
        );

        $this->assertSame($sessionCount, OpenIdSession::count());
    }

    /**
     * @return void
     */
    public function testCallbackRejectsUnknownProvider(): void
    {
        $fallbackUrl = 'https://webshop.example/openid-fallback';
        $sessionCount = OpenIdSession::count();

        $this->assertOpenIdErrorRedirect(
            $this->openIdCallbackRequest('unknown', ['state' => 'unknown-state'], $fallbackUrl),
            $fallbackUrl,
            'not_enabled',
        );

        $this->assertSame($sessionCount, OpenIdSession::count());
    }

    /**
     * @return void
     */
    public function testCallbackRejectsWhenOpenIdIsGloballyDisabled(): void
    {
        $implementation = $this->makeOpenIdImplementation();
        $session = $this->makeOpenIdSession($implementation);

        Config::set('openid.enabled', false);

        $this->assertOpenIdErrorRedirect(
            $this->openIdCallbackRequest(query: ['state' => $session->state]),
            $session->session_final_url,
            'not_enabled',
        );

        $this->assertSame(OpenIdSession::STATE_ERROR, $session->refresh()->session_state);
    }

    /**
     * @return void
     */
    public function testCallbackRejectsResolvedSessionAsExpired(): void
    {
        $implementation = $this->makeOpenIdImplementation();
        $session = $this->makeOpenIdSession($implementation, [
            'session_state' => OpenIdSession::STATE_RESOLVED,
        ]);

        $this->assertOpenIdErrorRedirect(
            $this->openIdCallbackRequest(query: ['state' => $session->state]),
            $session->session_final_url,
            'session_expired',
        );

        $this->assertSame(OpenIdSession::STATE_RESOLVED, $session->refresh()->session_state);
    }

    /**
     * @return void
     */
    public function testCallbackExpiresOldPendingSession(): void
    {
        $implementation = $this->makeOpenIdImplementation();
        $session = $this->makeOpenIdSession($implementation, [
            'created_at' => now()->subSeconds(OpenIdSession::SESSION_EXPIRATION_TIME + 1),
        ]);

        $this->assertOpenIdErrorRedirect(
            $this->openIdCallbackRequest(query: ['state' => $session->state]),
            $session->session_final_url,
            'session_expired',
        );

        $this->assertSame(OpenIdSession::STATE_EXPIRED, $session->refresh()->session_state);
    }

    /**
     * @return void
     */
    public function testCallbackRejectsWhenProviderIsDisabledOnImplementation(): void
    {
        $implementation = $this->makeOpenIdImplementation([
            'openid_enabled' => false,
        ]);
        $session = $this->makeOpenIdSession($implementation);

        $this->assertOpenIdErrorRedirect(
            $this->openIdCallbackRequest(query: ['state' => $session->state]),
            $session->session_final_url,
            'not_enabled',
        );

        $this->assertSame(OpenIdSession::STATE_ERROR, $session->refresh()->session_state);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAuthCallbackResolvesExistingBsnIdentityAndRedirectsWithShortToken(): void
    {
        $bsn = $this->makeUniqueOpenIdBsn();
        $identity = $this->makeIdentity($this->makeUniqueEmail(), bsn: $bsn);
        $implementation = $this->makeOpenIdImplementation();
        $session = $this->makeOpenIdSession($implementation, [
            'target' => 'fundRequest-123',
        ]);

        $this->assertSame($identity->address, Identity::findByBsn($bsn)?->address);
        $this->fakeOpenIdService(callbackBsn: $bsn);

        $query = $this->assertOpenIdAuthLinkRedirect(
            $this->openIdCallbackRequest(query: ['state' => $session->state, 'code' => 'auth-code']),
            $session,
            'fundRequest-123',
        );

        $proxy = IdentityProxy::where('exchange_token', $query['token'])->firstOrFail();

        $this->assertSame(IdentityProxy::STATE_ACTIVE, $proxy->state);
        $this->assertSame($identity->address, $proxy->identity_address);
        $this->assertNotEmpty($proxy->access_token);
        $this->assertSame(OpenIdSession::STATE_RESOLVED, $session->refresh()->session_state);
        $this->assertSame($identity->address, $session->identity_address);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAuthCallbackCreatesIdentityWhenBsnIsUnknownAndSignupIsAllowed(): void
    {
        $bsn = $this->makeUniqueOpenIdBsn();
        $implementation = $this->makeOpenIdImplementation([
            'digid_sign_up_allowed' => true,
        ], [
            'bsn_enabled' => true,
        ]);
        $session = $this->makeOpenIdSession($implementation);
        $identityCount = Identity::count();

        $this->fakeOpenIdService(callbackBsn: $bsn);

        $query = $this->assertOpenIdAuthLinkRedirect(
            $this->openIdCallbackRequest(query: ['state' => $session->state, 'code' => 'auth-code']),
            $session,
        );
        $identity = Identity::findByBsn($bsn);
        $proxy = IdentityProxy::where('exchange_token', $query['token'])->firstOrFail();

        $this->assertSame($identityCount + 1, Identity::count());
        $this->assertNotNull($identity);
        $this->assertSame($identity->address, $proxy->identity_address);
        $this->assertSame($identity->address, $session->refresh()->identity_address);
        $this->assertSame($bsn, $identity->bsn);
        $this->assertSame(OpenIdSession::STATE_RESOLVED, $session->session_state);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAuthCallbackRejectsUnknownBsnWhenSignupIsDisabled(): void
    {
        $implementation = $this->makeOpenIdImplementation([
            'digid_sign_up_allowed' => false,
        ]);
        $session = $this->makeOpenIdSession($implementation);

        $this->fakeOpenIdService(callbackBsn: $this->makeUniqueOpenIdBsn());

        $this->assertOpenIdErrorRedirect(
            $this->openIdCallbackRequest(query: ['state' => $session->state, 'code' => 'auth-code']),
            $session->session_final_url,
            'uid_not_found',
        );

        $this->assertSame(OpenIdSession::STATE_ERROR, $session->refresh()->session_state);
    }

    /**
     * @return void
     */
    public function testAuthCallbackRejectsMissingBsnClaim(): void
    {
        $implementation = $this->makeOpenIdImplementation();
        $session = $this->makeOpenIdSession($implementation);

        $this->fakeOpenIdService(callbackPayload: [
            'claims' => [],
            'user_info' => null,
            'user_info_error' => null,
            'id_token' => 'openid-id-token',
            'access_token' => 'openid-access-token',
        ]);

        $this->assertOpenIdErrorRedirect(
            $this->openIdCallbackRequest(query: ['state' => $session->state, 'code' => 'auth-code']),
            $session->session_final_url,
            'missing_claims',
        );

        $this->assertSame(OpenIdSession::STATE_ERROR, $session->refresh()->session_state);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestCallbackAssignsBsnAndRedirectsSignedUp(): void
    {
        $implementation = $this->makeOpenIdImplementation(organizationData: [
            'bsn_enabled' => true,
        ]);
        $fund = $this->makeTestFund($implementation->organization, implementation: $implementation);
        $requester = $this->makeIdentity();
        $session = $this->makeOpenIdSession($implementation, fund: $fund, identity: $requester);
        $bsn = $this->makeUniqueOpenIdBsn();

        $this->fakeOpenIdService(callbackBsn: $bsn);

        $this->assertOpenIdSuccessRedirect(
            $this->openIdCallbackRequest(query: ['state' => $session->state, 'code' => 'auth-code']),
            $session->session_final_url,
            'signed_up',
        );

        $this->assertSame(OpenIdSession::STATE_RESOLVED, $session->refresh()->session_state);
        $this->assertSame($bsn, $requester->refresh()->bsn);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestCallbackRedirectsSignedInWhenOrganizationBsnIsDisabled(): void
    {
        $implementation = $this->makeOpenIdImplementation(organizationData: [
            'bsn_enabled' => false,
        ]);
        $fund = $this->makeTestFund($implementation->organization, implementation: $implementation);
        $requester = $this->makeIdentity();
        $session = $this->makeOpenIdSession($implementation, fund: $fund, identity: $requester);

        $this->fakeOpenIdService(callbackBsn: $this->makeUniqueOpenIdBsn());

        $this->assertOpenIdSuccessRedirect(
            $this->openIdCallbackRequest(query: ['state' => $session->state, 'code' => 'auth-code']),
            $session->session_final_url,
            'signed_in',
        );

        $this->assertSame(OpenIdSession::STATE_RESOLVED, $session->refresh()->session_state);
        $this->assertNull($requester->refresh()->bsn);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestCallbackRejectsDifferentBsnForSessionIdentity(): void
    {
        $implementation = $this->makeOpenIdImplementation(organizationData: [
            'bsn_enabled' => true,
        ]);
        $fund = $this->makeTestFund($implementation->organization, implementation: $implementation);
        $requester = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->makeUniqueOpenIdBsn());
        $session = $this->makeOpenIdSession($implementation, fund: $fund, identity: $requester);

        $this->fakeOpenIdService(callbackBsn: $this->makeUniqueOpenIdBsn());

        $this->assertOpenIdErrorRedirect(
            $this->openIdCallbackRequest(query: ['state' => $session->state, 'code' => 'auth-code']),
            $session->session_final_url,
            'uid_dont_match',
        );

        $this->assertSame(OpenIdSession::STATE_ERROR, $session->refresh()->session_state);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestCallbackRejectsBsnUsedByAnotherIdentity(): void
    {
        $implementation = $this->makeOpenIdImplementation(organizationData: [
            'bsn_enabled' => true,
        ]);
        $fund = $this->makeTestFund($implementation->organization, implementation: $implementation);
        $requester = $this->makeIdentity();
        $session = $this->makeOpenIdSession($implementation, fund: $fund, identity: $requester);
        $bsn = $this->makeUniqueOpenIdBsn();

        $this->makeIdentity($this->makeUniqueEmail(), bsn: $bsn);
        $this->fakeOpenIdService(callbackBsn: $bsn);

        $this->assertOpenIdErrorRedirect(
            $this->openIdCallbackRequest(query: ['state' => $session->state, 'code' => 'auth-code']),
            $session->session_final_url,
            'uid_used',
        );

        $this->assertSame(OpenIdSession::STATE_ERROR, $session->refresh()->session_state);
    }

    /**
     * @return void
     */
    public function testCallbackRejectsUnknownSessionRequest(): void
    {
        $implementation = $this->makeOpenIdImplementation();
        $session = $this->makeOpenIdSession($implementation, [
            'session_request' => 'unknown',
        ]);

        $this->assertOpenIdErrorRedirect(
            $this->openIdCallbackRequest(query: ['state' => $session->state, 'code' => 'auth-code']),
            $session->session_final_url,
            'unknown_session_type',
        );

        $this->assertSame(OpenIdSession::STATE_ERROR, $session->refresh()->session_state);
    }

    /**
     * @param TestResponse $response
     * @param string $url
     * @param string $error
     * @return void
     */
    protected function assertOpenIdErrorRedirect(TestResponse $response, string $url, string $error): void
    {
        $response
            ->assertRedirect(url_extend_get_params($url, ['openid_error' => $error]))
            ->assertCookieExpired(self::OPENID_FALLBACK_COOKIE);
    }

    /**
     * @param TestResponse $response
     * @param string $url
     * @param string $success
     * @return void
     */
    protected function assertOpenIdSuccessRedirect(TestResponse $response, string $url, string $success): void
    {
        $response
            ->assertRedirect(url_extend_get_params($url, ['openid_success' => $success]))
            ->assertCookieExpired(self::OPENID_FALLBACK_COOKIE);
    }

    /**
     * @param TestResponse $response
     * @param OpenIdSession $session
     * @param string|null $target
     * @return array
     */
    protected function assertOpenIdAuthLinkRedirect(
        TestResponse $response,
        OpenIdSession $session,
        ?string $target = null,
    ): array {
        $location = (string) $response->headers->get('Location');
        $authLinkUrl = rtrim($session->session_final_url, '/') . '/auth-link';

        $this->assertStringStartsWith($authLinkUrl . '?', $location);
        parse_str($this->queryStringFromLocation($location), $query);

        $this->assertNotEmpty($query['token'] ?? null);

        if ($target) {
            $this->assertSame($target, $query['target'] ?? null);
        } else {
            $this->assertArrayNotHasKey('target', $query);
        }

        $response->assertCookieExpired(self::OPENID_FALLBACK_COOKIE);

        return $query;
    }

    /**
     * @param string $location
     * @return string
     */
    protected function queryStringFromLocation(string $location): string
    {
        $fragment = (string) parse_url($location, PHP_URL_FRAGMENT);

        if (str_contains($fragment, '?')) {
            return explode('?', $fragment, 2)[1];
        }

        return (string) parse_url($location, PHP_URL_QUERY);
    }

    /**
     * @throws Throwable
     * @return string
     */
    protected function makeUniqueOpenIdBsn(): string
    {
        do {
            $bsn = str_pad((string) $this->randomFakeBsn(), 9, '0', STR_PAD_LEFT);
        } while (Identity::findByBsn($bsn));

        return $bsn;
    }
}
