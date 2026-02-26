<?php

namespace Tests\Unit\Searches;

use App\Searches\WebshopGenericSearch;

class WebshopGenericSearchTest extends SearchTestCase
{
    /**
     * @return void
     * @throws \Exception
     */
    public function testQueryBuilds(): void
    {
        $search = new WebshopGenericSearch([]);

        $this->assertQueryBuilds($search->query('funds'));
    }
}
