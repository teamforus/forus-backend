<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('transactions');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('token_id')->unsigned();
            $table->integer('from_wallet_id')->unsigned();
            $table->integer('to_wallet_id')->unsigned();
            $table->integer('amount')->unsigned();
            $table->string('state', 10);
            $table->timestamps();

            $table->foreign('token_id'
            )->references('id')->on('tokens')->onDelete('cascade');

            $table->foreign('from_wallet_id'
            )->references('id')->on('wallets')->onDelete('cascade');

            $table->foreign('to_wallet_id'
            )->references('id')->on('wallets')->onDelete('cascade');
        });
    }
}
