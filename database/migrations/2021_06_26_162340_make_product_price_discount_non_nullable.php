<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Product;

/**
 * Class MakeProductPriceDiscountNonNullable
 * @noinspection PhpUnused
 */
class MakeProductPriceDiscountNonNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     * @throws \Doctrine\DBAL\Exception
     */
    public function up()
    {
        DB::getDoctrineSchemaManager()
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('enum', 'string');

        Product::whereNull('price_discount')->update([
            'price_discount' => 0,
        ]);

        Schema::table('products', function(Blueprint $table) {
            $table->decimal('price_discount')->default('0.0')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     * @throws \Doctrine\DBAL\Exception
     */
    public function down()
    {
        DB::getDoctrineSchemaManager()
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('enum', 'string');

        Schema::table('products', function(Blueprint $table) {
            $table->decimal('price_discount')->nullable()->change();
        });
    }
}
