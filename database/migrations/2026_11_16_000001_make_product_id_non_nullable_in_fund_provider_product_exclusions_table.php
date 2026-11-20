<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table('fund_provider_product_exclusions', function (Blueprint $table) {
            $table->dropForeign('fund_provider_product_exclusions_product_id_foreign');
        });

        Schema::table('fund_provider_product_exclusions', function (Blueprint $table) {
            $table->unsignedInteger('product_id')->nullable(false)->change();

            $table->foreign('product_id', 'fund_provider_product_exclusions_product_id_foreign')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_provider_product_exclusions', function (Blueprint $table) {
            $table->dropForeign('fund_provider_product_exclusions_product_id_foreign');
        });

        Schema::table('fund_provider_product_exclusions', function (Blueprint $table) {
            $table->unsignedInteger('product_id')->nullable()->default(null)->change();

            $table->foreign('product_id', 'fund_provider_product_exclusions_product_id_foreign')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });
    }
};
