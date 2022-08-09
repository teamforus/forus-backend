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
        DB::statement("ALTER TABLE `funds` CHANGE `type` `type` ENUM('budget', 'subsidies', 'external') DEFAULT 'budget';");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `funds` CHANGE `type` `type` ENUM('budget', 'subsidies') DEFAULT 'budget';");
    }
};
