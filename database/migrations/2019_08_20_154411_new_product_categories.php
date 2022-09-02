<?php

use App\Models\ProductCategory;
use Illuminate\Database\Migrations\Migration;
use Database\Seeders\ProductCategoriesTableSeeder;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
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
                13 => 412,

                // products
                8 => 752000,
                9 => 753000,
                10 => 754006,
                11 => 754000,
                12 => 751000,
            ];

            ProductCategoriesTableSeeder::seedProducts(true);
            ProductCategoriesTableSeeder::migrateProducts($map);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        
    }
};
