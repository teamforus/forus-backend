<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('reservation_extra_payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reservation_extra_payment_id');
            $table->string('refund_id')->nullable();
            $table->enum('state', [
                'queued', 'failed', 'pending', 'refunded', 'canceled', 'processing',
            ])->default('pending');
            $table->decimal('amount');
            $table->string('currency', 3);
            $table->timestamps();

            $table->foreign('reservation_extra_payment_id', 'extra_payment_refunds_payment_id_foreign')
                ->references('id')->on('reservation_extra_payments')
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
        Schema::dropIfExists('reservation_extra_payment_refunds');
    }
};
