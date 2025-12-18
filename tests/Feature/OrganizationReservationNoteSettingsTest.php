<?php

namespace Tests\Feature;

use App\Models\Organization;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class OrganizationReservationNoteSettingsTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * @throws Throwable
     * @return void
     */
    public function testUpdateOrganizationReservationNoteSettingsSuccess(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->assertOrganizationReservationNoteSettingsSuccess($organization, ['reservation_note' => false]);

        $this->assertOrganizationReservationNoteSettingsSuccess($organization, [
            'reservation_note' => true,
            'reservation_note_text' => 'test note text',
        ]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUpdateOrganizationReservationNoteSettingsFail(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->makeOrganizationUpdateRequest($organization, [
            'reservation_note' => 'text',
            'reservation_note_text' => ['test note text'],
        ])->assertJsonValidationErrors(['reservation_note', 'reservation_note_text']);

        $this->makeOrganizationUpdateRequest($organization, ['reservation_note' => true])
            ->assertJsonValidationErrors(['reservation_note_text']);

        $this->makeOrganizationUpdateRequest($organization, ['reservation_note_text' => true])
            ->assertJsonValidationErrors(['reservation_note_text']);
    }

    /**
     * @param Organization $organization
     * @param array $attributes
     * @return void
     */
    protected function assertOrganizationReservationNoteSettingsSuccess(
        Organization $organization,
        array $attributes,
    ): void {
        $response = $this->makeOrganizationUpdateRequest($organization, $attributes);
        $response->assertSuccessful();
        $data = $response->json('data');

        foreach ($attributes as $key => $value) {
            $this->assertEquals($value, $data[$key]);
        }
    }

    /**
     * @param Organization $organization
     * @param array $params
     * @return TestResponse
     */
    protected function makeOrganizationUpdateRequest(Organization $organization, array $params): TestResponse
    {
        return $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/update-reservation-fields",
            $params,
            $this->makeApiHeaders($organization->identity),
        );
    }
}
