<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * @noinspection PhpUnused
 */
class DropWalletTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::dropIfExists('wallet_tokens');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::create('wallet_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('wallet_id')->unsigned();
            $table->integer('token_id')->unsigned();
            $table->integer('amount');
            $table->timestamps();

            $table->foreign('wallet_id'
            )->references('id')->on('wallets')->onDelete('cascade');

            $table->foreign('token_id'
            )->references('id')->on('tokens')->onDelete('cascade');
        });
    }
}
