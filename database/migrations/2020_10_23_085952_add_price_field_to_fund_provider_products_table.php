<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 */
class AddPriceFieldToFundProviderProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fund_provider_products', static function (Blueprint $table) {
            $table->decimal('price', 8, 2)->nullable()->after('amount');
            $table->decimal('old_price', 8, 2)->nullable()->after('price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_provider_products', static function (Blueprint $table) {
            $table->dropColumn('price');
            $table->dropColumn('old_price');
        });
    }
}
