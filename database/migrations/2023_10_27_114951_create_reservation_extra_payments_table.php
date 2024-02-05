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
        Schema::create('reservation_extra_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_reservation_id');
            $table->enum('type', ['mollie'])->default('mollie');
            $table->string('payment_id')->nullable();
            $table->string('method', 50)->nullable();
            $table->enum('state', [
                'open', 'paid', 'failed', 'pending', 'canceled',
            ])->default('pending');
            $table->decimal('amount');
            $table->decimal('amount_refunded')->nullable();
            $table->decimal('amount_captured')->nullable();
            $table->decimal('amount_remaining')->nullable();
            $table->string('currency', 3);
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_reservation_id')
                ->references('id')->on('product_reservations')
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
        Schema::dropIfExists('reservation_extra_payments');
    }
};
