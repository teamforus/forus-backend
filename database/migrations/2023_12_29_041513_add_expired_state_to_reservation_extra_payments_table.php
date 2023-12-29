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
        DB::statement(implode(' ', [
            "ALTER TABLE `reservation_extra_payments`",
            "CHANGE `state` `state` ENUM('open', 'paid', 'failed', 'pending', 'canceled', 'expired')",
            "NOT NULL DEFAULT 'pending'",
            "AFTER `method`"
        ]));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
};
