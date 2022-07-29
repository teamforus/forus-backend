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
        Schema::create('voucher_transaction_notes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('voucher_transaction_id')->unsigned();
            $table->string('icon', 10)->default('quote');
            $table->string('message', 255)->default('');
            $table->boolean('pin_to_top')->default(false);
            $table->string('group', 10)->default('');
            $table->timestamps();

            $table->foreign('voucher_transaction_id')
                ->references('id')->on('voucher_transactions')
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
        Schema::dropIfExists('voucher_transaction_notes');
    }
};
