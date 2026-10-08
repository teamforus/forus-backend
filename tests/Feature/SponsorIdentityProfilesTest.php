<?php

namespace Tests\Feature;

use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Permission;
use App\Models\ProfileRecord;
use App\Models\RecordTypeOption;
use App\Services\IConnectApiService\Exceptions\PersonBsnApiException;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use App\Services\IdentityProviderService\Support\IdentityProviderScimUserPayload;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesApiRequests;
use Tests\Traits\MakesRequesterVoucherPayouts;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestIdentityProviders;
use Tests\Traits\MakesTestOrganizations;

class SponsorIdentityProfilesTest extends TestCase
{
    use DatabaseTransactions;
    use MakesApiRequests;
    use MakesTestOrganizations;
    use MakesTestIdentities;
    use MakesTestIdentityProviders;
    use MakesTestFunds;
    use MakesRequesterVoucherPayouts;

    /**
     * Tests that a sponsor can list identities associated with their organization.
     *
     * @throws PersonBsnApiException
     * @return void
     */
    public function testSponsorCanListIdentities(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identity1 = $this->makeIdentity();
        $identity2 = $this->makeIdentity();

        $this->apiListIdentitiesRequest($organization->id, $organization->identity)
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');

        $fund->makeVoucher($identity1);
        $fund->makeFundRequest($identity2, []);

        $identity3 = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $organization->id);

        $this->apiListIdentitiesRequest($organization->id, $organization->identity)
            ->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.*.id', fn ($ids) => in_array($identity1->id, $ids))
            ->assertJsonPath('data.*.id', fn ($ids) => in_array($identity2->id, $ids))
            ->assertJsonPath('data.*.id', fn ($ids) => in_array($identity3->id, $ids));
    }

    /**
     * Tests that a sponsor can create a new identity for their organization.
     *
     * @return void
     */
    public function testSponsorCanCreateNewIdentity(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $payload = [
            'given_name' => 'Alice',
            'family_name' => 'Doe',
            'birth_date' => '1990-05-10',
            'city' => 'Testville',
            'street' => 'Main Street',
            'house_number' => '123',
            'postal_code' => '1234AB',
            'house_composition' => RecordTypeOption::query()
                ->whereRelation('record_type', 'key', 'house_composition')
                ->inRandomOrder()
                ->first()
                ->value,
            'living_arrangement' => RecordTypeOption::query()
                ->whereRelation('record_type', 'key', 'living_arrangement')
                ->inRandomOrder()
                ->first()
                ->value,
        ];

        $organization->forceFill([
            'allow_profiles_create' => false,
        ])->save();

        $this->apiMakeIdentityRequest($organization->id, $payload, $organization->identity)->assertForbidden();

        $organization->forceFill([
            'allow_profiles_create' => true,
        ])->save();

        $this->apiMakeIdentityRequest($organization->id, $payload, $organization->identity)
            ->assertSuccessful()
            ->assertJsonPath('data.profile.organization_id', $organization->id)
            ->assertJsonPath('data.records.given_name.0.value', 'Alice')
            ->assertJsonPath('data.records.family_name.0.value', 'Doe');
    }

    /**
     * Tests that a sponsor can view a single identity associated with their organization.
     *
     * @throws PersonBsnApiException
     * @return void
     */
    public function testSponsorCanViewSingleIdentity(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identity1 = $this->makeIdentity();
        $identity2 = $this->makeIdentity();

        $fund->makeVoucher($identity1);
        $fund->makeFundRequest($identity2, []);

        $identity3 = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $organization->id);

        foreach ([$identity1, $identity2, $identity3] as $identity) {
            $this->apiViewIdentityRequest($organization->id, $identity->id, $organization->identity)
                ->assertSuccessful()
                ->assertJsonPath('data.id', $identity->id)
                ->assertJsonPath(
                    'data.profile.organization_id',
                    $identity3->profiles()->count() > 0 ? $organization->id : null,
                );
        }
    }

    /**
     * Tests that sponsor identity details include fund request bank accounts.
     *
     * @throws \Throwable
     * @return void
     */
    public function testSponsorIdentityShowsFundRequestBankAccount(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $requester = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());

        $fund = $this->makePayoutEnabledFund($organization);
        $result = $this->makePayoutVoucherViaApplication($requester, $fund);
        $fundRequest = $result['fund_request'];
        $iban = $result['iban'];
        $ibanName = $result['iban_name'];

        $this->apiViewIdentityRequest($organization->id, $requester->id, $organization->identity)
            ->assertSuccessful()
            ->assertJsonFragment([
                'type' => 'fund_request',
                'type_id' => $fundRequest->id,
                'iban' => $iban,
                'name' => $ibanName,
            ]);
    }

    /**
     * Tests that a sponsor can update identities and that profiles and records are created.
     *
     * @throws PersonBsnApiException
     * @return void
     */
    public function testSponsorCanUpdateIdentity(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identity1 = $this->makeIdentity();
        $identity2 = $this->makeIdentity();
        $identity3 = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $organization->id);

        $fund->makeVoucher($identity1);
        $fund->makeFundRequest($identity2, []);

        $payload = [
            'given_name' => 'Updated',
            'family_name' => 'Person',
            'city' => 'Teststad',
        ];

        /**
         * @var Identity $identity
         */
        foreach ([$identity1, $identity2, $identity3] as $identity) {
            $this->apiUpdateIdentityRequest($organization->id, $identity->id, $payload, $organization->identity)
                ->assertSuccessful()
                ->assertJsonCount(3, 'data.records')
                ->assertJsonPath('data.profile.id', $identity->profiles[0]->id)
                ->assertJsonPath('data.profile.identity_id', $identity->id)
                ->assertJsonPath('data.profile.organization_id', $organization->id)
                ->assertJsonPath('data.records.given_name.0.value', 'Updated')
                ->assertJsonPath('data.records.family_name.0.value', 'Person')
                ->assertJsonPath('data.records.city.0.value', 'Teststad');
        }
    }

    /**
     * Tests that a sponsor can add a bank account to an identity.
     *
     * @return void
     */
    public function testSponsorCanAddBankAccount(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $identity = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $organization->id);

        $organization->findOrMakeProfile($identity);

        $payload = [
            'name' => 'John Doe',
            'iban' => 'NL91ABNA0417164300',
        ];

        $this->apiMakeBankAccountRequest($organization->id, $identity->id, $payload, $organization->identity)
            ->assertSuccessful()
            ->assertJsonPath('data.bank_accounts.0.name', 'John Doe')
            ->assertJsonPath('data.bank_accounts.0.iban', 'NL91ABNA0417164300');
    }

    /**
     * Tests that a sponsor can update a bank account of an identity.
     *
     * @return void
     */
    public function testSponsorCanUpdateBankAccount(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $identity = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $organization->id);

        $profile = $organization->findOrMakeProfile($identity);

        $bankAccount = $profile->profile_bank_accounts()->create([
            'name' => 'John Doe',
            'iban' => 'NL91ABNA0417164300',
        ]);

        $payload = [
            'name' => 'Jane Smith',
            'iban' => 'NL02ABNA0123456789',
        ];

        $this->apiUpdateBankAccountRequest(
            $organization->id,
            $identity->id,
            $bankAccount->id,
            $payload,
            $organization->identity
        )->assertSuccessful()
            ->assertJsonPath('data.bank_accounts.0.id', $bankAccount->id)
            ->assertJsonPath('data.bank_accounts.0.name', 'Jane Smith')
            ->assertJsonPath('data.bank_accounts.0.iban', 'NL02ABNA0123456789');
    }

    /**
     * Tests that a sponsor can delete a bank account from an identity.
     *
     * @return void
     */
    public function testSponsorCanDeleteBankAccount(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $identity = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $organization->id);

        $profile = $organization->findOrMakeProfile($identity);

        $bankAccount = $profile->profile_bank_accounts()->create([
            'name' => 'John Doe',
            'iban' => 'NL91ABNA0417164300',
        ]);

        $this->apiDeleteBankAccountRequest(
            $organization->id,
            $identity->id,
            $bankAccount->id,
            $organization->identity
        )->assertSuccessful();

        $this->assertDatabaseMissing('profile_bank_accounts', [
            'id' => $bankAccount->id,
        ]);
    }

    /**
     * Tests that a sponsor can export identities.
     *
     * @throws PersonBsnApiException
     * @return void
     */
    public function testSponsorCanExportIdentities(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identity1 = $this->makeIdentity();
        $identity2 = $this->makeIdentity();

        $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $organization->id);
        $fund->makeVoucher($identity1);
        $fund->makeFundRequest($identity2, []);

        $response = $this->apiExportIdentitiesRequest($organization->id, $organization->identity);

        $response->assertSuccessful();
        $response->assertHeader('content-disposition');
        $response->assertHeader('content-type', 'text/csv; charset=utf-8');
    }

    /**
     * Tests that a sponsor cannot access identities from another organization.
     *
     * @return void
     */
    public function testUnauthorizedSponsorCannotAccessOtherOrganizationsIdentities(): void
    {
        $sponsor1 = $this->makeTestOrganization($this->makeIdentity());
        $sponsor2 = $this->makeTestOrganization($this->makeIdentity());

        $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $sponsor1->id);
        $identity2 = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $sponsor2->id);

        $this->apiViewIdentityRequest($sponsor1->id, $identity2->id, $sponsor1->identity)->assertForbidden();

        $this->apiUpdateIdentityRequest(
            $sponsor1->id,
            $identity2->id,
            ['given_name' => 'Hacker'],
            $sponsor1->identity
        )->assertForbidden();

        $this->apiMakeBankAccountRequest(
            $sponsor1->id,
            $identity2->id,
            ['name' => 'John Doe', 'iban' => 'NL91ABNA0417164300'],
            $sponsor1->identity
        )->assertForbidden();
    }

    /**
     * @return void
     */
    public function testProfileStaffCanViewOwnRequestersBeforeVouchersWithoutExposingOtherSponsorsManagement(): void
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => true,
            'allow_identity_provider_requester_provisioning' => true,
        ]);

        $connection = $this->makeEntraConnection($sponsor);
        $viewer = $this->makeTestEmployeeWithPermissions($sponsor, [Permission::VIEW_IDENTITIES]);
        $active = $this->makeIdentityProviderRequester($connection);

        $disabled = $this->makeIdentityProviderRequester($connection, [
            'provisioning_status' => IdentityProviderMembership::PROVISIONING_STATUS_DISABLED,
        ]);

        $deleted = $this->makeIdentityProviderRequester($connection, [
            'provisioning_status' => IdentityProviderMembership::PROVISIONING_STATUS_DELETED,
        ]);

        $otherSponsor = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_provider_requester_provisioning' => true,
        ]);

        $otherViewer = $this->makeTestEmployeeWithPermissions($otherSponsor, [Permission::VIEW_IDENTITIES]);
        $this->makeIdentityProviderRequester($this->makeEntraConnection($otherSponsor));

        $response = $this->apiListIdentitiesRequest($sponsor->id, $viewer->identity)->assertOk();

        $this->assertEqualsCanonicalizing(
            [$active->identity_id, $disabled->identity_id, $deleted->identity_id],
            $response->json('data.*.id'),
        );

        foreach ([[$active, 'active'], [$disabled, 'disabled'], [$deleted, 'disabled']] as [$membership, $status]) {
            $this->apiViewIdentityRequest($sponsor->id, $membership->identity_id, $viewer->identity)
                ->assertOk()->assertJsonPath('data.identity_provider_management', ['provider' => 'entra', 'status' => $status]);
        }

        $this->apiViewIdentityRequest($otherSponsor->id, $active->identity_id, $otherViewer->identity)->assertForbidden();
        $this->makeTestFund($otherSponsor)->makeVoucher($active->identity);

        $this->apiViewIdentityRequest($otherSponsor->id, $active->identity_id, $otherViewer->identity)
            ->assertOk()->assertJsonPath('data.id', $active->identity_id)->assertJsonPath('data.identity_provider_management', null);
    }

    /**
     * @return void
     */
    public function testScimNameUpdatesPreserveProfileHistoryAndOtherSponsorsEdits(): void
    {
        Config::set('identity_providers.enabled', true);
        [$connection, $token] = $this->makeIdentityProviderScimContext();
        $sponsor = $connection->organization;
        $sponsor->forceFill(['allow_profiles' => true])->save();
        $manager = $this->makeTestEmployeeWithPermissions($sponsor, [Permission::MANAGE_IDENTITIES]);
        $membership = $this->provisionIdentityProviderRequester($connection, $token, $this->makeIdentityProviderScimUserPayload());
        $identity = $membership->identity;
        $otherSponsor = $this->makeTestOrganization($this->makeIdentity());
        $otherManager = $this->makeTestEmployeeWithPermissions($otherSponsor, [Permission::MANAGE_IDENTITIES]);
        $this->makeTestFund($otherSponsor)->makeVoucher($identity);

        $this->apiUpdateIdentityRequest($otherSponsor->id, $identity->id, [
            'given_name' => 'Other sponsor edit',
        ], $otherManager->identity)->assertOk();

        $this->apiViewIdentityRequest($sponsor->id, $identity->id, $manager->identity)
            ->assertOk()->assertJsonPath('data.records.given_name.0.value', 'Jane');

        $this->apiUpdateIdentityRequest($sponsor->id, $identity->id, [
            'given_name' => 'Local edit',
        ], $manager->identity)->assertOk();

        $this->apiIdentityProviderScimUsersRequest('PATCH', $connection, $token, [
            'schemas' => [IdentityProviderScimUserPayload::SCHEMA_PATCH],
            'Operations' => [['op' => 'replace', 'path' => 'name.givenName', 'value' => 'Entra update']],
        ], $membership->uid)->assertOk();

        $this->apiViewIdentityRequest($sponsor->id, $identity->id, $manager->identity)
            ->assertOk()
            ->assertJsonPath('data.records.given_name.*.value', ['Entra update', 'Local edit', 'Jane'])
            ->assertJsonPath('data.records.given_name.*.source', [ProfileRecord::SOURCE_ENTRA, null, ProfileRecord::SOURCE_ENTRA])
            ->assertJsonPath('data.records.given_name.*.employee.id', [null, $manager->id, null]);

        $this->apiViewIdentityRequest($otherSponsor->id, $identity->id, $otherManager->identity)
            ->assertOk()->assertJsonPath('data.records.given_name.*.value', ['Other sponsor edit']);

        $implementation = $this->makeTestImplementation($sponsor);
        $proxy = $this->makeIdentityProviderProxy($membership);

        $this->getJson('/api/v1/platform/profile', $this->makeApiHeaders($proxy, [
            'Client-Type' => Implementation::FRONTEND_WEBSHOP,
            'Client-Key' => $implementation->key,
        ]))->assertOk()
            ->assertJsonPath('records.given_name.0.value', 'Entra update')
            ->assertJsonPath('records.given_name.0.source', ProfileRecord::SOURCE_ENTRA);
    }

    /**
     * Sends a POST request to create a new identity under an organization.
     *
     * @param int $organizationId
     * @param array $payload
     * @param Identity $authIdentity
     * @return TestResponse
     */
    protected function apiMakeIdentityRequest(int $organizationId, array $payload, Identity $authIdentity): TestResponse
    {
        return $this->postJson(
            "/api/v1/platform/organizations/$organizationId/sponsor/identities",
            $payload,
            $this->makeApiHeaders($authIdentity),
        );
    }

    /**
     * Sends a PUT request to update a specific identity under an organization.
     *
     * @param int $organizationId
     * @param int $identityId
     * @param array $payload
     * @param Identity $authIdentity
     * @return TestResponse
     */
    protected function apiUpdateIdentityRequest(
        int $organizationId,
        int $identityId,
        array $payload,
        Identity $authIdentity,
    ): TestResponse {
        return $this->putJson(
            "/api/v1/platform/organizations/$organizationId/sponsor/identities/$identityId",
            $payload,
            $this->makeApiHeaders($authIdentity),
        );
    }

    /**
     * Sends a POST request to add a bank account to a specific identity.
     *
     * @param int $organizationId
     * @param int $identityId
     * @param array $payload
     * @param Identity $authIdentity
     * @return TestResponse
     */
    protected function apiMakeBankAccountRequest(
        int $organizationId,
        int $identityId,
        array $payload,
        Identity $authIdentity,
    ): TestResponse {
        return $this->postJson(
            "/api/v1/platform/organizations/$organizationId/sponsor/identities/$identityId/bank-accounts",
            $payload,
            $this->makeApiHeaders($authIdentity),
        );
    }

    /**
     * Sends a PATCH request to update a bank account for a specific identity.
     *
     * @param int $organizationId
     * @param int $identityId
     * @param int $bankAccountId
     * @param array $payload
     * @param Identity $authIdentity
     * @return TestResponse
     */
    protected function apiUpdateBankAccountRequest(
        int $organizationId,
        int $identityId,
        int $bankAccountId,
        array $payload,
        Identity $authIdentity,
    ): TestResponse {
        return $this->patchJson(
            "/api/v1/platform/organizations/$organizationId/sponsor/identities/$identityId/bank-accounts/$bankAccountId",
            $payload,
            $this->makeApiHeaders($authIdentity),
        );
    }

    /**
     * Deletes a bank account associated with a specific identity under an organization.
     *
     * @param int $organizationId
     * @param int $identityId
     * @param int $bankAccountId
     * @param Identity $authIdentity
     * @return TestResponse
     */
    protected function apiDeleteBankAccountRequest(
        int $organizationId,
        int $identityId,
        int $bankAccountId,
        Identity $authIdentity,
    ): TestResponse {
        return $this->deleteJson(
            "/api/v1/platform/organizations/$organizationId/sponsor/identities/$identityId/bank-accounts/$bankAccountId",
            [],
            $this->makeApiHeaders($authIdentity),
        );
    }
}
