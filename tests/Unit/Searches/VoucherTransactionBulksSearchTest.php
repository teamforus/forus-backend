<?php

namespace Tests\Unit\Searches;

use App\Models\VoucherTransactionBulk;
use App\Searches\VoucherTransactionBulksSearch;

class VoucherTransactionBulksSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new VoucherTransactionBulksSearch([], VoucherTransactionBulk::query());

        $this->assertQueryBuilds($search->query());
    }
}
