<?php

namespace Tests\Unit\Searches\Sponsor;

use App\Models\HouseholdProfile;
use App\Searches\Sponsor\HouseholdProfilesSearch;
use Tests\Unit\Searches\SearchTestCase;

class HouseholdProfilesSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new HouseholdProfilesSearch([], HouseholdProfile::query());

        $this->assertQueryBuilds($search->query());
    }
}
