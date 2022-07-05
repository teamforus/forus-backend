<?php

use Illuminate\Database\Migrations\Migration;
use Database\Seeders\ImplementationsNotificationBrandingSeeder;

/**
 * @noinspection PhpUnused
 */
class MigrateEmailColorsAndLogosToImplementationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     * @throws Exception
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
    public function down(): void {}
}
