<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class DropWalletAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('wallet_assets');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('wallet_assets', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('wallet_id')->unsigned();
            $table->string('address', 42)->nullable();
            $table->timestamps();

            $table->foreign('wallet_id'
            )->references('id')->on('wallets')->onDelete('cascade');
        });
    }
}
