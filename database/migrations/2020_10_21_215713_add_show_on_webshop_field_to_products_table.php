<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShowOnWebshopFieldToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('products', static function (Blueprint $table) {
            $table->boolean('show_on_webshop')->default(true)->after('no_price');
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
            $table->dropColumn('show_on_webshop');
        });
    }
}
