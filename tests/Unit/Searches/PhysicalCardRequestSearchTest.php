<?php

namespace Tests\Unit\Searches;

use App\Models\PhysicalCardRequest;
use App\Searches\PhysicalCardRequestSearch;

class PhysicalCardRequestSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new PhysicalCardRequestSearch([], PhysicalCardRequest::query());

        $this->assertQueryBuilds($search->query());
    }
}
