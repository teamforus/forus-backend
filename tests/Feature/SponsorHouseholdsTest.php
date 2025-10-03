<?php

namespace Tests\Feature;

use App\Models\Identity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesApiRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;

class SponsorHouseholdsTest extends TestCase
{
    use DatabaseTransactions;
    use MakesApiRequests;
    use MakesTestOrganizations;
    use MakesTestIdentities;
    use MakesTestFunds;

    /**
     * Tests that a sponsor can successfully list households associated with their organization.
     *
     * @return void
     */
    public function testSponsorCanListHouseholds(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);

        $household1Id = $this->apiMakeHouseholdRequest($organization->id, [
            'uid' => Str::random(20),
        ], $organization->identity)->assertSuccessful()->json('data.id');

        $household2Id = $this->apiMakeHouseholdRequest($organization->id, [
            'uid' => Str::random(20),
        ], $organization->identity)->assertSuccessful()->json('data.id');

        $this->apiListHouseholdsRequest($organization->id, $organization->identity)
            ->assertSuccessful()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.*.id', fn ($ids) => in_array($household1Id, $ids))
            ->assertJsonPath('data.*.id', fn ($ids) => in_array($household2Id, $ids));
    }

    /**
     * Verifies that a sponsor cannot list households belonging to another organization.
     *
     * @return void
     */
    public function testSponsorCannotListHouseholdsOfAnotherOrganization(): void
    {
        $organization1 = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);
        $organization2 = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);

        $this->apiMakeHouseholdRequest($organization2->id, [
            'uid' => Str::random(20),
            'city' => 'Rotterdam',
        ], $organization2->identity)->assertSuccessful();

        $this->apiListHouseholdsRequest($organization2->id, $organization1->identity)
            ->assertForbidden();
    }

    /**
     * Tests that a sponsor can successfully create a household with valid data.
     *
     * @return void
     */
    public function testSponsorCanCreateHouseholdWithValidData(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);

        $payload = [
            'uid' => Str::random(20),
            'living_arrangement' => 'single_parent_household',
            'city' => 'Amsterdam',
            'street' => 'Baker Street',
            'house_nr' => '221B',
            'house_nr_addition' => 'A',
            'postal_code' => '1234AB',
            'neighborhood' => 'Center',
            'municipality' => 'Amsterdam',
        ];

        $this->apiMakeHouseholdRequest($organization->id, $payload, $organization->identity)
            ->assertSuccessful()
            ->assertJsonPath('data.organization_id', $organization->id)
            ->assertJsonPath('data.city', 'Amsterdam')
            ->assertJsonPath('data.postal_code', '1234AB');
    }

    /**
     * Tests that a sponsor cannot create a household with invalid data.
     *
     * @return void
     */
    public function testSponsorCannotCreateHouseholdWithInvalidData(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);

        $this
            ->apiMakeHouseholdRequest($organization->id, [
                // invalid option
                'living_arrangement' => 'invalid_option',
                // should be string
                'postal_code' => 123456,
            ], $organization->identity)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['uid', 'living_arrangement', 'postal_code']);
    }

    /**
     * @return void
     */
    public function testSponsorCanViewHousehold(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);

        $householdId = $this
            ->apiMakeHouseholdRequest($organization->id, [
                'uid' => Str::random(20),
                'city' => 'Rotterdam',
            ], $organization->identity)
            ->assertSuccessful()
            ->json('data.id');

        $this->apiViewHouseholdRequest($organization->id, $householdId, $organization->identity)
            ->assertSuccessful()
            ->assertJsonPath('data.id', $householdId)
            ->assertJsonPath('data.city', 'Rotterdam');
    }

    /**
     * @return void
     */
    public function testSponsorCannotViewHouseholdFromAnotherOrganization(): void
    {
        $org1 = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);
        $org2 = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);

        $householdId = $this
            ->apiMakeHouseholdRequest($org2->id, ['uid' => Str::random(20)], $org2->identity)
            ->assertSuccessful()
            ->json('data.id');

        $this->apiViewHouseholdRequest($org2->id, $householdId, $org1->identity)
            ->assertForbidden();
    }

    /**
     * Verifies that a sponsor can successfully update a household record they have permission to access,
     * including validation of the updated values in the response.
     */
    public function testSponsorCanUpdateHousehold(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);

        $householdId = $this
            ->apiMakeHouseholdRequest($organization->id, [
                'uid' => Str::random(20),
                'city' => 'Groningen',
            ], $organization->identity)
            ->assertSuccessful()
            ->json('data.id');

        $this
            ->apiUpdateHouseholdRequest($organization->id, $householdId, [
                'city' => 'Utrecht',
                'postal_code' => '4321BA',
            ], $organization->identity)
            ->assertSuccessful()
            ->assertJsonPath('data.id', $householdId)
            ->assertJsonPath('data.city', 'Utrecht')
            ->assertJsonPath('data.postal_code', '4321BA');
    }

    /**
     * Verifies that a sponsor from one organization cannot update a household record that belongs to a different organization.
     */
    public function testSponsorCannotUpdateHouseholdFromAnotherOrganization(): void
    {
        $org1 = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);
        $org2 = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);

        $householdId = $this
            ->apiMakeHouseholdRequest($org2->id, ['uid' => Str::random(20)], $org2->identity)
            ->assertSuccessful()
            ->json('data.id');

        $this->apiUpdateHouseholdRequest($org2->id, $householdId, ['city' => 'Leeuwarden'], $org1->identity)
            ->assertForbidden();
    }

    /**
     * Tests that a sponsor cannot update a household with invalid data.
     *
     * @return void
     */
    public function testSponsorCannotUpdateHouseholdWithInvalidData(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);

        $storeData = [
            'uid' => Str::random(20),
            'organization_id' => $organization->id,
            'city' => 'Arnhem',
        ];

        $updateData = [
            'living_arrangement' => 'not_real_option',
        ];

        $householdId = $this
            ->apiMakeHouseholdRequest($organization->id, $storeData, $organization->identity)
            ->assertSuccessful()
            ->json('data.id');

        $this->apiUpdateHouseholdRequest($organization->id, $householdId, $updateData, $organization->identity)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['living_arrangement']);
    }

    /**
     * Verifies that a sponsor organization can successfully delete a household they own.
     *
     * @return void
     */
    public function testSponsorCanDeleteHousehold(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);

        $householdId = $this
            ->apiMakeHouseholdRequest($organization->id, ['uid' => Str::random(20)], $organization->identity)
            ->assertSuccessful()
            ->json('data.id');

        $this->apiDeleteHouseholdMemberRequest($organization->id, $householdId, $organization->identity)
            ->assertSuccessful();

        $this->assertSoftDeleted('households', [
            'id' => $householdId,
        ]);
    }

    /**
     * Verifies that a sponsor organization cannot delete a household that belongs to a different organization.
     *
     * @return void
     */
    public function testSponsorCannotDeleteHouseholdFromAnotherOrganization(): void
    {
        $org1 = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);
        $org2 = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);

        $householdId = $this
            ->apiMakeHouseholdRequest($org2->id, ['uid' => Str::random(20)], $org2->identity)
            ->assertSuccessful()
            ->json('data.id');

        $this->apiDeleteHouseholdMemberRequest($org2->id, $householdId, $org1->identity)
            ->assertForbidden();

        $this->assertDatabaseHas('households', [
            'id' => $householdId,
        ]);
    }

    /**
     * Tests that a sponsor organization can successfully add a member to a household.
     *
     * @return void
     */
    public function testSponsorCanAddMemberToHousehold(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $this->apiMakeVoucherAsSponsorRequest($organization, $this->makeTestFund($organization), [
            'amount' => 100,
            'email' => $identity->email,
            'assign_by_type' => 'email',
        ], $organization->identity)->assertSuccessful();

        $householdId = $this
            ->apiMakeHouseholdRequest($organization->id, ['uid' => Str::random(20)], $organization->identity)
            ->assertSuccessful()
            ->json('data.id');

        $this->apiCreateHouseholdMemberRequest($organization->id, $householdId, ['identity_id' => $identity->id], $organization->identity)
            ->assertCreated();

        $profile = $organization->findOrMakeProfile($identity);

        $this->assertDatabaseHas('household_profiles', [
            'household_id' => $householdId,
            'profile_id' => $profile->id,
        ]);
    }

    /**
     * Verifies that a sponsor cannot add the same member to a household more than once.
     *
     * @return void
     */
    public function testSponsorCannotAddSameMemberTwice(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $this->apiMakeVoucherAsSponsorRequest($organization, $this->makeTestFund($organization), [
            'amount' => 100,
            'email' => $identity->email,
            'assign_by_type' => 'email',
        ], $organization->identity)->assertSuccessful();

        $householdId = $this
            ->apiMakeHouseholdRequest($organization->id, ['uid' => Str::random(20)], $organization->identity)
            ->assertSuccessful()
            ->json('data.id');

        $this->apiCreateHouseholdMemberRequest($organization->id, $householdId, ['identity_id' => $identity->id], $organization->identity)
            ->assertCreated();

        $this->apiCreateHouseholdMemberRequest($organization->id, $householdId, ['identity_id' => $identity->id], $organization->identity)
            ->assertSuccessful();

        $this->assertDatabaseCount('household_profiles', 1);
    }

    /**
     * Ensures that a sponsor organization cannot add a member from another organization to their household.
     *
     * @return void
     */
    public function testSponsorCannotAddMemberFromAnotherOrganization(): void
    {
        $org1 = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);
        $org2 = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);
        $identity1 = $this->makeIdentity($this->makeUniqueEmail());
        $identity2 = $this->makeIdentity($this->makeUniqueEmail());

        $this->apiMakeVoucherAsSponsorRequest($org1, $this->makeTestFund($org1), [
            'amount' => 100,
            'email' => $identity1->email,
            'assign_by_type' => 'email',
        ], $org1->identity)->assertSuccessful();

        $this->apiMakeVoucherAsSponsorRequest($org2, $this->makeTestFund($org2), [
            'amount' => 100,
            'email' => $identity2->email,
            'assign_by_type' => 'email',
        ], $org2->identity)->assertSuccessful();

        $householdId = $this
            ->apiMakeHouseholdRequest($org1->id, ['uid' => Str::random(20)], $org1->identity)
            ->assertSuccessful()
            ->json('data.id');

        $this->apiCreateHouseholdMemberRequest($org1->id, $householdId, ['identity_id' => $identity2->id], $org1->identity)
            ->assertUnprocessable();
    }

    /**
     * Tests that a sponsor organization can successfully remove a member from a household.
     *
     * @return void
     */
    public function testSponsorCanRemoveMemberFromHousehold(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $this->apiMakeVoucherAsSponsorRequest($organization, $this->makeTestFund($organization), [
            'amount' => 100,
            'email' => $identity->email,
            'assign_by_type' => 'email',
        ], $organization->identity)->assertSuccessful();

        $householdId = $this
            ->apiMakeHouseholdRequest($organization->id, ['uid' => Str::random(20)], $organization->identity)
            ->assertSuccessful()
            ->json('data.id');

        $householdProfileId = $this
            ->apiCreateHouseholdMemberRequest($organization->id, $householdId, ['identity_id' => $identity->id], $organization->identity)
            ->assertCreated()
            ->json('data.id');

        $this->apiRemoveHouseholdMemberRequest($organization->id, $householdId, $householdProfileId, $organization->identity)
            ->assertSuccessful();

        $profile = $organization->findOrMakeProfile($identity);

        $this->assertDatabaseMissing('household_profiles', [
            'household_id' => $householdId,
            'profile_id' => $profile->id,
        ]);
    }

    /**
     * Verifies that a sponsor organization cannot remove a member from a household that belongs to a different organization.
     *
     * @return void
     */
    public function testSponsorCannotRemoveMemberFromAnotherOrganization(): void
    {
        $org1 = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);
        $org2 = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $this->apiMakeVoucherAsSponsorRequest($org1, $this->makeTestFund($org1), [
            'amount' => 100,
            'email' => $identity->email,
            'assign_by_type' => 'email',
        ], $org1->identity)->assertSuccessful();

        $householdId = $this
            ->apiMakeHouseholdRequest($org1->id, ['uid' => Str::random(20)], $org1->identity)
            ->assertSuccessful()
            ->json('data.id');

        $householdProfileId = $this
            ->apiCreateHouseholdMemberRequest($org1->id, $householdId, ['identity_id' => $identity->id], $org1->identity)
            ->assertCreated()
            ->json('data.id');

        $this->apiRemoveHouseholdMemberRequest($org1->id, $householdId, $householdProfileId, $org2->identity)
            ->assertForbidden();
    }

    /**
     * Tests that creating a household with invalid data triggers validation errors.
     *
     * @return void
     */
    public function testCreateHouseholdValidationErrors(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);

        $invalidPayload = [
            'living_arrangement' => 'invalid_value',
            'postal_code' => 12345, // should be string
        ];

        $this->apiMakeHouseholdRequest($organization->id, $invalidPayload, $organization->identity)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['uid', 'living_arrangement', 'postal_code']);
    }

    /**
     * Tests that updating a household record with invalid data triggers validation errors.
     *
     * @return void
     */
    public function testUpdateHouseholdValidationErrors(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);

        $householdId = $this
            ->apiMakeHouseholdRequest($organization->id, ['uid' => Str::random(20)], $organization->identity)
            ->assertSuccessful()
            ->json('data.id');

        $invalidPayload = [
            'postal_code' => 99999, // should be string
            'living_arrangement' => '-', // not in enum
        ];

        $this->apiUpdateHouseholdRequest($organization->id, $householdId, $invalidPayload, $organization->identity)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['postal_code', 'living_arrangement']);
    }

    /**
     * Tests that adding a household member with invalid data triggers validation error responses.
     *
     * @return void
     */
    public function testAddMemberValidationErrors(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_households' => true]);

        $householdId = $this
            ->apiMakeHouseholdRequest($organization->id, ['uid' => Str::random(20)], $organization->identity)
            ->assertSuccessful()
            ->json('data.id');

        $invalidPayload = [
            'identity_id' => 'not-an-id',
        ];

        $this->apiCreateHouseholdMemberRequest($organization->id, $householdId, $invalidPayload, $organization->identity)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['identity_id']);
    }

    /**
     * Sends a POST request to add a member to a household within a sponsor organization.
     *
     * @param int $organizationId
     * @param int $householdId
     * @param array $payload
     * @param Identity $authIdentity
     * @return TestResponse
     */
    protected function apiCreateHouseholdMemberRequest(
        int $organizationId,
        int $householdId,
        array $payload,
        Identity $authIdentity,
    ): TestResponse {
        return $this->postJson(
            "/api/v1/platform/organizations/$organizationId/sponsor/households/$householdId/household-profiles",
            $payload,
            $this->makeApiHeaders($authIdentity),
        );
    }

    /**
     * Sends a DELETE request to remove a member from a household within a sponsor organization.
     *
     * @param int $organizationId
     * @param int $householdId
     * @param int $profileId
     * @param Identity $authIdentity
     * @return TestResponse
     */
    protected function apiRemoveHouseholdMemberRequest(
        int $organizationId,
        int $householdId,
        int $profileId,
        Identity $authIdentity,
    ): TestResponse {
        return $this->deleteJson(
            "/api/v1/platform/organizations/$organizationId/sponsor/households/$householdId/household-profiles/$profileId",
            [],
            $this->makeApiHeaders($authIdentity),
        );
    }

    /**
     * Sends a DELETE request to remove a household under an organization.
     *
     * @param int $organizationId
     * @param int $householdId
     * @param Identity $authIdentity
     * @return TestResponse
     */
    protected function apiDeleteHouseholdMemberRequest(
        int $organizationId,
        int $householdId,
        Identity $authIdentity,
    ): TestResponse {
        return $this->deleteJson(
            "/api/v1/platform/organizations/$organizationId/sponsor/households/$householdId",
            [],
            $this->makeApiHeaders($authIdentity),
        );
    }

    /**
     * Sends a PATCH request to update a household under an organization.
     *
     * @param int $organizationId
     * @param int $householdId
     * @param array $payload
     * @param Identity $authIdentity
     * @return TestResponse
     */
    protected function apiUpdateHouseholdRequest(
        int $organizationId,
        int $householdId,
        array $payload,
        Identity $authIdentity,
    ): TestResponse {
        return $this->patchJson(
            "/api/v1/platform/organizations/$organizationId/sponsor/households/$householdId",
            $payload,
            $this->makeApiHeaders($authIdentity),
        );
    }

    /**
     * Sends a GET request to view a specific household under an organization.
     *
     * @param int $organizationId
     * @param int $householdId
     * @param Identity $authIdentity
     * @return TestResponse
     */
    protected function apiViewHouseholdRequest(int $organizationId, int $householdId, Identity $authIdentity): TestResponse
    {
        return $this->getJson(
            "/api/v1/platform/organizations/$organizationId/sponsor/households/$householdId",
            $this->makeApiHeaders($authIdentity),
        );
    }

    /**
     * Sends a POST request to create a new household under an organization.
     *
     * @param int $organizationId
     * @param array $payload
     * @param Identity $authIdentity
     * @return TestResponse
     */
    protected function apiMakeHouseholdRequest(int $organizationId, array $payload, Identity $authIdentity): TestResponse
    {
        return $this->postJson(
            "/api/v1/platform/organizations/$organizationId/sponsor/households",
            $payload,
            $this->makeApiHeaders($authIdentity),
        );
    }

    /**
     * Sends a GET request to retrieve the list of households associated with a specific organization.
     *
     * @param int $organizationId
     * @param Identity $authIdentity
     * @return TestResponse
     */
    protected function apiListHouseholdsRequest(int $organizationId, Identity $authIdentity): TestResponse
    {
        return $this->getJson(
            "/api/v1/platform/organizations/$organizationId/sponsor/households",
            $this->makeApiHeaders($authIdentity),
        );
    }
}
