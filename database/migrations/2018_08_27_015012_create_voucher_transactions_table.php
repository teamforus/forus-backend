<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVoucherTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('voucher_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('voucher_id')->unsigned();
            $table->integer('organization_id')->unsigned();
            $table->integer('product_id')->unsigned()->nullable();
            $table->float('amount')->unsigned();
            $table->string('address', 200);
            $table->timestamps();

            $table->foreign('voucher_id'
            )->references('id')->on('vouchers')->onDelete('cascade');

            $table->foreign('organization_id'
            )->references('id')->on('organizations')->onDelete('cascade');

            $table->foreign('product_id'
            )->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('voucher_transactions');
    }
}
