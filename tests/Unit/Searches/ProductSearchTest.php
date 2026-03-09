<?php

namespace Tests\Unit\Searches;

use App\Models\Product;
use App\Searches\ProductSearch;

class ProductSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new ProductSearch([], Product::query());

        $this->assertQueryBuilds($search->query());
    }

    
}
