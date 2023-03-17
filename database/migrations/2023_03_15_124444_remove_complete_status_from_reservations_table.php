<?php

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
        DB::statement(
            "ALTER TABLE `product_reservations` CHANGE `state` `state` ".
            "ENUM('pending', 'accepted', 'rejected', 'canceled', 'canceled_by_client') DEFAULT 'pending';"
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {
        DB::statement(
            "ALTER TABLE `product_reservations` CHANGE `state` `state` ".
            "ENUM('pending', 'accepted', 'rejected', 'canceled', 'canceled_by_client', 'complete') DEFAULT 'pending';"
        );}
};
