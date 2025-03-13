<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $states = [
            'waiting', 'pending', 'accepted', 'rejected', 'canceled', 'canceled_by_client',
            'canceled_payment_failed', 'canceled_payment_expired', 'canceled_payment_canceled',
            'canceled_by_sponsor',
        ];

        Schema::table('product_reservations', function (Blueprint $table) use ($states) {
            $table->enum('state', $states)->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
