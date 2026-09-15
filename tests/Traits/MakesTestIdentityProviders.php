<?php

namespace Tests\Traits;

use App\Models\Employee;
use App\Models\IdentityProxy;
use App\Models\Organization;
use App\Services\IdentityProviderService\Models\ExternalIdentity;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use App\Services\IdentityProviderService\Services\IdentityProviderAccountLinkService;
use App\Services\OpenIdService\OpenIdClientService;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Fakes\FakeOpenIdClientService;

trait MakesTestIdentityProviders
{
    /**
     * @param array $claims
     * @return FakeOpenIdClientService
     */
    protected function bindFakeOpenIdClient(array $claims): FakeOpenIdClientService
    {
        $client = (new FakeOpenIdClientService())->setClaims($claims);
        $this->app->instance(OpenIdClientService::class, $client);

        return $client;
    }

    /**
     * @param Organization $organization
     * @return IdentityProviderConnection
     */
    protected function makeEntraConnection(Organization $organization): IdentityProviderConnection
    {
        $tenantId = Str::uuid()->toString();

        return $organization->identity_provider_connections()->create([
            'provider' => IdentityProviderConnection::PROVIDER_ENTRA,
            'tenant_id' => $tenantId,
            'issuer' => "https://login.microsoftonline.com/$tenantId/v2.0",
            'status' => IdentityProviderConnection::STATUS_ENABLED,
            'created_by_identity_id' => $organization->identity->id,
            'updated_by_identity_id' => $organization->identity->id,
            'consented_at' => now(),
        ]);
    }

    /**
     * @param IdentityProviderConnection $connection
     * @param Employee $employee
     * @return IdentityProviderMembership
     */
    protected function makeIdentityProviderMembership(
        IdentityProviderConnection $connection,
        Employee $employee,
    ): IdentityProviderMembership {
        $externalIdentity = ExternalIdentity::create([
            'provider' => $connection->provider,
            'tenant_id' => $connection->tenant_id,
            'object_id' => Str::uuid()->toString(),
            'issuer' => $connection->issuer,
            'subject' => Str::uuid()->toString(),
            'identity_id' => $employee->identity->id,
        ]);

        return $connection->memberships()->create([
            'external_identity_id' => $externalIdentity->id,
            'identity_id' => $employee->identity->id,
            'employee_id' => $employee->id,
            'claim_state' => IdentityProviderMembership::CLAIM_CLAIMED,
            'consent_version' => IdentityProviderAccountLinkService::CONSENT_VERSION,
            'consented_at' => now(),
        ]);
    }

    /**
     * @param IdentityProviderMembership $membership
     * @return IdentityProxy
     */
    protected function makeIdentityProviderProxy(IdentityProviderMembership $membership): IdentityProxy
    {
        $proxy = $this->makeIdentityProxy($membership->identity, tokenType: 'short_token');

        $proxy->identity_provider_binding()->create([
            'connection_id' => $membership->connection_id,
            'membership_id' => $membership->id,
        ]);

        return $proxy;
    }

    /**
     * @param TestResponse $response
     * @param string $errorCode
     * @param string $finalUrl
     * @return void
     */
    protected function assertIdentityProviderCallbackError(TestResponse $response, string $errorCode, string $finalUrl): void
    {
        $response->assertRedirect();
        $location = $response->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $query);

        $this->assertSame($finalUrl, explode('?', $location, 2)[0]);
        $this->assertSame($errorCode, $query['entra_error'] ?? null);
    }

    /**
     * @param IdentityProxy $proxy
     * @param IdentityProviderMembership $membership
     * @return void
     */
    protected function assertIdentityProviderProxy(IdentityProxy $proxy, IdentityProviderMembership $membership): void
    {
        $this->assertSame($membership->identity->address, $proxy->identity_address);
        $this->assertSame($membership->connection_id, $proxy->identity_provider_binding->connection_id);
        $this->assertSame($membership->id, $proxy->identity_provider_binding->membership_id);
        $this->getJson('/api/v1/identity', $this->makeApiHeaders($proxy))->assertOk();
    }
}
