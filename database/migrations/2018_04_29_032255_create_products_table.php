<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('organization_id')->unsigned();
            $table->integer('product_category_id')->unsigned();
            $table->string('name', 20);
            $table->text('description');
            $table->integer('price')->unsigned();
            $table->integer('old_price')->unsigned();
            $table->integer('total_amount')->unsigned();
            $table->integer('sold_amount')->unsigned();
            $table->timestamps();

            $table->foreign('organization_id'
            )->references('id')->on('organizations')->onDelete('cascade');

            $table->foreign('product_category_id'
            )->references('id')->on('product_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
