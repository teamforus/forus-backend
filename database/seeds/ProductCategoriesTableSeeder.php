<?php

use App\Models\ProductCategory;

class ProductCategoriesTableSeeder extends DatabaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ProductCategory::create([
            'key'       => 'bikes',
            'name'      => 'Bikes'
        ]);
    }
}
