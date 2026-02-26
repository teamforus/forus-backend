<?php

namespace Tests\Unit\Searches;

use App\Models\Fund;
use App\Searches\FundSearch;

class FundSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new FundSearch([], Fund::query());

        $this->assertQueryBuilds($search->query());
    }
}
