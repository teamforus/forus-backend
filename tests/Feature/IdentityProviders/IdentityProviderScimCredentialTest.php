<?php

namespace Tests\Feature\IdentityProviders;

use App\Models\Implementation;
use App\Models\Role;
use App\Services\IdentityProviderService\Models\IdentityProviderScimCredential;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\MakesTestIdentityProviders;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class IdentityProviderScimCredentialTest extends TestCase
{
    use DatabaseTransactions;
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
    public function testOwnerCanIssueRotateAndRevokeCredentials(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => true,
            'allow_identity_provider_requester_provisioning' => true,
        ]);

        $connection = $this->makeEntraConnection($organization);
        $proxy = $this->makeIdentityProxy($organization->identity);

        $issued = $this->apiIssueIdentityProviderScimCredentialRequest($organization, $connection, $proxy)->assertCreated();
        $token = $issued->json('data.token');
        $credential = IdentityProviderScimCredential::where('uid', $issued->json('data.uid'))->firstOrFail();

        $this->assertSame(hash('sha256', $token), $credential->token_hash);
        $issued->assertJsonMissingPath('data.token_hash');

        $this->apiGetIdentityProviderConnectionRequest($organization, $proxy)
            ->assertOk()
            ->assertJsonPath('data.scim_credential.uid', $credential->uid)
            ->assertJsonMissingPath('data.scim_credential.token')
            ->assertJsonMissingPath('data.scim_credential.token_hash');

        $this->apiGetIdentityProviderScimUsersRequest($connection, $token)->assertOk();

        $rotated = $this->apiIssueIdentityProviderScimCredentialRequest($organization, $connection, $proxy)->assertCreated();
        $replacement = IdentityProviderScimCredential::where('uid', $rotated->json('data.uid'))->firstOrFail();

        $this->assertNotSame($credential->id, $replacement->id);
        $this->assertTrue($credential->refresh()->isRevoked());
        $this->apiGetIdentityProviderScimUsersRequest($connection, $token)->assertUnauthorized();
        $this->apiGetIdentityProviderScimUsersRequest($connection, $rotated->json('data.token'))->assertOk();

        $this->apiRevokeIdentityProviderScimCredentialRequest($organization, $connection, $replacement, $proxy)->assertNoContent();

        $this->assertTrue($replacement->refresh()->isRevoked());
        $this->apiGetIdentityProviderScimUsersRequest($connection, $rotated->json('data.token'))->assertUnauthorized();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testCredentialManagementRequiresLocalOwnerAndMatchingConnection(): void
    {
        $owner = $this->makeIdentity();

        $organization = $this->makeTestOrganization($owner, [
            'allow_identity_providers' => true,
            'allow_identity_provider_requester_provisioning' => true,
        ]);

        $connection = $this->makeEntraConnection($organization);
        $credential = $this->makeIdentityProviderScimCredential($connection, Str::random(64));
        $localProxy = $this->makeIdentityProxy($owner);
        $employee = $organization->addEmployee($this->makeIdentity(), [Role::where('key', 'admin')->firstOrFail()->id]);
        $employeeProxy = $this->makeIdentityProxy($employee->identity);
        $otherOrganization = $this->makeTestOrganization($this->makeIdentity());
        $otherConnection = $this->makeEntraConnection($otherOrganization);
        $otherCredential = $this->makeIdentityProviderScimCredential($otherConnection, Str::random(64));
        $membership = $this->makeIdentityProviderMembership($otherConnection, $otherOrganization->addEmployee($owner));
        $entraProxy = $this->makeIdentityProviderProxy($membership);

        foreach ([$employeeProxy, $entraProxy] as $proxy) {
            $this->apiIssueIdentityProviderScimCredentialRequest($organization, $connection, $proxy)->assertForbidden();
            $this->apiRevokeIdentityProviderScimCredentialRequest($organization, $connection, $credential, $proxy)->assertForbidden();
        }

        $this->apiIssueIdentityProviderScimCredentialRequest($organization, $otherConnection, $localProxy)->assertNotFound();
        $this->apiRevokeIdentityProviderScimCredentialRequest($organization, $connection, $otherCredential, $localProxy)
            ->assertNotFound();

        $this->assertFalse($credential->refresh()->isRevoked());
        $this->assertFalse($otherCredential->refresh()->isRevoked());
        $this->assertSame(1, $connection->scim_credentials()->count());
    }
}
