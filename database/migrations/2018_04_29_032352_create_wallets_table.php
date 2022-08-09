<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('identity_id')->unsigned();
            $table->timestamps();

            $table->foreign('identity_id'
            )->references('id')->on('identities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
