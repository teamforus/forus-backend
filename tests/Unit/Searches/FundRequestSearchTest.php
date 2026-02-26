<?php

namespace Tests\Unit\Searches;

use App\Models\FundRequest;
use App\Searches\FundRequestSearch;

class FundRequestSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new FundRequestSearch([], FundRequest::query());

        $this->assertQueryBuilds($search->query());
    }
}
