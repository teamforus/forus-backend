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
            'key'       => 'boeken',
            'name'      => 'Boeken'
        ]);

        ProductCategory::create([
            'key'       => 'fiets',
            'name'      => 'Fiets'
        ]);

        ProductCategory::create([
            'key'       => 'sport-accessoires',
            'name'      => 'Sport'
        ]);

        ProductCategory::create([
            'key'       => 'zwemmen',
            'name'      => 'Zwemmen'
        ]);

        ProductCategory::create([
            'key'       => 'computer',
            'name'      => 'Computer'
        ]);

        ProductCategory::create([
            'key'       => 'kleding',
            'name'      => 'Kleding'
        ]);

        ProductCategory::create([
            'key'       => 'speelgoed',
            'name'      => 'Speelgoed'
        ]);
    }
}
