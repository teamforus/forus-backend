<?php

namespace Tests\Unit\Searches\Sponsor;

use App\Models\Household;
use App\Searches\Sponsor\HouseholdSearch;
use Tests\Unit\Searches\SearchTestCase;

class HouseholdSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new HouseholdSearch([], Household::query());

        $this->assertQueryBuilds($search->query());
    }
}
