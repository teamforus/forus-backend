<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('fund_formula_products', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('fund_id');
            $table->unsignedInteger('product_id');
            $table->decimal('price', 8, 2)->unsigned();
            $table->timestamps();

            $table->foreign('fund_id')
                ->references('id')
                ->on('funds')
                ->onDelete('restrict');

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_formula_products');
    }
};
