<?php

use Illuminate\Database\Migrations\Migration;
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
        Schema::table('reservations', function () {
            DB::statement(
                "ALTER TABLE `product_reservations` CHANGE `state` `state` ".
                "ENUM('pending', 'accepted', 'rejected', 'canceled', 'canceled_by_client', 'complete') DEFAULT 'pending';"
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
};
