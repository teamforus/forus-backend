<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
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

        DB::statement(
            "ALTER TABLE `product_reservations` CHANGE `state` `state` " .
            "ENUM('" . implode("', '", $states) . "') DEFAULT 'pending';",
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $states = [
            'waiting', 'pending', 'accepted', 'rejected', 'canceled', 'canceled_by_client',
            'canceled_payment_failed', 'canceled_payment_expired', 'canceled_payment_canceled',
        ];

        DB::statement(
            "ALTER TABLE `product_reservations` CHANGE `state` `state` " .
            "ENUM('" . implode("', '", $states) . "') DEFAULT 'pending';",
        );
    }
};
