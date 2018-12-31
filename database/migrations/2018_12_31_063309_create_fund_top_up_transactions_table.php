<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFundTopUpTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fund_top_up_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('fund_top_up_id')->unsigned();
            $table->float('amount')->unsigned()->nullable();
            $table->string('bunq_transaction_id',20)->nullable();
            $table->timestamps();

            $table->foreign('fund_top_up_id'
            )->references('id')->on('fund_top_ups')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fund_top_up_transactions');
    }
}
