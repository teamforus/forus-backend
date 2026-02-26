<?php

namespace Tests\Unit\Searches;

use App\Models\Reimbursement;
use App\Searches\ReimbursementsSearch;

class ReimbursementsSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new ReimbursementsSearch([], Reimbursement::query());

        $this->assertQueryBuilds($search->query());
    }
}
