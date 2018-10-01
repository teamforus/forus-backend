<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVoucherTransactionBunqColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('voucher_transactions', function(Blueprint $table) {
            $table->integer('payment_id')->unsigned()->nullable();
            $table->integer('attempts')->unsigned()->default(0);
            $table->string('state')->default('pending');

            $table->timestamp('last_attempt_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
