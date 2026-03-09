<?php

namespace Tests\Unit\Searches;

use App\Models\Voucher;
use App\Searches\VouchersSearch;

class VouchersSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new VouchersSearch([
            'type' => 'all',
            'source' => 'all',
        ], Voucher::query());

        $this->assertQueryBuilds($search->query());
    }
}
