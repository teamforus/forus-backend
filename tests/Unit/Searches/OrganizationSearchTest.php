<?php

namespace Tests\Unit\Searches;

use App\Models\Organization;
use App\Searches\OrganizationSearch;

class OrganizationSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new OrganizationSearch([], Organization::query());

        $this->assertQueryBuilds($search->query());
    }

    
}
