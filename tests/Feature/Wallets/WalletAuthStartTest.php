<?php

namespace Tests\Feature\Wallets;

use App\Models\Fund;
use App\Models\Implementation;
use App\Services\WalletService\Models\WalletFlow;
use App\Services\WalletService\Models\WalletSession;
use App\Services\WalletService\WalletService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesWalletTestData;

class WalletAuthStartTest extends TestCase
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
    public function testStartAuthCreatesPendingSessionForUnauthenticatedWebshopUser(): void
    {
        $this->fakeWalletService(authorizationData: [
            'meta' => [
                'intent_id' => 'intent-123',
            ],
        ]);

        $implementation = $this->makeWalletImplementation();
        $sessionCount = WalletSession::count();
        $response = $this->apiStartWalletAuthRequest($implementation, [
            'target' => 'fundRequest-123',
        ]);

        $response->assertSuccessful();
        $this->assertSame($sessionCount + 1, WalletSession::count());

        /** @var WalletFlow $flow */
        $session = $this->findWalletSessionByRedirectUrl($response->json('redirect_url'));
        $flow = $implementation->availableWalletFlows()->first();

        $this->assertSame($session->getRedirectUrl(), $response->json('redirect_url'));
        $this->assertSame($flow->id, $session->wallet_flow_id);
        $this->assertSame(WalletService::PROVIDER_VERID, $session->wallet_flow->provider);
        $this->assertSame($implementation->id, $session->implementation_id);
        $this->assertSame(Implementation::FRONTEND_WEBSHOP, $session->client_type);
        $this->assertNull($session->identity_address);
        $this->assertSame('fundRequest-123', $session->target);
        $this->assertSame($implementation->urlFrontend(Implementation::FRONTEND_WEBSHOP), $session->session_final_url);
        $this->assertSame(static::FAKE_REDIRECT_URL, $session->openid_auth_redirect_url);
        $this->assertSame(WalletSession::REQUEST_AUTH, $session->session_request);
        $this->assertSame(WalletSession::STATE_PENDING, $session->session_state);
        $this->assertSame(static::FAKE_STATE, $session->state);
        $this->assertSame(static::FAKE_NONCE, $session->nonce);
        $this->assertSame(static::FAKE_CODE_VERIFIER, $session->code_verifier);
        $this->assertSame('intent-123', $session->meta['intent_id']);
    }

    /**
     * @return void
     */
    public function testStartFundRequestCreatesPendingSessionForAuthenticatedUser(): void
    {
        $this->fakeWalletService(authorizationData: [
            'meta' => [
                'fund_id' => PHP_INT_MAX,
                'intent_id' => 'intent-123',
            ],
        ]);

        $implementation = $this->makeWalletImplementation();
        $fund = $this->makeTestFund($implementation->organization, implementation: $implementation);
        $requester = $this->makeIdentity();
        $sessionCount = WalletSession::count();

        $response = $this->apiStartWalletAuthRequest($implementation, [
            'request' => WalletSession::REQUEST_FUND_REQUEST,
            'fund_id' => $fund->id,
            'target' => 'voucher-123',
        ], $requester);

        $response->assertSuccessful();
        $this->assertSame($sessionCount + 1, WalletSession::count());

        /** @var WalletFlow $flow */
        $session = $this->findWalletSessionByRedirectUrl($response->json('redirect_url'));
        $flow = $implementation->availableWalletFlows()->first();

        $this->assertSame($session->getRedirectUrl(), $response->json('redirect_url'));
        $this->assertSame($flow->id, $session->wallet_flow_id);
        $this->assertSame($requester->address, $session->identity_address);
        $this->assertNull($session->target);
        $this->assertSame($fund->id, $session->meta['fund_id']);
        $this->assertSame('intent-123', $session->meta['intent_id']);
        $this->assertSame(WalletSession::REQUEST_FUND_REQUEST, $session->session_request);
        $this->assertSame(WalletSession::STATE_PENDING, $session->session_state);
        $this->assertSame($fund->urlWebshop(sprintf('/fondsen/%s/activeer', $fund->id)), $session->session_final_url);
    }

    /**
     * @return void
     */
    public function testStartFundRequestRejectsUnauthenticatedUser(): void
    {
        $this->fakeWalletService();

        $implementation = $this->makeWalletImplementation();
        $fund = $this->makeTestFund($implementation->organization, implementation: $implementation);
        $sessionCount = WalletSession::count();

        $this
            ->apiStartWalletAuthRequest($implementation, [
                'request' => WalletSession::REQUEST_FUND_REQUEST,
                'fund_id' => $fund->id,
            ])
            ->assertForbidden();

        $this->assertSame($sessionCount, WalletSession::count());
    }

    /**
     * @return void
     */
    public function testStartAuthRejectsAuthenticatedUser(): void
    {
        $this->fakeWalletService();

        $implementation = $this->makeWalletImplementation();
        $sessionCount = WalletSession::count();

        $this
            ->apiStartWalletAuthRequest($implementation, authProxy: $this->makeIdentity())
            ->assertForbidden();

        $this->assertSame($sessionCount, WalletSession::count());
    }

    /**
     * @return void
     */
    public function testStartAuthRejectsWhenWalletIsGloballyDisabled(): void
    {
        $this->fakeWalletService();

        Config::set('openid.enabled', false);

        $implementation = $this->makeWalletImplementation();
        $sessionCount = WalletSession::count();

        $this->apiStartWalletAuthRequest($implementation)->assertForbidden();
        $this->assertSame($sessionCount, WalletSession::count());
    }

    /**
     * @return void
     */
    public function testStartAuthRejectsNonWebshopClientType(): void
    {
        $this->fakeWalletService();

        $implementation = $this->makeWalletImplementation();
        $sessionCount = WalletSession::count();

        $this
            ->apiStartWalletAuthRequest($implementation, headers: [
                'Client-Type' => Implementation::FRONTEND_SPONSOR_DASHBOARD,
            ])
            ->assertForbidden();

        $this->assertSame($sessionCount, WalletSession::count());
    }

    /**
     * @return void
     */
    public function testStartAuthRejectsWhenOrganizationDoesNotAllowWallet(): void
    {
        $this->fakeWalletService();

        $implementation = $this->makeWalletImplementation(organizationData: [
            'allow_openid' => false,
        ]);
        $sessionCount = WalletSession::count();

        $this->apiStartWalletAuthRequest($implementation)->assertForbidden();
        $this->assertSame($sessionCount, WalletSession::count());
    }

    /**
     * @return void
     */
    public function testStartAuthRejectsWhenProviderIsDisabledOnImplementation(): void
    {
        $this->fakeWalletService();

        $implementation = $this->makeWalletImplementation([
            'openid_enabled' => false,
        ]);
        $sessionCount = WalletSession::count();

        $this->apiStartWalletAuthRequest($implementation)->assertForbidden();
        $this->assertSame($sessionCount, WalletSession::count());
    }

    /**
     * @return void
     */
    public function testStartAuthRejectsWhenFlowContextIsIncomplete(): void
    {
        $this->fakeWalletService();

        $implementation = $this->makeWalletImplementation(walletFlow: $this->makeWalletFlow([
            'context' => null,
        ]));
        $sessionCount = WalletSession::count();

        $this->apiStartWalletAuthRequest($implementation)->assertForbidden();
        $this->assertSame($sessionCount, WalletSession::count());
    }

    /**
     * @return void
     */
    public function testStartAuthRejectsUnknownFlowId(): void
    {
        $this->fakeWalletService();

        $implementation = $this->makeWalletImplementation();
        $sessionCount = WalletSession::count();

        $this
            ->apiStartWalletAuthRequest($implementation, [
                'flow_id' => WalletFlow::max('id') + 1,
            ])
            ->assertJsonValidationErrors(['flow_id']);

        $this->assertSame($sessionCount, WalletSession::count());
    }

    /**
     * @return void
     */
    public function testStartFundRequestRejectsInvalidFund(): void
    {
        $this->fakeWalletService();

        $implementation = $this->makeWalletImplementation();
        $sessionCount = WalletSession::count();

        $this
            ->apiStartWalletAuthRequest($implementation, [
                'request' => WalletSession::REQUEST_FUND_REQUEST,
                'fund_id' => Fund::max('id') + 1,
            ], $this->makeIdentity())
            ->assertJsonValidationErrors(['fund_id']);

        $this->assertSame($sessionCount, WalletSession::count());
    }

    /**
     * @return void
     */
    public function testStartAuthReturnsServiceUnavailableWhenAuthorizationUrlCannotBeBuilt(): void
    {
        $this->fakeFailingWalletService();

        $implementation = $this->makeWalletImplementation();
        $sessionCount = WalletSession::count();

        $this
            ->apiStartWalletAuthRequest($implementation)
            ->assertStatus(503)
            ->assertHeader('Error-Code', 'wallet_unknown_error');

        $this->assertSame($sessionCount, WalletSession::count());
    }

    /**
     * @return void
     */
    public function testStartAuthReturnsServiceUnavailableWhenVeridIntentFails(): void
    {
        $this->fakeFailingWalletService();

        $implementation = $this->makeWalletImplementation([
            'openid_verid_brand_uuid' => '00000000-0000-0000-0000-000000000001',
        ]);
        $sessionCount = WalletSession::count();

        $this
            ->apiStartWalletAuthRequest($implementation)
            ->assertStatus(503)
            ->assertHeader('Error-Code', 'wallet_unknown_error');

        $this->assertSame($sessionCount, WalletSession::count());
    }

    /**
     * @return void
     */
    public function testRedirectRejectsWhenProviderIsDisabledOnImplementation(): void
    {
        $implementation = $this->makeWalletImplementation();
        $session = $this->makeWalletSession($implementation);

        $implementation->forceFill(['openid_enabled' => false])->save();

        $response = $this->getJson($session->getRedirectUrl());

        $response->assertRedirect();
        $this->assertStringContainsString('wallet_error=not_enabled', $response->headers->get('Location'));
        $this->assertSame(WalletSession::STATE_ERROR, $session->refresh()->session_state);
    }

    /**
     * @return void
     */
    public function testRedirectRejectsWhenSessionFlowIsDisabled(): void
    {
        $implementation = $this->makeWalletImplementation();
        $session = $this->makeWalletSession($implementation);

        $implementation->wallet_flows()->detach();

        $response = $this->getJson($session->getRedirectUrl());

        $response->assertRedirect();
        $this->assertStringContainsString('wallet_error=not_enabled', $response->headers->get('Location'));
        $this->assertSame(WalletSession::STATE_ERROR, $session->refresh()->session_state);
    }

    /**
     * @param string $redirectUrl
     * @return WalletSession
     */
    protected function findWalletSessionByRedirectUrl(string $redirectUrl): WalletSession
    {
        preg_match('#/wallets/([^/]+)/redirect$#', (string) parse_url($redirectUrl, PHP_URL_PATH), $matches);

        return WalletSession::whereSessionUid($matches[1] ?? null)->firstOrFail();
    }
}
