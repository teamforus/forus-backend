<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateProductsAddExpirationAndSoldOut extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('sold_amount');
            $table->timestamp('expire_at');
            $table->boolean('sold_out')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('sold_amount')->unsigned();
            $table->dropColumn('expire_at');
            $table->dropColumn('sold_out');
        });
    }
}
