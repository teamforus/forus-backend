<?php

namespace Tests\Feature\IdentityProviders;

use App\Models\Identity;
use App\Models\IdentityProxy;
use App\Models\Organization;
use App\Models\Role;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use App\Services\IdentityProviderService\Models\IdentityProviderOidcSession;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;
use Tests\Traits\MakesTestIdentityProviders;
use Tests\Traits\MakesTestOrganizations;

class IdentityProviderRequesterRestrictionsTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestIdentityProviders;
    use MakesTestOrganizations;

    protected IdentityProxy $proxy;
    protected IdentityProviderMembership $membership;
    protected IdentityProviderConnection $entraConnection;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('identity_providers.enabled', true);

        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => true,
            'allow_identity_provider_requester_provisioning' => true,
        ]);

        $this->entraConnection = $this->makeEntraConnection($organization);
        $this->membership = $this->makeIdentityProviderRequester($this->entraConnection);
        $this->proxy = $this->makeIdentityProviderProxy($this->membership);
    }

    /**
     * @return void
     */
    public function testRequesterCanReadEmailsButCannotManageThem(): void
    {
        $identity = $this->membership->identity;
        $primary = $identity->primary_email;
        $secondary = $identity->addEmail($this->makeUniqueEmail(), verified: true);
        $headers = $this->makeApiHeaders($this->proxy);

        $this->getJson('/api/v1/identity', $headers)->assertOk()->assertJsonPath('can_manage_emails', false);

        $this->getJson("/api/v1/identity/emails/$primary->id", $headers)
            ->assertOk()->assertJsonPath('data.email', $primary->email);

        $this->postJson('/api/v1/identity/emails', ['email' => $this->makeUniqueEmail()], $headers)->assertForbidden();
        $this->patchJson("/api/v1/identity/emails/$secondary->id/primary", [], $headers)->assertForbidden();
        $this->deleteJson("/api/v1/identity/emails/$secondary->id", [], $headers)->assertForbidden();

        $this->assertSame($primary->id, $identity->refresh()->primary_email->id);
        $this->assertFalse($secondary->refresh()->trashed());
        $this->assertSame(2, $identity->emails()->count());

        $this->entraConnection->organization->forceFill(['allow_identity_provider_requester_provisioning' => false])->save();

        $this->getJson('/api/v1/identity', $headers)->assertOk()->assertJsonPath('can_manage_emails', false);
        $this->postJson('/api/v1/identity/emails', ['email' => $this->makeUniqueEmail()], $headers)->assertForbidden();
    }

    /**
     * @return void
     */
    public function testRequesterCannotLinkOrUnlinkAccounts(): void
    {
        $identity = $this->membership->identity;

        $this->apiStartIdentityProviderLinkRequest($this->entraConnection, $this->proxy)
            ->assertForbidden()->assertJsonPath('message', __('policies.identity_providers.link_managed_requester'));

        $this->apiDeleteIdentityProviderLinkRequest($this->membership, $this->proxy)
            ->assertForbidden()->assertJsonPath('message', __('policies.identity_providers.link_managed_requester'));

        $this->assertTrue($this->membership->refresh()->isClaimed());
        $this->assertSame($identity->id, $this->membership->identity_id);
        $this->assertFalse(IdentityProviderOidcSession::where('identity_id', $identity->id)->exists());

        $localProxy = Identity::makeProxy('short_token', $identity, IdentityProxy::STATE_ACTIVE);
        $decision = Gate::forUser($identity)->inspect('manageLinks', [IdentityProviderMembership::class, $localProxy]);

        $this->assertTrue($decision->denied());
        $this->assertSame(__('policies.identity_providers.link_managed_requester'), $decision->message());
    }

    /**
     * @return void
     */
    public function testRequesterCannotBecomeEmployeeOrOrganizationOwner(): void
    {
        $identity = $this->membership->identity;
        $sponsor = $this->entraConnection->organization;
        $otherSponsor = $this->makeTestOrganization($this->makeIdentity());
        $adminRole = Role::where('key', 'admin')->firstOrFail();

        foreach ([$sponsor, $otherSponsor] as $organization) {
            $this->apiMakeEmployeeRequest($organization, [
                'email' => $identity->email, 'roles' => [$adminRole->id],
            ], $organization->identity)
                ->assertForbidden()->assertJsonPath('message', __('policies.employees.managed_requester'));
        }

        $this->assertFalse($identity->employees()->exists());

        $this->apiMakeOrganizationRequest([
            ...$sponsor->only(['name', 'iban', 'email', 'business_type_id']),
            'phone' => '1234567890',
            'kvk' => Organization::GENERIC_KVK,
        ], $this->proxy)->assertForbidden();

        $this->assertFalse($identity->organizations()->exists());

        $employee = $sponsor->addEmployee($identity, [$adminRole->id]);
        $ownerAddress = $sponsor->identity_address;

        $this->patchJson("/api/v1/platform/organizations/$sponsor->id/transfer-ownership", [
            'employee_id' => $employee->id,
        ], $this->makeApiHeaders($sponsor->identity))->assertForbidden();

        $this->assertSame($ownerAddress, $sponsor->refresh()->identity_address);
    }
}
