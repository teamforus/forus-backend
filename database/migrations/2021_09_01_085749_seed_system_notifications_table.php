<?php

use Illuminate\Database\Migrations\Migration;
use Database\Seeders\SystemNotificationsTableSeeder;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        (new SystemNotificationsTableSeeder())->run();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
};
