<?php

namespace Tests\Unit\Searches;

use App\Models\VoucherRecord;
use App\Searches\VoucherRecordSearch;

class VoucherRecordSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new VoucherRecordSearch([], VoucherRecord::query());

        $this->assertQueryBuilds($search->query());
    }
}
