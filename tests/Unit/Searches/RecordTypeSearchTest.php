<?php

namespace Tests\Unit\Searches;

use App\Models\RecordType;
use App\Searches\RecordTypeSearch;

class RecordTypeSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new RecordTypeSearch([], RecordType::query());

        $this->assertQueryBuilds($search->query());
    }
}
