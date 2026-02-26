<?php

namespace Tests\Unit\Searches;

use App\Models\ReservationExtraPayment;
use App\Searches\ReservationExtraPaymentsSearch;

class ReservationExtraPaymentsSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new ReservationExtraPaymentsSearch([], ReservationExtraPayment::query());

        $this->assertQueryBuilds($search->query());
    }
}
