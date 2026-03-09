<?php

namespace Tests\Unit\Searches;

use App\Models\ProductCategory;
use App\Searches\ProductCategorySearch;

class ProductCategorySearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new ProductCategorySearch([], ProductCategory::query());

        $this->assertQueryBuilds($search->query());
    }
}
