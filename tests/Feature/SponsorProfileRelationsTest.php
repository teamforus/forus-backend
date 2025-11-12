<?php

namespace Tests\Feature;

use App\Models\Identity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesApiRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;

class SponsorProfileRelationsTest extends TestCase
{
    use DatabaseTransactions;
    use MakesApiRequests;
    use MakesTestOrganizations;
    use MakesTestIdentities;
    use MakesTestFunds;

    /**
     * Tests that a sponsor can successfully list profile relations associated with a specific identity.
     *
     * @return void
     */
    public function testSponsorCanListProfileRelations(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_relations' => true]);

        $identity = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $organization->id);
        $related = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $organization->id);

        $this
            ->apiMakeProfileRelationRequest($organization->id, $identity->id, [
                'related_identity_id' => $related->id,
                'type' => 'partner',
                'subtype' => 'partner_unmarried',
                'living_together' => true,
            ], $organization->identity)
            ->assertCreated();

        $this
            ->apiListProfileRelationsRequest($organization->id, $identity->id, $organization->identity)
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.identity_id', $identity->id)
            ->assertJsonPath('data.0.related_identity_id', $related->id);
    }

    /**
     * Tests that a sponsor is forbidden from listing profile relations of another organization.
     *
     * @return void
     */
    public function testSponsorCannotListProfileRelationsOfAnotherOrganization(): void
    {
        $org1 = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_relations' => true]);
        $org2 = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_relations' => true]);

        $identity = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $org2->id);

        $this->apiListProfileRelationsRequest($org2->id, $identity->id, $org1->identity)
            ->assertForbidden();
    }

    /**
     * Tests that a sponsor can successfully create a profile relation between two profiles.
     *
     * @return void
     */
    public function testSponsorCanCreateProfileRelation(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_relations' => true]);

        $identity = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $organization->id);
        $related = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $organization->id);

        $profile = $organization->findOrMakeProfile($identity);
        $relatedProfile = $organization->findOrMakeProfile($related);

        $payload = [
            'related_identity_id' => $related->id,
            'type' => 'partner',
            'subtype' => 'partner_unmarried',
            'living_together' => true,
        ];

        $this->apiMakeProfileRelationRequest($organization->id, $identity->id, $payload, $organization->identity)
            ->assertCreated()
            ->assertJsonPath('data.profile_id', $profile->id)
            ->assertJsonPath('data.related_profile_id', $relatedProfile->id)
            ->assertJsonPath('data.type', 'partner')
            ->assertJsonPath('data.subtype', 'partner_unmarried')
            ->assertJsonPath('data.living_together', true);
    }

    /**
     * Tests that a sponsor is forbidden from creating a profile relation for an organization they do not belong to.
     *
     * @return void
     */
    public function testSponsorCannotCreateProfileRelationForAnotherOrganization(): void
    {
        $org1 = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_relations' => true]);
        $org2 = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_relations' => true]);

        $identity = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $org2->id);
        $related = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $org2->id);

        $payload = [
            'type' => 'partner',
            'subtype' => 'partner_unmarried',
            'living_together' => false,
            'related_identity_id' => $related->id,
        ];

        $this->apiMakeProfileRelationRequest($org2->id, $identity->id, $payload, $org1->identity)
            ->assertForbidden();
    }

    /**
     * Tests that a sponsor cannot create a profile relation with a profile from another organization.
     *
     * @return void
     */
    public function testSponsorCannotCreateRelationWithProfileFromAnotherOrganization(): void
    {
        $org1 = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_relations' => true]);
        $org2 = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_relations' => true]);

        $identity = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $org1->id);
        $related = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $org2->id);

        $payload = [
            'type' => 'partner',
            'subtype' => 'partner_unmarried',
            'living_together' => true,
            'related_identity_id' => $related->id,
        ];

        $this->apiMakeProfileRelationRequest($org1->id, $identity->id, $payload, $org1->identity)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['related_identity_id']);
    }

    /**
     * Tests that creating a profile relation with invalid data returns validation error responses.
     *
     * @return void
     */
    public function testCreateProfileRelationValidationErrors(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_relations' => true]);

        $identity = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $organization->id);
        $organization->findOrMakeProfile($identity);

        $payload = [
            'type' => 'invalid_type',
            'subtype' => 'invalid_subtype',
            'living_together' => 'not-a-boolean',
            'related_identity_id' => 999999, // non-existent
        ];

        $this->apiMakeProfileRelationRequest($organization->id, $identity->id, $payload, $organization->identity)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['related_identity_id', 'type', 'subtype', 'living_together']);
    }

    /**
     * Tests that a sponsor can successfully update a profile relation.
     *
     * @return void
     */
    public function testSponsorCanUpdateProfileRelation(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_relations' => true]);

        $identity = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $organization->id);
        $related = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $organization->id);

        $relationId = $this
            ->apiMakeProfileRelationRequest($organization->id, $identity->id, [
                'related_identity_id' => $related->id,
                'type' => 'partner',
                'subtype' => 'partner_unmarried',
                'living_together' => true,
            ], $organization->identity)
            ->assertCreated()
            ->json('data.id');

        $payload = [
            'type' => 'parent_child',
            'subtype' => 'foster_parent_foster_child',
            'living_together' => false,
        ];

        $this->apiUpdateProfileRelationRequest($organization->id, $identity->id, $relationId, $payload, $organization->identity)
            ->assertSuccessful()
            ->assertJsonPath('data.type', 'parent_child')
            ->assertJsonPath('data.subtype', 'foster_parent_foster_child')
            ->assertJsonPath('data.living_together', false);
    }

    /**
     * Tests that a sponsor is forbidden from updating a profile relation
     * belonging to a different organization than the one they are associated with.
     *
     * @return void
     */
    public function testSponsorCannotUpdateProfileRelationOfAnotherOrganization(): void
    {
        $org1 = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_relations' => true]);
        $org2 = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_relations' => true]);

        $identity = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $org2->id);
        $related = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $org2->id);

        $relationId = $this
            ->apiMakeProfileRelationRequest($org2->id, $identity->id, [
                'type' => 'partner',
                'subtype' => 'partner_unmarried',
                'living_together' => true,
                'related_identity_id' => $related->id,
            ], $org2->identity)
            ->assertCreated()
            ->json('data.id');

        $this->apiUpdateProfileRelationRequest(
            $org2->id,
            $identity->id,
            $relationId,
            [
                'type' => 'partner',
                'subtype' => 'partner_unmarried',
                'living_together' => true,
            ],
            $org1->identity
        )->assertForbidden();
    }

    /**
     * Tests that updating a profile relation with invalid data triggers validation error responses.
     *
     * @return void
     */
    public function testUpdateProfileRelationValidationErrors(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_relations' => true]);

        $identity = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $organization->id);
        $related = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $organization->id);

        $profile = $organization->findOrMakeProfile($identity);
        $relatedProfile = $organization->findOrMakeProfile($related);

        $relation = $profile->profile_relations()->create([
            'related_profile_id' => $relatedProfile->id,
            'type' => 'partner',
            'subtype' => 'partner_unmarried',
            'living_together' => true,
        ]);

        $payload = [
            'subtype' => 'invalid',
            'living_together' => 'invalid_bool',
        ];

        $this->apiUpdateProfileRelationRequest($organization->id, $identity->id, $relation->id, $payload, $organization->identity)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subtype', 'living_together']);
    }

    /**
     * Tests that a sponsor can successfully delete a profile relation.
     *
     * @return void
     */
    public function testSponsorCanDeleteProfileRelation(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_relations' => true]);

        $identity = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $organization->id);
        $related = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $organization->id);

        $relationId = $this
            ->apiMakeProfileRelationRequest($organization->id, $identity->id, [
                'type' => 'partner',
                'subtype' => 'partner_unmarried',
                'living_together' => true,
                'related_identity_id' => $related->id,
            ], $organization->identity)
            ->assertCreated()
            ->json('data.id');

        $this->apiDeleteProfileRelationRequest($organization->id, $identity->id, $relationId, $organization->identity)
            ->assertNoContent();

        $this->assertDatabaseMissing('profile_relations', [
            'id' => $relationId,
        ]);
    }

    /**
     * Tests that a sponsor is forbidden from deleting a profile relation belonging to another organization.
     *
     * @return void
     */
    public function testSponsorCannotDeleteProfileRelationOfAnotherOrganization(): void
    {
        $org1 = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_relations' => true]);
        $org2 = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles_relations' => true]);

        $identity = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $org2->id);
        $related = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $org2->id);

        $relationId = $this
            ->apiMakeProfileRelationRequest($org2->id, $identity->id, [
                'type' => 'partner',
                'subtype' => 'partner_unmarried',
                'living_together' => true,
                'related_identity_id' => $related->id,
            ], $org2->identity)
            ->assertCreated()
            ->json('data.id');

        $this->apiDeleteProfileRelationRequest($org2->id, $identity->id, $relationId, $org1->identity)
            ->assertForbidden();
    }

    /**
     * Sends a DELETE request to remove a profile relation within the platform.
     *
     * @param int $organizationId
     * @param int $identityId
     * @param int $relationId
     * @param Identity $authIdentity
     * @return TestResponse
     */
    protected function apiDeleteProfileRelationRequest(
        int $organizationId,
        int $identityId,
        int $relationId,
        Identity $authIdentity
    ): TestResponse {
        return $this->deleteJson(
            "/api/v1/platform/organizations/$organizationId/sponsor/identities/$identityId/relations/$relationId",
            [],
            $this->makeApiHeaders($authIdentity),
        );
    }

    /**
     * Sends a PATCH request to update a profile relation within the platform.
     *
     * @param int $organizationId
     * @param int $identityId
     * @param int $relationId
     * @param array $payload
     * @param Identity $authIdentity
     * @return TestResponse
     */
    protected function apiUpdateProfileRelationRequest(
        int $organizationId,
        int $identityId,
        int $relationId,
        array $payload,
        Identity $authIdentity
    ): TestResponse {
        return $this->patchJson(
            "/api/v1/platform/organizations/$organizationId/sponsor/identities/$identityId/relations/$relationId",
            $payload,
            $this->makeApiHeaders($authIdentity),
        );
    }

    /**
     * Sends a POST request to create a new profile relation within the platform.
     *
     * @param int $organizationId
     * @param int $identityId
     * @param array $payload
     * @param Identity $authIdentity
     * @return TestResponse
     */
    protected function apiMakeProfileRelationRequest(
        int $organizationId,
        int $identityId,
        array $payload,
        Identity $authIdentity
    ): TestResponse {
        return $this->postJson(
            "/api/v1/platform/organizations/$organizationId/sponsor/identities/$identityId/relations",
            $payload,
            $this->makeApiHeaders($authIdentity),
        );
    }

    /**
     * Sends a GET request to list profile relations of a given identity.
     *
     * @param int $organizationId
     * @param int $identityId
     * @param Identity $authIdentity
     * @return TestResponse
     */
    protected function apiListProfileRelationsRequest(
        int $organizationId,
        int $identityId,
        Identity $authIdentity
    ): TestResponse {
        return $this->getJson(
            "/api/v1/platform/organizations/$organizationId/sponsor/identities/$identityId/relations",
            $this->makeApiHeaders($authIdentity),
        );
    }
}
