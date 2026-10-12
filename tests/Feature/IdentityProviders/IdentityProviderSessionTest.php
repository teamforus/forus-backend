<?php

namespace Tests\Feature\IdentityProviders;

use App\Models\IdentityProxy;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tests\Traits\MakesTest2FA;
use Tests\Traits\MakesTestIdentityProviders;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class IdentityProviderSessionTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTest2FA;
    use MakesTestIdentityProviders;
    use MakesTestOrganizations;

    protected IdentityProxy $localProxy;
    protected IdentityProxy $sourceProxy;
    protected IdentityProxy $unrelatedProxy;
    protected IdentityProviderMembership $membership;
    protected IdentityProviderConnection $entraConnection;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('identity_providers.enabled', true);
        $this->withHeaders(['Client-Type' => Implementation::FRONTEND_SPONSOR_DASHBOARD]);

        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        $this->entraConnection = $this->makeEntraConnection($organization);
        $this->membership = $this->makeIdentityProviderMembership($this->entraConnection, $organization->addEmployee($this->makeIdentity()));
        $this->sourceProxy = $this->makeIdentityProviderProxy($this->membership);
        $this->localProxy = $this->makeIdentityProxy($this->membership->identity);
        $otherOrganization = $this->makeTestOrganization($this->makeIdentity());

        $otherMembership = $this->makeIdentityProviderMembership(
            $this->makeEntraConnection($otherOrganization),
            $otherOrganization->addEmployee($this->makeIdentity()),
        );

        $this->unrelatedProxy = $this->makeIdentityProviderProxy($otherMembership);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPinAndQrDescendantsInheritBindingWithoutConfirmed2faAndAreRevokedByUnlink(): void
    {
        $this->assertFalse($this->sourceProxy->is2FAConfirmed());

        $pinResponse = $this->apiCreateIdentityProxyRequest('code')->assertCreated();

        $this->apiAuthorizeIdentityProxyRequest('code', [
            'auth_code' => (string) $pinResponse->json('auth_code'),
        ], $this->sourceProxy)->assertOk();

        $pinProxy = IdentityProxy::where('access_token', $pinResponse->json('access_token'))->firstOrFail();
        $this->assertIdentityProviderProxy($pinProxy, $this->membership);

        $qrResponse = $this->apiCreateIdentityProxyRequest('token')->assertCreated();

        $this->apiAuthorizeIdentityProxyRequest('token', [
            'auth_token' => $qrResponse->json('auth_token'),
        ], $pinProxy)->assertOk();

        $qrProxy = IdentityProxy::where('access_token', $qrResponse->json('access_token'))->firstOrFail();
        $this->assertIdentityProviderProxy($qrProxy, $this->membership);

        $this->apiGetIdentityProviderLinksRequest($qrProxy)->assertOk()->assertJsonPath('meta.can_manage_links', false);
        $this->apiDeleteIdentityProviderLinkRequest($this->membership, $qrProxy)->assertForbidden();
        $this->apiDeleteIdentityProviderLinkRequest($this->membership, $this->localProxy)->assertNoContent();

        $this->assertRevokedProxies([$this->sourceProxy, $pinProxy, $qrProxy]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testShortTokenInheritsBindingAndPausePreservesLocalAndUnrelatedSessions(): void
    {
        $proxy = $this->createShortTokenProxy($this->sourceProxy);
        $this->assertIdentityProviderProxy($proxy, $this->membership);

        $localChild = $this->createShortTokenProxy($this->localProxy);

        $this->assertSame($this->localProxy->identity_address, $localChild->identity_address);
        $this->assertNull($localChild->identity_provider_binding);

        $this->apiChangeIdentityProviderConnectionStateRequest(
            $this->entraConnection->organization,
            $this->entraConnection,
            'pause',
            $this->entraConnection->organization->identity,
        )->assertOk();

        $this->assertRevokedProxies([$this->sourceProxy, $proxy]);
        $this->getJson('/api/v1/identity', $this->makeApiHeaders($localChild))->assertOk();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testShared2faHandoffPreservesBindingAndConfirmationUntilDisconnect(): void
    {
        $this->confirmSource2FA();
        $handoff = $this->createShared2faHandoff();

        $response = $this->apiExchangeIdentityProxyRequest('email', $handoff->exchange_token)->assertOk();
        $proxy = IdentityProxy::where('access_token', $response->json('access_token'))->firstOrFail();

        $this->assertSame($handoff->id, $proxy->id);
        $this->assertSame($this->sourceProxy->identity_2fa_uuid, $proxy->identity_2fa_uuid);
        $this->assertTrue($proxy->is2FAConfirmed());
        $this->assertIdentityProviderProxy($proxy, $this->membership);

        $this->apiChangeIdentityProviderConnectionStateRequest(
            $this->entraConnection->organization,
            $this->entraConnection,
            'disconnect',
            $this->entraConnection->organization->identity,
        )->assertOk();

        $this->assertRevokedProxies([$proxy]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPendingShared2faHandoffCannotActivateAfterSourceLogoutAndRevocation(): void
    {
        $this->confirmSource2FA();
        $handoff = $this->createShared2faHandoff();

        $this->assertTrue($handoff->isPending());
        $this->assertSame($this->membership->id, $handoff->identity_provider_binding->membership_id);

        $this->apiChangeIdentityProviderConnectionStateRequest(
            $this->entraConnection->organization,
            $this->entraConnection,
            'pause',
            $this->entraConnection->organization->identity,
        )->assertOk();

        $this->apiExchangeIdentityProxyRequest('email', $handoff->exchange_token)->assertNotFound();

        $this->assertRevokedProxies([$handoff]);
    }

    /**
     * @param IdentityProxy $sourceProxy
     * @return IdentityProxy
     */
    protected function createShortTokenProxy(IdentityProxy $sourceProxy): IdentityProxy
    {
        $response = $this->apiCreateIdentityProxyRequest('short-token', $sourceProxy)->assertCreated();
        $exchange = $this->apiExchangeIdentityProxyRequest('short-token', $response->json('exchange_token'))->assertOk();

        return IdentityProxy::where('access_token', $exchange->json('access_token'))->firstOrFail();
    }

    /**
     * @return IdentityProxy
     */
    protected function createShared2faHandoff(): IdentityProxy
    {
        $response = $this->apiCreateIdentityProxyRequest('shared-2fa', $this->sourceProxy)->assertOk();
        parse_str(parse_url($response->json('redirect_url'), PHP_URL_QUERY), $query);

        $this->getJson('/api/v1/identity', $this->makeApiHeaders($this->sourceProxy))->assertUnauthorized();

        return IdentityProxy::where('exchange_token', $query['token'])->firstOrFail();
    }

    /**
     * @return void
     */
    protected function confirmSource2FA(): void
    {
        $provider = $this->setup2FAProvider($this->localProxy, 'authenticator');
        $this->activate2FAProvider($this->localProxy, $provider);
        $this->sourceProxy->inherit2FAStateFrom($this->localProxy->refresh());

        $this->assertTrue($this->sourceProxy->is2FAConfirmed());
    }

    /**
     * @param IdentityProxy[] $proxies
     * @return void
     */
    protected function assertRevokedProxies(array $proxies): void
    {
        foreach ($proxies as $proxy) {
            $this->getJson('/api/v1/identity', $this->makeApiHeaders($proxy))->assertUnauthorized();
        }

        $this->getJson('/api/v1/identity', $this->makeApiHeaders($this->localProxy))->assertOk();
        $this->getJson('/api/v1/identity', $this->makeApiHeaders($this->unrelatedProxy))->assertOk();
    }
}
