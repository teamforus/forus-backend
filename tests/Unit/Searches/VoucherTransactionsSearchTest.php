<?php

namespace Tests\Unit\Searches;

use App\Models\VoucherTransaction;
use App\Searches\VoucherTransactionsSearch;

class VoucherTransactionsSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new VoucherTransactionsSearch([], VoucherTransaction::query());

        $this->assertQueryBuilds($search->query());
    }
}
