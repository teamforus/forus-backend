<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class RemoveOldPriceFromFundProviderProductsTable
 * @noinspection PhpUnused
 */
class RemoveOldPriceFromFundProviderProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fund_provider_products', function (Blueprint $table) {
            $table->dropColumn('old_price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fund_provider_products', function (Blueprint $table) {
            $table->decimal('old_price')->nullable()->after('price');
        });
    }
}
