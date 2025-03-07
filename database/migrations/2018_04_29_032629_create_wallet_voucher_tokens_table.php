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
        Schema::create('wallet_voucher_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('wallet_voucher_id')->unsigned();
            $table->string('type', 20);
            $table->string('token', 64);
            $table->integer('expires_in')->unsigned();
            $table->timestamps();

            $table->foreign('wallet_voucher_id')
                ->references('id')
                ->on('wallet_vouchers')
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
        Schema::dropIfExists('wallet_voucher_tokens');
    }
};
