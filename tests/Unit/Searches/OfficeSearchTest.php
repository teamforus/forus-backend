<?php

namespace Tests\Unit\Searches;

use App\Models\Office;
use App\Searches\OfficeSearch;

class OfficeSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new OfficeSearch([], Office::query());

        $this->assertQueryBuilds($search->query());
    }
}
