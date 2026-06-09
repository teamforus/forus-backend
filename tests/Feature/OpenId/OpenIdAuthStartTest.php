<?php

namespace Tests\Feature\OpenId;

use App\Models\Fund;
use App\Models\Implementation;
use App\Services\OpenIdService\Models\OpenIdFlow;
use App\Services\OpenIdService\Models\OpenIdSession;
use App\Services\OpenIdService\OpenIdService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tests\Traits\MakesOpenIdTestData;
use Tests\Traits\MakesTestFunds;

class OpenIdAuthStartTest extends TestCase
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
    public function testStartAuthCreatesPendingSessionForUnauthenticatedWebshopUser(): void
    {
        $this->fakeOpenIdService(authorizationData: [
            'meta' => [
                'intent_id' => 'intent-123',
            ],
        ]);

        $implementation = $this->makeOpenIdImplementation();
        $sessionCount = OpenIdSession::count();
        $response = $this->apiStartOpenIdAuthRequest($implementation, [
            'target' => 'fundRequest-123',
        ]);

        $response->assertSuccessful();
        $this->assertSame($sessionCount + 1, OpenIdSession::count());

        /** @var OpenIdFlow $flow */
        $session = $this->findOpenIdSessionByRedirectUrl($response->json('redirect_url'));
        $flow = $implementation->availableOpenIdFlows()->first();

        $this->assertSame($session->getRedirectUrl(), $response->json('redirect_url'));
        $this->assertSame($flow->id, $session->openid_flow_id);
        $this->assertSame(OpenIdService::PROVIDER_VERID, $session->openid_flow->provider);
        $this->assertSame($implementation->id, $session->implementation_id);
        $this->assertSame(Implementation::FRONTEND_WEBSHOP, $session->client_type);
        $this->assertNull($session->identity_address);
        $this->assertSame('fundRequest-123', $session->target);
        $this->assertSame($implementation->urlFrontend(Implementation::FRONTEND_WEBSHOP), $session->session_final_url);
        $this->assertSame(static::FAKE_REDIRECT_URL, $session->openid_auth_redirect_url);
        $this->assertSame(OpenIdSession::REQUEST_AUTH, $session->session_request);
        $this->assertSame(OpenIdSession::STATE_PENDING, $session->session_state);
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
        $this->fakeOpenIdService(authorizationData: [
            'meta' => [
                'fund_id' => PHP_INT_MAX,
                'intent_id' => 'intent-123',
            ],
        ]);

        $implementation = $this->makeOpenIdImplementation();
        $fund = $this->makeTestFund($implementation->organization, implementation: $implementation);
        $requester = $this->makeIdentity();
        $sessionCount = OpenIdSession::count();

        $response = $this->apiStartOpenIdAuthRequest($implementation, [
            'request' => OpenIdSession::REQUEST_FUND_REQUEST,
            'fund_id' => $fund->id,
            'target' => 'voucher-123',
        ], $requester);

        $response->assertSuccessful();
        $this->assertSame($sessionCount + 1, OpenIdSession::count());

        /** @var OpenIdFlow $flow */
        $session = $this->findOpenIdSessionByRedirectUrl($response->json('redirect_url'));
        $flow = $implementation->availableOpenIdFlows()->first();

        $this->assertSame($session->getRedirectUrl(), $response->json('redirect_url'));
        $this->assertSame($flow->id, $session->openid_flow_id);
        $this->assertSame($requester->address, $session->identity_address);
        $this->assertNull($session->target);
        $this->assertSame($fund->id, $session->meta['fund_id']);
        $this->assertSame('intent-123', $session->meta['intent_id']);
        $this->assertSame(OpenIdSession::REQUEST_FUND_REQUEST, $session->session_request);
        $this->assertSame(OpenIdSession::STATE_PENDING, $session->session_state);
        $this->assertSame($fund->urlWebshop(sprintf('/fondsen/%s/activeer', $fund->id)), $session->session_final_url);
    }

    /**
     * @return void
     */
    public function testStartFundRequestRejectsUnauthenticatedUser(): void
    {
        $this->fakeOpenIdService();

        $implementation = $this->makeOpenIdImplementation();
        $fund = $this->makeTestFund($implementation->organization, implementation: $implementation);
        $sessionCount = OpenIdSession::count();

        $this
            ->apiStartOpenIdAuthRequest($implementation, [
                'request' => OpenIdSession::REQUEST_FUND_REQUEST,
                'fund_id' => $fund->id,
            ])
            ->assertForbidden();

        $this->assertSame($sessionCount, OpenIdSession::count());
    }

    /**
     * @return void
     */
    public function testStartAuthRejectsAuthenticatedUser(): void
    {
        $this->fakeOpenIdService();

        $implementation = $this->makeOpenIdImplementation();
        $sessionCount = OpenIdSession::count();

        $this
            ->apiStartOpenIdAuthRequest($implementation, authProxy: $this->makeIdentity())
            ->assertForbidden();

        $this->assertSame($sessionCount, OpenIdSession::count());
    }

    /**
     * @return void
     */
    public function testStartAuthRejectsWhenOpenIdIsGloballyDisabled(): void
    {
        $this->fakeOpenIdService();

        Config::set('openid.enabled', false);

        $implementation = $this->makeOpenIdImplementation();
        $sessionCount = OpenIdSession::count();

        $this->apiStartOpenIdAuthRequest($implementation)->assertForbidden();
        $this->assertSame($sessionCount, OpenIdSession::count());
    }

    /**
     * @return void
     */
    public function testStartAuthRejectsNonWebshopClientType(): void
    {
        $this->fakeOpenIdService();

        $implementation = $this->makeOpenIdImplementation();
        $sessionCount = OpenIdSession::count();

        $this
            ->apiStartOpenIdAuthRequest($implementation, headers: [
                'Client-Type' => Implementation::FRONTEND_SPONSOR_DASHBOARD,
            ])
            ->assertForbidden();

        $this->assertSame($sessionCount, OpenIdSession::count());
    }

    /**
     * @return void
     */
    public function testStartAuthRejectsWhenOrganizationDoesNotAllowOpenId(): void
    {
        $this->fakeOpenIdService();

        $implementation = $this->makeOpenIdImplementation(organizationData: [
            'allow_openid' => false,
        ]);
        $sessionCount = OpenIdSession::count();

        $this->apiStartOpenIdAuthRequest($implementation)->assertForbidden();
        $this->assertSame($sessionCount, OpenIdSession::count());
    }

    /**
     * @return void
     */
    public function testStartAuthRejectsWhenProviderIsDisabledOnImplementation(): void
    {
        $this->fakeOpenIdService();

        $implementation = $this->makeOpenIdImplementation([
            'openid_enabled' => false,
        ]);
        $sessionCount = OpenIdSession::count();

        $this->apiStartOpenIdAuthRequest($implementation)->assertForbidden();
        $this->assertSame($sessionCount, OpenIdSession::count());
    }

    /**
     * @return void
     */
    public function testStartAuthRejectsWhenFlowContextIsIncomplete(): void
    {
        $this->fakeOpenIdService();

        $implementation = $this->makeOpenIdImplementation(openidFlow: $this->makeOpenIdFlow([
            'context' => null,
        ]));
        $sessionCount = OpenIdSession::count();

        $this->apiStartOpenIdAuthRequest($implementation)->assertForbidden();
        $this->assertSame($sessionCount, OpenIdSession::count());
    }

    /**
     * @return void
     */
    public function testStartAuthRejectsUnknownFlowId(): void
    {
        $this->fakeOpenIdService();

        $implementation = $this->makeOpenIdImplementation();
        $sessionCount = OpenIdSession::count();

        $this
            ->apiStartOpenIdAuthRequest($implementation, [
                'flow_id' => OpenIdFlow::max('id') + 1,
            ])
            ->assertJsonValidationErrors(['flow_id']);

        $this->assertSame($sessionCount, OpenIdSession::count());
    }

    /**
     * @return void
     */
    public function testStartFundRequestRejectsInvalidFund(): void
    {
        $this->fakeOpenIdService();

        $implementation = $this->makeOpenIdImplementation();
        $sessionCount = OpenIdSession::count();

        $this
            ->apiStartOpenIdAuthRequest($implementation, [
                'request' => OpenIdSession::REQUEST_FUND_REQUEST,
                'fund_id' => Fund::max('id') + 1,
            ], $this->makeIdentity())
            ->assertJsonValidationErrors(['fund_id']);

        $this->assertSame($sessionCount, OpenIdSession::count());
    }

    /**
     * @return void
     */
    public function testStartAuthReturnsServiceUnavailableWhenAuthorizationUrlCannotBeBuilt(): void
    {
        $this->fakeFailingOpenIdService();

        $implementation = $this->makeOpenIdImplementation();
        $sessionCount = OpenIdSession::count();

        $this
            ->apiStartOpenIdAuthRequest($implementation)
            ->assertStatus(503)
            ->assertHeader('Error-Code', 'openid_unknown_error');

        $this->assertSame($sessionCount, OpenIdSession::count());
    }

    /**
     * @return void
     */
    public function testStartAuthReturnsServiceUnavailableWhenVeridIntentFails(): void
    {
        $this->fakeFailingOpenIdService();

        $implementation = $this->makeOpenIdImplementation([
            'openid_verid_brand_uuid' => '00000000-0000-0000-0000-000000000001',
        ]);
        $sessionCount = OpenIdSession::count();

        $this
            ->apiStartOpenIdAuthRequest($implementation)
            ->assertStatus(503)
            ->assertHeader('Error-Code', 'openid_unknown_error');

        $this->assertSame($sessionCount, OpenIdSession::count());
    }

    /**
     * @return void
     */
    public function testRedirectRejectsWhenProviderIsDisabledOnImplementation(): void
    {
        $implementation = $this->makeOpenIdImplementation();
        $session = $this->makeOpenIdSession($implementation);

        $implementation->forceFill(['openid_enabled' => false])->save();

        $response = $this->getJson($session->getRedirectUrl());

        $response->assertRedirect();
        $this->assertStringContainsString('openid_error=not_enabled', $response->headers->get('Location'));
        $this->assertSame(OpenIdSession::STATE_ERROR, $session->refresh()->session_state);
    }

    /**
     * @return void
     */
    public function testRedirectRejectsWhenSessionFlowIsDisabled(): void
    {
        $implementation = $this->makeOpenIdImplementation();
        $session = $this->makeOpenIdSession($implementation);

        $implementation->openid_flows()->detach();

        $response = $this->getJson($session->getRedirectUrl());

        $response->assertRedirect();
        $this->assertStringContainsString('openid_error=not_enabled', $response->headers->get('Location'));
        $this->assertSame(OpenIdSession::STATE_ERROR, $session->refresh()->session_state);
    }

    /**
     * @param string $redirectUrl
     * @return OpenIdSession
     */
    protected function findOpenIdSessionByRedirectUrl(string $redirectUrl): OpenIdSession
    {
        preg_match('#/openid/([^/]+)/redirect$#', (string) parse_url($redirectUrl, PHP_URL_PATH), $matches);

        return OpenIdSession::whereSessionUid($matches[1] ?? null)->firstOrFail();
    }
}
