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
        Schema::create('fund_top_up_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('fund_top_up_id')->unsigned();
            $table->float('amount')->unsigned()->nullable();
            $table->string('bunq_transaction_id', 20)->nullable();
            $table->timestamps();

            $table->foreign('fund_top_up_id')
                ->references('id')
                ->on('fund_top_ups')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_top_up_transactions');
    }
};
