<?php

namespace Tests\Unit\Searches;

use App\Models\PrevalidationRequest;
use App\Searches\PrevalidationRequestSearch;

class PrevalidationRequestSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new PrevalidationRequestSearch([], PrevalidationRequest::query());

        $this->assertQueryBuilds($search->query());
    }
}
