<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vouchers', function(Blueprint $table) {
            $table->integer('product_id')->unsigned()->nullable();
            $table->integer('parent_id')->unsigned()->nullable();

            $table->foreign('product_id'
            )->references('id')->on('products')->onDelete('cascade');

            $table->foreign('parent_id'
            )->references('id')->on('vouchers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vouchers', function(Blueprint $table) {
            $table->dropForeign('vouchers_product_id_foreign');
            $table->dropForeign('vouchers_parent_id_foreign');

            $table->dropColumn('product_id');
            $table->dropColumn('parent_id');
        });
    }
}
