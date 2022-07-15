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
            "ALTER TABLE `fund_request_records` CHANGE `state` `state` ".
            "ENUM('pending', 'approved', 'declined', 'disregarded') DEFAULT 'pending';"
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        DB::statement(
            "ALTER TABLE `fund_request_records` CHANGE `state` `state` ".
            "ENUM('pending', 'approved', 'declined') DEFAULT 'pending';"
        );
    }
};
