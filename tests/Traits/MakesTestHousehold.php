<?php

namespace Tests\Traits;

use App\Models\Household;
use App\Models\HouseholdProfile;
use App\Models\Organization;
use App\Models\Profile;
use Illuminate\Support\Str;

trait MakesTestHousehold
{
    /**
     * @param Organization $organization
     * @param string|null $uid
     * @param string|null $living_arrangement
     * @param array $data
     * @return Household
     */
    protected function makeTestHousehold(
        Organization $organization,
        string $uid = null,
        string $living_arrangement = null,
        array $data = []
    ): Household {
        return Household::create([
            ...$data,
            'uid' => $uid ?? Str::random(20),
            'living_arrangement' => $living_arrangement ?? Household::LIVING_ARRANGEMENT_UNKNOWN,
            'organization_id' => $organization->id,
        ]);
    }

    /**
     * @param Household $household
     * @param Profile $profile
     * @return HouseholdProfile
     */
    protected function makeTestHouseholdProfile(Household $household, Profile $profile): HouseholdProfile
    {
        return $household->household_profiles()->create([
            'profile_id' => $profile->id,
        ]);
    }
}
