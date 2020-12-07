<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNoPriceTypeAndDiscountFieldsToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('products', static function (Blueprint $table) {
            $table->enum('no_price_type', ['free', 'discount'])->default('free')->after('no_price');
            $table->decimal('no_price_discount', 8, 2)->nullable()->after('no_price_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('products', static function (Blueprint $table) {
            $table->dropColumn('no_price_type');
            $table->dropColumn('no_price_discount');
        });
    }
}
