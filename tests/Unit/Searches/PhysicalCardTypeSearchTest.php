<?php

namespace Tests\Unit\Searches;

use App\Models\PhysicalCardType;
use App\Searches\PhysicalCardTypeSearch;

class PhysicalCardTypeSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new PhysicalCardTypeSearch([], PhysicalCardType::query());

        $this->assertQueryBuilds($search->query());
    }
}
