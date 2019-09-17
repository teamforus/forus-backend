<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\ProductCategory;

class NewProductCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $count = ProductCategory::count();

        /** Seed only when it's an existing db */
        if ($count > 0 && $count < 100) {
            $map = [
                // products
                1 => 784,
                2 => 1026,
                3 => 988,
                4 => 1144,
                5 => 222,
                6 => 166,
                7 => 1239,
                // 8 => 499676, ??

                // products
                8 => 751000,
                9 => 752000,
                10 => 753000,
                11 => 753000,
                12 => 751000,
            ];

            ProductCategoriesTableSeeder::seedProducts(true);
            ProductCategoriesTableSeeder::migrateProducts($map);
            ProductCategoriesTableSeeder::migrateFundProductCategories($map);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
