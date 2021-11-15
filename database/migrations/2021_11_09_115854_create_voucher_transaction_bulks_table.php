<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 */
class CreateVoucherTransactionBulksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('voucher_transaction_bulks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('bank_connection_id')->nullable();
            $table->unsignedInteger('payment_id')->nullable();
            $table->string('state')->default('pending');
            $table->integer('state_fetched_times')->default(0);
            $table->timestamp('state_fetched_at')->nullable();
            $table->timestamps();

            $table->foreign('bank_connection_id')
                ->references('id')->on('bank_connections')
                ->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_transaction_bulks');
    }
}
