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

class IdentityProviderManagedRestrictionsTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestIdentityProviders;
    use MakesTestOrganizations;

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
            'allow_identity_provider_employee_provisioning' => true,
            'allow_identity_provider_requester_provisioning' => true,
        ]);

        $this->entraConnection = $this->makeEntraConnection($organization);
    }

    /**
     * @return void
     */
    public function testManagedAccountCanReadEmailsButCannotManageThem(): void
    {
        foreach (IdentityProviderMembership::ACCOUNT_TYPES as $accountType) {
            $this->entraConnection->organization->forceFill([
                'allow_identity_provider_employee_provisioning' => true,
                'allow_identity_provider_requester_provisioning' => true,
            ])->save();

            $membership = $accountType === IdentityProviderMembership::ACCOUNT_TYPE_EMPLOYEE
                ? $this->makeIdentityProviderEmployee($this->entraConnection)
                : $this->makeIdentityProviderRequester($this->entraConnection);

            $proxy = $this->makeIdentityProviderProxy($membership);
            $identity = $membership->identity;
            $primary = $identity->primary_email;
            $secondary = $identity->addEmail($this->makeUniqueEmail(), verified: true);

            $this->apiGetIdentityRequest($proxy)->assertOk()->assertJsonPath('can_manage_emails', false);

            $this->apiGetIdentityEmailRequest($primary, $proxy)
                ->assertOk()->assertJsonPath('data.email', $primary->email);

            $this->apiStoreIdentityEmailRequest(['email' => $this->makeUniqueEmail()], $proxy)->assertForbidden();
            $this->apiSetIdentityEmailPrimaryRequest($secondary, $proxy)->assertForbidden();
            $this->apiDeleteIdentityEmailRequest($secondary, $proxy)->assertForbidden();

            $this->assertSame($primary->id, $identity->refresh()->primary_email->id);
            $this->assertFalse($secondary->refresh()->trashed());
            $this->assertSame(2, $identity->emails()->count());

            $this->entraConnection->organization->forceFill([
                'allow_identity_provider_employee_provisioning' => false,
                'allow_identity_provider_requester_provisioning' => false,
            ])->save();

            $this->apiGetIdentityRequest($proxy)->assertOk()->assertJsonPath('can_manage_emails', false);
            $this->apiStoreIdentityEmailRequest(['email' => $this->makeUniqueEmail()], $proxy)->assertForbidden();
        }
    }

    /**
     * @return void
     */
    public function testManagedAccountCannotLinkOrUnlinkAccounts(): void
    {
        foreach (IdentityProviderMembership::ACCOUNT_TYPES as $accountType) {
            $membership = $accountType === IdentityProviderMembership::ACCOUNT_TYPE_EMPLOYEE
                ? $this->makeIdentityProviderEmployee($this->entraConnection)
                : $this->makeIdentityProviderRequester($this->entraConnection);

            $proxy = $this->makeIdentityProviderProxy($membership);
            $identity = $membership->identity;

            $this->apiStartIdentityProviderLinkRequest($this->entraConnection, $proxy)
                ->assertForbidden()->assertJsonPath('message', __('policies.identity_providers.link_managed_requester'));

            $this->apiDeleteIdentityProviderLinkRequest($membership, $proxy)
                ->assertForbidden()->assertJsonPath('message', __('policies.identity_providers.link_managed_requester'));

            $this->assertTrue($membership->refresh()->isClaimed());
            $this->assertSame($identity->id, $membership->identity_id);
            $this->assertFalse(IdentityProviderOidcSession::where('identity_id', $identity->id)->exists());

            $localProxy = Identity::makeProxy('short_token', $identity, IdentityProxy::STATE_ACTIVE);
            $decision = Gate::forUser($identity)->inspect('manageLinks', [IdentityProviderMembership::class, $localProxy]);

            $this->assertTrue($decision->denied());
            $this->assertSame(__('policies.identity_providers.link_managed_requester'), $decision->message());
        }
    }

    /**
     * @return void
     */
    public function testManagedAccountCannotCreateOrganizationsOrBecomeOwner(): void
    {
        foreach (IdentityProviderMembership::ACCOUNT_TYPES as $accountType) {
            $membership = $accountType === IdentityProviderMembership::ACCOUNT_TYPE_EMPLOYEE
                ? $this->makeIdentityProviderEmployee($this->entraConnection)
                : $this->makeIdentityProviderRequester($this->entraConnection);

            $proxy = $this->makeIdentityProviderProxy($membership);
            $identity = $membership->identity;
            $sponsor = $this->entraConnection->organization;
            $otherSponsor = $this->makeTestOrganization($this->makeIdentity());
            $adminRole = Role::where('key', 'admin')->firstOrFail();

            foreach ($membership->isEmployee() ? [$otherSponsor] : [$sponsor, $otherSponsor] as $organization) {
                $this->apiMakeEmployeeRequest($organization, [
                    'email' => $identity->email, 'roles' => [$adminRole->id],
                ], $organization->identity)->assertForbidden()
                    ->assertJsonPath('message', __('policies.employees.managed_identity'));
            }

            $this->assertSame($membership->employee_id ? 1 : 0, $identity->employees()->count());

            $this->apiMakeOrganizationRequest([
                ...$sponsor->only(['name', 'iban', 'email', 'business_type_id']),
                'phone' => '1234567890',
                'kvk' => Organization::GENERIC_KVK,
            ], $proxy)->assertForbidden();

            $this->assertFalse($identity->organizations()->exists());

            $employee = $membership->employee ?? $sponsor->addEmployee($identity, [$adminRole->id]);
            $employee->roles()->sync([$adminRole->id]);
            $ownerAddress = $sponsor->identity_address;

            $this->apiTransferOrganizationOwnershipRequest($sponsor, $employee, $sponsor->identity)->assertForbidden();

            $this->assertSame($ownerAddress, $sponsor->refresh()->identity_address);
        }
    }
}
