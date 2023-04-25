<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fund_provider_product_exclusions', function (Blueprint $table) {
            $table->index(['product_id', 'fund_provider_id'], 'table_product_id_fund_provider_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_provider_product_exclusions', function (Blueprint $table) {
            $table->dropForeign('fund_provider_product_exclusions_product_id_foreign');
            $table->dropIndex('table_product_id_fund_provider_id_index');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }
};
