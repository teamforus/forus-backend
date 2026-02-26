<?php

namespace Tests\Unit\Searches;

use App\Models\FundTopUpTransaction;
use App\Searches\FundTopUpTransactionSearch;

class FundTopUpTransactionSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new FundTopUpTransactionSearch([], FundTopUpTransaction::query());

        $this->assertQueryBuilds($search->query());
    }
}
