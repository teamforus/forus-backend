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
            $table->decimal('amount', 10, 5);
            $table->bigInteger('gas')->default(0);

            $table->string('wallet_from_address', 64)->nullable();
            $table->string('wallet_to_address', 64)->nullable();

            $table->foreign('wallet_from_address'
            )->references('address')->on('ethereum_wallets')->onDelete('cascade');

            $table->foreign('wallet_to_address'
            )->references('address')->on('ethereum_wallets')->onDelete('cascade');

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
