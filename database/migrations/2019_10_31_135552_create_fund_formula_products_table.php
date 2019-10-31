<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFundFormulaProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fund_formula_products', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('fund_id')->nullable();
            $table->unsignedInteger('product_id')->nullable();
            $table->decimal('price', 8, 2)->unsigned();
            $table->timestamps();

            $table->foreign('fund_id'
            )->references('id')->on('funds')->onDelete('set null');

            $table->foreign('product_id'
            )->references('id')->on('products')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fund_formula_products');
    }
}
