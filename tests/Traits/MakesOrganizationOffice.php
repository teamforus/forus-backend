<?php

namespace Tests\Traits;

use App\Models\Office;
use App\Models\Organization;

trait MakesOrganizationOffice
{
    /**
     * @param Organization $organization
     * @param array $organizationData
     * @return Office
     */
    protected function makeOrganizationOffice(Organization $organization, array $organizationData = []): Office
    {
        return $organization->offices()->create([
            'address' => fake()->text(30),
            'branch_id' => '114324234',
            'branch_name' => 'JKE234',
            'branch_number' => '123456789123',
            ...$organizationData,
        ]);
    }
}