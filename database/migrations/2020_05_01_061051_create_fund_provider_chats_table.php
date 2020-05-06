<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFundProviderChatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fund_provider_chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('product_id')->nullable();
            $table->unsignedInteger('fund_provider_id')->nullable();
            $table->string('identity_address', 200);
            $table->timestamps();

            $table->foreign('product_id'
            )->references('id')->on('products')->onDelete('cascade');

            $table->foreign('fund_provider_id'
            )->references('id')->on('fund_providers')->onDelete('cascade');

            $table->foreign('identity_address'
            )->references('address')->on('identities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fund_provider_chats');
    }
}
