<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fund_product_limit_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fund_product_limit_id');
            $table->unsignedInteger('product_id');

            $table->foreign('fund_product_limit_id')
                ->references('id')
                ->on('fund_product_limits')
                ->onDelete('cascade');

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->unique(
                ['fund_product_limit_id', 'product_id'],
                'fund_product_limit_products_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_product_limit_products');
    }
};
