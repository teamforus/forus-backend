<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFundTopUpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fund_top_ups', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('fund_id')->unsigned()->nullable();
            $table->float('amount')->unsigned()->nullable();
            $table->string('bunq_transaction_id',20)->nullable();
            $table->string('code',20);
            $table->string('state')->default("pending");
            $table->timestamps();

            $table->foreign('fund_id'
            )->references('id')->on('funds')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fund_top_ups');
    }
}
