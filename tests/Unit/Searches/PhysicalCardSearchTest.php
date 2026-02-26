<?php

namespace Tests\Unit\Searches;

use App\Models\PhysicalCard;
use App\Searches\PhysicalCardSearch;

class PhysicalCardSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new PhysicalCardSearch([], PhysicalCard::query());

        $this->assertQueryBuilds($search->query());
    }
}
