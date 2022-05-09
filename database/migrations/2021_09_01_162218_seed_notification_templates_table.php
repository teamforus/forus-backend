<?php

use Illuminate\Database\Migrations\Migration;
use Database\Seeders\NotificationTemplatesTableSeeder;

/**
 * @noinspection PhpUnused
 */
class SeedNotificationTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     * @throws Exception
     */
    public function up(): void
    {
        (new NotificationTemplatesTableSeeder())->run();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
}
