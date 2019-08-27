<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEthereumWalletTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ethereum_wallet_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('hash')->nullable();
            $table->string('block_hash')->nullable();
            $table->string('block_number')->nullable();
            $table->decimal('amount');
            $table->bigInteger('gas')->default(0);

            $table->integer('wallet_from_id')->unsigned();
            $table->integer('wallet_to_id')->unsigned();

            $table->foreign('wallet_from_id'
            )->references('id')->on('ethereum_wallets')->onDelete('cascade');

            $table->foreign('wallet_to_id'
            )->references('id')->on('ethereum_wallets')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ethereum_wallet_transactions');
    }
}
