<?php

namespace Tests\Unit\Searches;

use App\Models\Record;
use App\Searches\RecordSearch;

class RecordSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new RecordSearch([], Record::query());

        $this->assertQueryBuilds($search->query());
    }

    
}
