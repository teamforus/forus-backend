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
        DB::statement("
            ALTER TABLE `voucher_transactions` 
            CHANGE `target` `target` ENUM('provider', 'iban', 'top_up') 
            NOT NULL DEFAULT 'provider';
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
};
