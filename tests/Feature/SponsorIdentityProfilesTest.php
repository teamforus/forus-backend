<?php

namespace Tests\Feature;

use App\Models\Identity;
use App\Models\RecordTypeOption;
use App\Services\IConnectApiService\Exceptions\PersonBsnApiException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesApiRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;

class SponsorIdentityProfilesTest extends TestCase
{
    use DatabaseTransactions;
    use MakesApiRequests;
    use MakesTestOrganizations;
    use MakesTestIdentities;
    use MakesTestFunds;

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
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
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
     * Sends a GET request to list all identities under an organization.
     *
     * @param int $organizationId
     * @param Identity $authIdentity
     * @return TestResponse
     */
    protected function apiListIdentitiesRequest(int $organizationId, Identity $authIdentity): TestResponse
    {
        return $this->getJson(
            "/api/v1/platform/organizations/$organizationId/sponsor/identities",
            $this->makeApiHeaders($authIdentity),
        );
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
     * Sends a GET request to view a specific identity under an organization.
     *
     * @param int $organizationId
     * @param int $identityId
     * @param Identity $authIdentity
     * @return TestResponse
     */
    protected function apiViewIdentityRequest(int $organizationId, int $identityId, Identity $authIdentity): TestResponse
    {
        return $this->getJson(
            "/api/v1/platform/organizations/$organizationId/sponsor/identities/$identityId",
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

    /**
     * Sends a GET request to export identities for a specific organization in CSV format.
     *
     * @param int $organizationId
     * @param Identity $authIdentity
     * @return TestResponse
     */
    protected function apiExportIdentitiesRequest(int $organizationId, Identity $authIdentity): TestResponse
    {
        return $this->get(
            "/api/v1/platform/organizations/$organizationId/sponsor/identities/export?data_format=csv",
            $this->makeApiHeaders($authIdentity),
        );
    }
}
