<?php

namespace Tests\Feature\IdentityProviders;

use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Role;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderOidcSession;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentityProviders;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class IdentityProviderConnectionTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestFunds;
    use MakesTestIdentityProviders;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('identity_providers.enabled', true);
        $this->withHeaders(['Client-Type' => Implementation::FRONTEND_SPONSOR_DASHBOARD]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testConnectionManagementRequiresOrganizationOwner(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        $connection = $this->makeEntraConnection($organization);
        $employee = $this->makeIdentity();
        $organization->addEmployee($employee, [Role::where('key', 'admin')->firstOrFail()->id]);
        $proxy = $this->makeIdentityProxy($employee);

        $this->apiGetIdentityProviderConnectionRequest($organization, $proxy)->assertForbidden();
        $this->apiChangeIdentityProviderConnectionStateRequest($organization, $connection, 'pause', $proxy)->assertForbidden();

        $this->assertTrue($connection->refresh()->isEnabled());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOwnerMustUseLocalSessionToManageConnection(): void
    {
        $owner = $this->makeIdentity();

        $organization = $this->makeTestOrganization($owner, [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        $connection = $this->makeEntraConnection($organization);
        $otherOrganization = $this->makeTestOrganization($this->makeIdentity());
        $otherConnection = $this->makeEntraConnection($otherOrganization);
        $membership = $this->makeIdentityProviderMembership($otherConnection, $otherOrganization->addEmployee($owner));
        $proxy = $this->makeIdentityProviderProxy($membership);

        $this->apiGetIdentityProviderConnectionRequest($organization, $proxy)->assertForbidden();
        $this->apiChangeIdentityProviderConnectionStateRequest($organization, $connection, 'pause', $proxy)->assertForbidden();

        $this->assertTrue($connection->refresh()->isEnabled());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testManagementRequiresModuleAndOrganizationCapability(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        $connection = $this->makeEntraConnection($organization);
        $proxy = $this->makeIdentityProxy($organization->identity);

        Config::set('identity_providers.enabled', false);

        $this->apiGetIdentityProviderConnectionRequest($organization, $proxy)->assertForbidden();
        $this->apiChangeIdentityProviderConnectionStateRequest($organization, $connection, 'pause', $proxy)->assertForbidden();

        Config::set('identity_providers.enabled', true);
        $organization->forceFill(['allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_NO])->save();

        $this->apiGetIdentityProviderConnectionRequest($organization, $proxy)->assertForbidden();
        $this->apiChangeIdentityProviderConnectionStateRequest($organization, $connection, 'pause', $proxy)->assertForbidden();

        $this->assertTrue($connection->refresh()->isEnabled());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testConnectionRoutesRejectAnotherOrganizationsConnection(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        $otherConnection = $this->makeEntraConnection($this->makeTestOrganization($this->makeIdentity()));
        $proxy = $this->makeIdentityProxy($organization->identity);

        $this->apiGetIdentityProviderConnectionRequest($organization, $proxy, $otherConnection)->assertNotFound();
        $this->apiGetIdentityProviderConnectionEventsRequest($organization, $otherConnection, [], $proxy)->assertNotFound();

        foreach (['pause', 'resume', 'disconnect'] as $action) {
            $this->apiChangeIdentityProviderConnectionStateRequest($organization, $otherConnection, $action, $proxy)->assertNotFound();
        }

        $this->assertTrue($otherConnection->refresh()->isEnabled());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOwnerCanInspectPauseAndResumeConnectionWithTargetedRevocation(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        $ownerProxy = $this->makeIdentityProxy($organization->identity);

        $this->apiGetIdentityProviderConnectionRequest($organization, $ownerProxy)->assertOk()->assertJsonPath('data', null);

        $connection = $this->makeEntraConnection($organization);
        $employee = $organization->addEmployee($this->makeIdentity());
        $membership = $this->makeIdentityProviderMembership($connection, $employee);
        $entraHeaders = $this->makeApiHeaders($this->makeIdentityProviderProxy($membership));
        $localHeaders = $this->makeApiHeaders($this->makeIdentityProxy($employee->identity));

        $implementation = $this->makeTestImplementation($organization, [
            'url_webshop' => 'https://webshop.example.test',
            'entra_login_enabled' => true,
        ]);

        $otherOrganization = $this->makeTestOrganization($this->makeIdentity());
        $otherConnection = $this->makeEntraConnection($otherOrganization);

        $otherMembership = $this->makeIdentityProviderMembership(
            $otherConnection,
            $otherOrganization->addEmployee($employee->identity),
        );

        $otherHeaders = $this->makeApiHeaders($this->makeIdentityProviderProxy($otherMembership));

        $this->apiGetIdentityProviderConnectionRequest($organization, $ownerProxy)
            ->assertOk()
            ->assertJsonPath('data.uid', $connection->uid)
            ->assertJsonPath('data.status', IdentityProviderConnection::STATUS_ENABLED);

        $this->apiGetIdentityProviderConnectionRequest($organization, $ownerProxy, $connection)
            ->assertOk()
            ->assertJsonPath('data.uid', $connection->uid)
            ->assertJsonPath('data.status', IdentityProviderConnection::STATUS_ENABLED)
            ->assertJsonPath('data.managed_employees_count', 1)
            ->assertJsonPath('data.webshops.0.id', $implementation->id)
            ->assertJsonPath('data.webshops.0.entra_login_enabled', true);

        $this->getJson('/api/v1/identity', $entraHeaders)->assertOk();

        $this->apiChangeIdentityProviderConnectionStateRequest($organization, $connection, 'pause', $ownerProxy)
            ->assertOk()
            ->assertJsonPath('data.status', IdentityProviderConnection::STATUS_PAUSED);

        $this->assertFalse($connection->refresh()->isEnabled());
        $this->assertFalse($implementation->refresh()->entraLoginEnabled());
        $this->assertTrue($membership->refresh()->isClaimed());
        $this->getJson('/api/v1/identity', $entraHeaders)->assertUnauthorized();
        $this->getJson('/api/v1/identity', $localHeaders)->assertOk();
        $this->getJson('/api/v1/identity', $otherHeaders)->assertOk();

        $this->apiChangeIdentityProviderConnectionStateRequest($organization, $connection, 'resume', $ownerProxy)
            ->assertOk()
            ->assertJsonPath('data.status', IdentityProviderConnection::STATUS_ENABLED)
            ->assertJsonPath('data.paused_at', null);

        $this->assertTrue($implementation->refresh()->entraLoginEnabled());
        $this->getJson('/api/v1/identity', $entraHeaders)->assertUnauthorized();

        $this->assertEquals([
            IdentityProviderConnection::EVENT_CONNECTION_PAUSED,
            IdentityProviderConnection::EVENT_CONNECTION_RESUMED,
        ], $connection->logs()->orderBy('id')->pluck('event')->all());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testDisconnectRetiresLinksInvalidatesSessionsAndPreservesHistory(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        $connection = $this->makeEntraConnection($organization);
        $employee = $organization->addEmployee($this->makeIdentity());
        $membership = $this->makeIdentityProviderMembership($connection, $employee);
        $entraHeaders = $this->makeApiHeaders($this->makeIdentityProviderProxy($membership));
        $localHeaders = $this->makeApiHeaders($this->makeIdentityProxy($employee->identity));
        $ownerProxy = $this->makeIdentityProxy($organization->identity);

        $pending = $this->makeOidcSession($connection, [
            'mode' => IdentityProviderOidcSession::MODE_SELF_LINK,
            'identity_id' => $employee->identity->id,
        ]);

        $exchange = $this->makeOidcSession($connection, [
            'membership_id' => $membership->id,
            'identity_id' => $employee->identity->id,
            'status' => IdentityProviderOidcSession::STATUS_RESOLVED,
            'exchange_token_hash' => hash('sha256', Str::random(32)),
            'browser_token_hash' => hash('sha256', Str::random(32)),
            'exchange_expires_at' => now()->addMinute(),
        ]);

        $otherConnection = $this->makeEntraConnection($this->makeTestOrganization($this->makeIdentity()));

        $otherConnection->update([
            'status' => IdentityProviderConnection::STATUS_DISCONNECTED,
            'disconnected_at' => now(),
        ]);

        $otherSession = $this->makeOidcSession($this->makeEntraConnection($otherConnection->organization));

        $this->getJson('/api/v1/identity', $entraHeaders)->assertOk();

        $this->apiChangeIdentityProviderConnectionStateRequest($organization, $connection, 'disconnect', $ownerProxy)
            ->assertOk()
            ->assertJsonPath('data.status', IdentityProviderConnection::STATUS_DISCONNECTED)
            ->assertJsonPath('data.managed_employees_count', 0);

        $this->assertTrue($membership->refresh()->isRetired());
        $this->assertNull($membership->identity_id);
        $this->assertNull($membership->employee_id);
        $this->assertNull($membership->external_identity()->firstOrFail()->identity_id);
        $this->assertNotNull($organization->employees()->find($employee->id));
        $this->assertSame(IdentityProviderOidcSession::STATUS_ERROR, $pending->refresh()->status);
        $this->assertSame(IdentityProviderOidcSession::STATUS_ERROR, $exchange->refresh()->status);
        $this->assertNull($exchange->exchange_token_hash);
        $this->assertNull($exchange->browser_token_hash);
        $this->assertSame(IdentityProviderOidcSession::STATUS_PENDING, $otherSession->refresh()->status);
        $this->getJson('/api/v1/identity', $entraHeaders)->assertUnauthorized();
        $this->getJson('/api/v1/identity', $localHeaders)->assertOk();

        $this->apiGetIdentityProviderConnectionRequest($organization, $ownerProxy)->assertOk()->assertJsonPath('data', null);

        $this->apiGetIdentityProviderConnectionHistoryRequest($organization, [], $ownerProxy)
            ->assertOk()
            ->assertJsonPath('data.*.uid', [$connection->uid]);

        $this->apiGetIdentityProviderConnectionRequest($organization, $ownerProxy, $connection)
            ->assertOk()
            ->assertJsonPath('data.status', IdentityProviderConnection::STATUS_DISCONNECTED);

        $this->apiGetIdentityProviderConnectionEventsRequest($organization, $connection, [], $ownerProxy)
            ->assertOk()
            ->assertJsonPath('data.0.event_type', IdentityProviderConnection::EVENT_CONNECTION_DISCONNECTED)
            ->assertJsonPath('data.1.event_type', IdentityProviderConnection::EVENT_MEMBERSHIP_RETIRED_BY_DISCONNECTION);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testEventsAreScopedToConnectionAndOrdered(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        $connection = $this->makeEntraConnection($organization);
        $proxy = $this->makeIdentityProxy($organization->identity);
        $otherConnection = $this->makeEntraConnection($this->makeTestOrganization($this->makeIdentity()));
        $otherConnection->recordEvent(IdentityProviderConnection::EVENT_CONNECTION_CONNECTED);

        $this->freezeTime();

        $first = $connection->recordEvent(IdentityProviderConnection::EVENT_CONNECTION_CONNECTED);
        $second = $connection->recordEvent(IdentityProviderConnection::EVENT_CONNECTION_PAUSED);
        $third = $connection->recordEvent(IdentityProviderConnection::EVENT_CONNECTION_RESUMED);

        $this->apiGetIdentityProviderConnectionEventsRequest($organization, $connection, [], $proxy)
            ->assertOk()
            ->assertJsonPath('data.*.id', [$third->id, $second->id, $first->id]);
    }

    /**
     * @param IdentityProviderConnection $connection
     * @param array $attributes
     * @return IdentityProviderOidcSession
     */
    protected function makeOidcSession(IdentityProviderConnection $connection, array $attributes = []): IdentityProviderOidcSession
    {
        return IdentityProviderOidcSession::create([
            'connection_id' => $connection->id,
            'mode' => IdentityProviderOidcSession::MODE_DASHBOARD,
            'status' => IdentityProviderOidcSession::STATUS_PENDING,
            'state' => hash('sha256', Str::random(32)),
            'nonce' => Str::random(32),
            'code_verifier' => Str::random(64),
            'authorization_url' => 'https://login.example.test/authorize',
            'final_url' => 'https://dashboard.example.test/auth/entra',
            'expires_at' => now()->addMinutes(10),
            ...$attributes,
        ]);
    }
}
