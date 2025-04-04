<?php

use Database\Seeders\ImplementationsNotificationBrandingSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * @noinspection PhpUnused
 */
class MigrateEmailColorsAndLogosToImplementationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @throws Throwable
     * @return void
     */
    public function up(): void
    {
        (new ImplementationsNotificationBrandingSeeder())->run();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
    }
}
