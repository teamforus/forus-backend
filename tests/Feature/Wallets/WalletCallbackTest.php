<?php

namespace Tests\Feature\Wallets;

use App\Models\Identity;
use App\Models\IdentityProxy;
use App\Services\WalletService\Models\WalletSession;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesWalletTestData;
use Throwable;

class WalletCallbackTest extends TestCase
{
    use DatabaseTransactions;
    use MakesWalletTestData;
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
    public function testCompletionRequiresTheInitiatingBrowserAndMatchingTicketAndCannotBeReplayed(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->makeUniqueWalletBsn());
        $implementation = $this->makeWalletImplementation();
        $this->fakeWalletService(callbackBsn: $identity->bsn);

        $start = $this->apiStartWalletAuthRequest($implementation)->assertOk();
        $session = WalletSession::where('session_uid', $start->json('session_uid'))->firstOrFail();
        $proxyCount = IdentityProxy::where('identity_address', $identity->address)->count();

        $callback = $this->walletCallbackRequest(query: ['state' => $session->state, 'code' => 'code'], complete: false);
        $callback->assertRedirect();
        $this->assertTrue($callback->headers->hasCacheControlDirective('no-store'));
        parse_str((string) parse_url($callback->headers->get('Location'), PHP_URL_FRAGMENT), $ticket);

        $this->assertTrue($session->refresh()->isPending());
        $this->assertSame($proxyCount, IdentityProxy::where('identity_address', $identity->address)->count());

        $completion = [
            'browser_token' => $start->json('browser_token'),
            'callback_ticket' => $ticket['wallet_ticket'],
        ];

        $this->apiCompleteWalletAuthRequest($session->session_uid, [
            ...$completion, 'browser_token' => 'wrong-browser',
        ])->assertForbidden();

        $this->apiCompleteWalletAuthRequest($session->session_uid, [
            'callback_ticket' => $completion['callback_ticket'],
        ])->assertUnprocessable();

        $otherSession = $this->makeWalletSession($implementation);
        $this->apiCompleteWalletAuthRequest($otherSession->session_uid, $completion)->assertForbidden();

        $this->apiCompleteWalletAuthRequest($session->session_uid, [
            ...$completion, 'callback_ticket' => 'invalid-ticket',
        ])->assertForbidden();

        $this->assertWalletAuthLinkRedirect(
            $this->apiCompleteWalletAuthRequest($session->session_uid, $completion),
            $session,
        );

        $this->assertSame(WalletSession::STATE_RESOLVED, $session->refresh()->session_state);
        $this->assertNull($session->browser_token_hash);
        $this->assertSame($proxyCount + 1, IdentityProxy::where('identity_address', $identity->address)->count());
        $this->apiCompleteWalletAuthRequest($session->session_uid, $completion)->assertNotFound();
    }

    /**
     * @return void
     */
    public function testCompletionRechecksExpiryAndFlowAvailabilityAfterCallback(): void
    {
        foreach (['expired', 'disabled'] as $reason) {
            $implementation = $this->makeWalletImplementation();
            $session = $this->makeWalletSession($implementation);
            $this->fakeWalletService(callbackBsn: $this->makeUniqueWalletBsn());

            $callback = $this->walletCallbackRequest(query: ['state' => $session->state, 'code' => 'code'], complete: false);
            parse_str((string) parse_url($callback->headers->get('Location'), PHP_URL_FRAGMENT), $ticket);

            if ($reason === 'expired') {
                $session->forceFill([
                    'created_at' => now()->subSeconds(Config::get('openid.session_expiration_seconds') + 1),
                ])->save();
            } else {
                $implementation->update(['openid_enabled' => false]);
            }

            $response = $this->apiCompleteWalletAuthRequest($session->session_uid, [
                'browser_token' => self::WALLET_BROWSER_TOKEN,
                'callback_ticket' => $ticket['wallet_ticket'],
            ]);

            if ($reason === 'expired') {
                $response->assertNotFound();
            } else {
                $this->assertWalletErrorRedirect($response, $session->session_final_url, 'not_enabled');
            }

            $this->assertNull($session->refresh()->identity_address);
        }
    }

    /**
     * @return void
     */
    public function testCallbackWithMissingStateRedirectsToFallbackWithSessionExpired(): void
    {
        $fallbackUrl = 'https://webshop.example/openid-fallback';
        $sessionCount = WalletSession::count();

        $this->assertWalletErrorRedirect(
            $this->walletCallbackRequest(fallbackUrl: $fallbackUrl),
            $fallbackUrl,
            'session_expired',
        );

        $this->assertSame($sessionCount, WalletSession::count());
    }

    /**
     * @return void
     */
    public function testCallbackWithUnknownStateRedirectsToFallbackWithSessionExpired(): void
    {
        $fallbackUrl = 'https://webshop.example/openid-fallback';
        $sessionCount = WalletSession::count();

        $this->assertWalletErrorRedirect(
            $this->walletCallbackRequest(query: ['state' => 'unknown-state'], fallbackUrl: $fallbackUrl),
            $fallbackUrl,
            'session_expired',
        );

        $this->assertSame($sessionCount, WalletSession::count());
    }

    /**
     * @return void
     */
    public function testCallbackRejectsUnknownProvider(): void
    {
        $fallbackUrl = 'https://webshop.example/openid-fallback';
        $sessionCount = WalletSession::count();

        $this->assertWalletErrorRedirect(
            $this->walletCallbackRequest('unknown', ['state' => 'unknown-state'], $fallbackUrl),
            $fallbackUrl,
            'not_enabled',
        );

        $this->assertSame($sessionCount, WalletSession::count());
    }

    /**
     * @return void
     */
    public function testCallbackRejectsWhenWalletIsGloballyDisabled(): void
    {
        $implementation = $this->makeWalletImplementation();
        $session = $this->makeWalletSession($implementation);

        Config::set('openid.enabled', false);

        $this->assertWalletErrorRedirect(
            $this->walletCallbackRequest(query: ['state' => $session->state]),
            $session->session_final_url,
            'not_enabled',
        );

        $this->assertSame(WalletSession::STATE_ERROR, $session->refresh()->session_state);
    }

    /**
     * @return void
     */
    public function testCallbackRejectsResolvedSessionAsExpired(): void
    {
        $implementation = $this->makeWalletImplementation();
        $session = $this->makeWalletSession($implementation, [
            'session_state' => WalletSession::STATE_RESOLVED,
        ]);

        $this->assertWalletErrorRedirect(
            $this->walletCallbackRequest(query: ['state' => $session->state]),
            $session->session_final_url,
            'session_expired',
        );

        $this->assertSame(WalletSession::STATE_RESOLVED, $session->refresh()->session_state);
    }

    /**
     * @return void
     */
    public function testCallbackExpiresOldPendingSession(): void
    {
        $implementation = $this->makeWalletImplementation();
        $session = $this->makeWalletSession($implementation, [
            'created_at' => now()->subSeconds(Config::get('openid.session_expiration_seconds') + 1),
        ]);

        $this->assertWalletErrorRedirect(
            $this->walletCallbackRequest(query: ['state' => $session->state]),
            $session->session_final_url,
            'session_expired',
        );

        $this->assertSame(WalletSession::STATE_EXPIRED, $session->refresh()->session_state);
    }

    /**
     * @return void
     */
    public function testCallbackRejectsWhenProviderIsDisabledOnImplementation(): void
    {
        $implementation = $this->makeWalletImplementation([
            'openid_enabled' => false,
        ]);
        $session = $this->makeWalletSession($implementation);

        $this->assertWalletErrorRedirect(
            $this->walletCallbackRequest(query: ['state' => $session->state]),
            $session->session_final_url,
            'not_enabled',
        );

        $this->assertSame(WalletSession::STATE_ERROR, $session->refresh()->session_state);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAuthCallbackResolvesExistingBsnIdentityAndRedirectsWithShortToken(): void
    {
        $bsn = $this->makeUniqueWalletBsn();
        $identity = $this->makeIdentity($this->makeUniqueEmail(), bsn: $bsn);
        $implementation = $this->makeWalletImplementation();
        $session = $this->makeWalletSession($implementation, [
            'target' => 'fundRequest-123',
        ]);

        $this->assertSame($identity->address, Identity::findByBsn($bsn)?->address);
        $this->fakeWalletService(callbackBsn: $bsn);

        $query = $this->assertWalletAuthLinkRedirect(
            $this->walletCallbackRequest(query: ['state' => $session->state, 'code' => 'auth-code']),
            $session,
            'fundRequest-123',
        );

        $proxy = IdentityProxy::where('exchange_token', $query['token'])->firstOrFail();

        $this->assertSame(IdentityProxy::STATE_ACTIVE, $proxy->state);
        $this->assertSame($identity->address, $proxy->identity_address);
        $this->assertNotEmpty($proxy->access_token);
        $this->assertSame(WalletSession::STATE_RESOLVED, $session->refresh()->session_state);
        $this->assertSame($identity->address, $session->identity_address);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAuthCallbackCreatesIdentityWhenBsnIsUnknownAndSignupIsAllowed(): void
    {
        $bsn = $this->makeUniqueWalletBsn();
        $implementation = $this->makeWalletImplementation([
            'digid_sign_up_allowed' => true,
        ], [
            'bsn_enabled' => true,
        ]);
        $session = $this->makeWalletSession($implementation);
        $identityCount = Identity::count();

        $this->fakeWalletService(callbackBsn: $bsn);

        $query = $this->assertWalletAuthLinkRedirect(
            $this->walletCallbackRequest(query: ['state' => $session->state, 'code' => 'auth-code']),
            $session,
        );
        $identity = Identity::findByBsn($bsn);
        $proxy = IdentityProxy::where('exchange_token', $query['token'])->firstOrFail();

        $this->assertSame($identityCount + 1, Identity::count());
        $this->assertNotNull($identity);
        $this->assertSame($identity->address, $proxy->identity_address);
        $this->assertSame($identity->address, $session->refresh()->identity_address);
        $this->assertSame($bsn, $identity->bsn);
        $this->assertSame(WalletSession::STATE_RESOLVED, $session->session_state);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAuthCallbackRejectsUnknownBsnWhenSignupIsDisabled(): void
    {
        $implementation = $this->makeWalletImplementation([
            'digid_sign_up_allowed' => false,
        ]);
        $session = $this->makeWalletSession($implementation);

        $this->fakeWalletService(callbackBsn: $this->makeUniqueWalletBsn());

        $this->assertWalletErrorRedirect(
            $this->walletCallbackRequest(query: ['state' => $session->state, 'code' => 'auth-code']),
            $session->session_final_url,
            'uid_not_found',
        );

        $this->assertSame(WalletSession::STATE_ERROR, $session->refresh()->session_state);
    }

    /**
     * @return void
     */
    public function testAuthCallbackRejectsMissingBsnClaim(): void
    {
        $implementation = $this->makeWalletImplementation();
        $session = $this->makeWalletSession($implementation);

        $this->fakeWalletService(callbackPayload: [
            'claims' => [],
            'id_token' => 'openid-id-token',
            'access_token' => 'openid-access-token',
        ]);

        $this->assertWalletErrorRedirect(
            $this->walletCallbackRequest(query: ['state' => $session->state, 'code' => 'auth-code']),
            $session->session_final_url,
            'missing_claims',
        );

        $this->assertSame(WalletSession::STATE_ERROR, $session->refresh()->session_state);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestCallbackAssignsBsnAndRedirectsSignedUp(): void
    {
        $implementation = $this->makeWalletImplementation(organizationData: [
            'bsn_enabled' => true,
        ]);
        $fund = $this->makeTestFund($implementation->organization, implementation: $implementation);
        $requester = $this->makeIdentity();
        $session = $this->makeWalletSession($implementation, fund: $fund, identity: $requester);
        $bsn = $this->makeUniqueWalletBsn();

        $this->fakeWalletService(callbackBsn: $bsn);

        $this->assertWalletSuccessRedirect(
            $this->walletCallbackRequest(query: ['state' => $session->state, 'code' => 'auth-code']),
            $session->session_final_url,
            'signed_up',
        );

        $this->assertSame(WalletSession::STATE_RESOLVED, $session->refresh()->session_state);
        $this->assertSame($bsn, $requester->refresh()->bsn);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestCallbackRejectsWhenOrganizationBsnIsDisabled(): void
    {
        $implementation = $this->makeWalletImplementation();
        $fund = $this->makeTestFund($implementation->organization, implementation: $implementation);
        $requester = $this->makeIdentity();
        $session = $this->makeWalletSession($implementation, fund: $fund, identity: $requester);

        $this->assertTrue($implementation->walletAvailable());
        $implementation->organization->forceFill(['bsn_enabled' => false])->save();
        $this->assertFalse($implementation->refresh()->walletAvailable());

        $this->fakeWalletService(callbackBsn: $this->makeUniqueWalletBsn());

        $this->assertWalletErrorRedirect(
            $this->walletCallbackRequest(query: ['state' => $session->state, 'code' => 'auth-code']),
            $session->session_final_url,
            'not_enabled',
        );

        $this->assertSame(WalletSession::STATE_ERROR, $session->refresh()->session_state);
        $this->assertNull($requester->refresh()->bsn);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestCallbackRejectsDifferentBsnForSessionIdentity(): void
    {
        $implementation = $this->makeWalletImplementation(organizationData: [
            'bsn_enabled' => true,
        ]);
        $fund = $this->makeTestFund($implementation->organization, implementation: $implementation);
        $requester = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->makeUniqueWalletBsn());
        $session = $this->makeWalletSession($implementation, fund: $fund, identity: $requester);

        $this->fakeWalletService(callbackBsn: $this->makeUniqueWalletBsn());

        $this->assertWalletErrorRedirect(
            $this->walletCallbackRequest(query: ['state' => $session->state, 'code' => 'auth-code']),
            $session->session_final_url,
            'uid_dont_match',
        );

        $this->assertSame(WalletSession::STATE_ERROR, $session->refresh()->session_state);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestCallbackRejectsBsnUsedByAnotherIdentity(): void
    {
        $implementation = $this->makeWalletImplementation(organizationData: [
            'bsn_enabled' => true,
        ]);
        $fund = $this->makeTestFund($implementation->organization, implementation: $implementation);
        $requester = $this->makeIdentity();
        $session = $this->makeWalletSession($implementation, fund: $fund, identity: $requester);
        $bsn = $this->makeUniqueWalletBsn();

        $this->makeIdentity($this->makeUniqueEmail(), bsn: $bsn);
        $this->fakeWalletService(callbackBsn: $bsn);

        $this->assertWalletErrorRedirect(
            $this->walletCallbackRequest(query: ['state' => $session->state, 'code' => 'auth-code']),
            $session->session_final_url,
            'uid_used',
        );

        $this->assertSame(WalletSession::STATE_ERROR, $session->refresh()->session_state);
    }

    /**
     * @return void
     */
    public function testCallbackRejectsUnknownSessionRequest(): void
    {
        $implementation = $this->makeWalletImplementation();
        $session = $this->makeWalletSession($implementation, [
            'session_request' => 'unknown',
        ]);

        $this->assertWalletErrorRedirect(
            $this->walletCallbackRequest(query: ['state' => $session->state, 'code' => 'auth-code']),
            $session->session_final_url,
            'unknown_session_type',
        );

        $this->assertSame(WalletSession::STATE_ERROR, $session->refresh()->session_state);
    }

    /**
     * @param TestResponse $response
     * @param string $url
     * @param string $error
     * @return void
     */
    protected function assertWalletErrorRedirect(TestResponse $response, string $url, string $error): void
    {
        $expectedUrl = url_extend_get_params($url, ['wallet_error' => $error]);

        if ($response->isRedirection()) {
            $response->assertRedirect($expectedUrl);
        } else {
            $response->assertOk()->assertJsonPath('redirect_url', $expectedUrl);
        }

        $response->assertCookieExpired(self::WALLET_FALLBACK_COOKIE);
    }

    /**
     * @param TestResponse $response
     * @param string $url
     * @param string $success
     * @return void
     */
    protected function assertWalletSuccessRedirect(TestResponse $response, string $url, string $success): void
    {
        $response
            ->assertOk()
            ->assertJsonPath('redirect_url', url_extend_get_params($url, ['wallet_success' => $success]))
            ->assertCookieExpired(self::WALLET_FALLBACK_COOKIE);
    }

    /**
     * @param TestResponse $response
     * @param WalletSession $session
     * @param string|null $target
     * @return array
     */
    protected function assertWalletAuthLinkRedirect(
        TestResponse $response,
        WalletSession $session,
        ?string $target = null,
    ): array {
        $response->assertOk();
        $location = $response->json('redirect_url');
        $authLinkUrl = rtrim($session->session_final_url, '/') . '/auth-link';

        $this->assertStringStartsWith($authLinkUrl . '?', $location);
        parse_str($this->queryStringFromLocation($location), $query);

        $this->assertNotEmpty($query['token'] ?? null);

        if ($target) {
            $this->assertSame($target, $query['target'] ?? null);
        } else {
            $this->assertArrayNotHasKey('target', $query);
        }

        $response->assertCookieExpired(self::WALLET_FALLBACK_COOKIE);

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
    protected function makeUniqueWalletBsn(): string
    {
        do {
            $bsn = str_pad((string) $this->randomFakeBsn(), 9, '0', STR_PAD_LEFT);
        } while (Identity::findByBsn($bsn));

        return $bsn;
    }
}
