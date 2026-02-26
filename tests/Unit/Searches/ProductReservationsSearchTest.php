<?php

namespace Tests\Unit\Searches;

use App\Models\ProductReservation;
use App\Searches\ProductReservationsSearch;

class ProductReservationsSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new ProductReservationsSearch([], ProductReservation::query());

        $this->assertQueryBuilds($search->query());
    }
}
