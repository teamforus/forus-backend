<?php

namespace Tests\Unit\Searches;

use App\Models\Prevalidation;
use App\Searches\PrevalidationSearch;

class PrevalidationSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new PrevalidationSearch([], Prevalidation::query());

        $this->assertQueryBuilds($search->query());
    }
}
